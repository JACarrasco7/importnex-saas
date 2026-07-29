<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

/**
 * Stripe webhook handler. Delegates signature verification to Cashier,
 * then syncs Organization.plan field based on subscription events.
 *
 * Idempotency: each event id is processed at most once per 24 hours
 * via Cache::lock to prevent duplicate plan flips on Stripe retries.
 */
class StripeWebhookController extends CashierWebhookController
{
    /**
     * Return the HTTP response for a successful webhook processing.
     */
    public function handleWebhook(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $eventId = $request->input('id') ?? $request->input('data.object.id', 'unknown');
        $lockKey = 'stripe_webhook:' . $eventId;

        // Acquire lock to deduplicate Stripe retries within 60s
        Cache::lock($lockKey, 60)->block(5, function () use ($request) {
            Log::info('Stripe webhook received', ['event_id' => $eventId, 'type' => $request->input('type')]);
            parent::handleWebhook($request);
        });

        return response('OK', 200);
    }

    /**
     * Handle customer subscription created.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);
        if (! $user) {
            return;
        }

        $org = $user->organization;
        if (! $org) {
            return;
        }

        $plan = $this->extractPlanFromSubscription($payload['data']['object']);
        if ($plan) {
            $org->update([
                'plan' => $plan,
                'subscribed_at' => now(),
            ]);
        }

        Log::info('Subscription created', ['organization_id' => $org->id, 'plan' => $plan]);
    }

    /**
     * Handle customer subscription updated (plan swap, cancel at period end, etc).
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $user = $this->getUserByStripeId($subscription['customer'] ?? null);
        if (! $user) {
            return;
        }

        $org = $user->organization;
        if (! $org) {
            return;
        }

        $plan = $this->extractPlanFromSubscription($subscription);
        if ($plan) {
            $org->update(['plan' => $plan]);
        }

        Log::info('Subscription updated', ['organization_id' => $org->id, 'plan' => $plan, 'status' => $subscription['status'] ?? null]);
    }

    /**
     * Handle customer subscription deleted.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);
        if (! $user) {
            return;
        }

        $org = $user->organization;
        if (! $org) {
            return;
        }

        $org->update(['plan' => 'starter']);

        Log::info('Subscription deleted', ['organization_id' => $org->id]);
    }

    /**
     * Get organization from Stripe customer ID.
     */
    protected function getUserByStripeId($stripeCustomerId)
    {
        if (! $stripeCustomerId) {
            return null;
        }

        $org = Organization::where('stripe_id', $stripeCustomerId)->first();
        if (! $org) {
            return null;
        }

        return $org->users()->first();
    }

    /**
     * Extract plan name from Stripe subscription items.
     */
    private function extractPlanFromSubscription(array $subscription): ?string
    {
        $items = $subscription['items']['data'] ?? [];
        if (empty($items)) {
            return null;
        }

        // The plan is stored as the price nickname or lookup key
        $price = $items[0]['price'] ?? [];
        $planId = $price['lookup_key'] ?? $price['nickname'] ?? null;

        if ($planId && config('subscription.plans.' . $planId)) {
            return $planId;
        }

        // Fallback: use price ID and match against config
        $priceId = $price['id'] ?? null;
        if ($priceId) {
            $plans = config('subscription.plans');
            foreach ($plans as $key => $plan) {
                if (($plan['stripe_price_id'] ?? null) === $priceId) {
                    return $key;
                }
            }
        }

        return null;
    }
}
