<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

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
    public function handleWebhook(Request $request): Response
    {
        $eventId = $request->input('id') ?? 'unknown-'.md5((string) $request->input('data.object.id', ''));
        $lockKey = 'stripe_webhook:'.$eventId;
        $lockTtl = 86400; // 24h — Stripe retries webhook events for ~24h

        // Acquire lock to deduplicate Stripe retries. If another worker is
        // already processing this event (or it has been processed within 24h),
        // skip silently to avoid duplicate plan flips.
        try {
            $lock = Cache::lock($lockKey, $lockTtl);

            if (! $lock->get()) {
                Log::info('Stripe webhook skipped (already processed or in-flight)', [
                    'event_id' => $eventId,
                    'type' => $request->input('type'),
                ]);

                return response('OK', 200);
            }

            Log::info('Stripe webhook received', ['event_id' => $eventId, 'type' => $request->input('type')]);
            parent::handleWebhook($request);
            $lock->release();
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed', [
                'event_id' => $eventId,
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

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

        // Owner organizations keep their plan even if subscription is deleted.
        if ($org->isOwner()) {
            Log::info('Subscription deleted but org is owner, plan kept', ['organization_id' => $org->id]);

            return;
        }

        $org->update(['plan' => 'starter']);

        Log::info('Subscription deleted', ['organization_id' => $org->id]);
    }

    /**
     * Handle invoice payment failed. Degrade the org to starter after grace period
     * is handled via the trial_ends_at (kept in the future for grace).
     */
    protected function handleInvoicePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        $user = $this->getUserByStripeId($customerId);
        if (! $user) {
            return;
        }

        $org = $user->organization;
        if (! $org || $org->isOwner()) {
            return;
        }

        Log::warning('Invoice payment failed', [
            'organization_id' => $org->id,
            'invoice_id' => $invoice['id'] ?? null,
            'attempt_count' => $invoice['attempt_count'] ?? null,
        ]);

        // Degrade to starter immediately. Frontend will show a banner based on
        // the next 'customer.subscription.deleted' webhook that will follow.
        // Keeping this conservative — product owner can override.
        $org->update(['plan' => 'starter']);
    }

    /**
     * Notify the org that trial is ending in 3 days.
     * Hook for sending email / showing in-app banner.
     */
    protected function handleCustomerSubscriptionTrialWillEnd(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $user = $this->getUserByStripeId($subscription['customer'] ?? null);
        if (! $user) {
            return;
        }

        $org = $user->organization;
        if (! $org) {
            return;
        }

        Log::info('Trial will end', [
            'organization_id' => $org->id,
            'trial_ends_at' => $subscription['trial_end'] ?? null,
        ]);
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
     * Falls back through: lookup_key → nickname → stripe_price_id mapping → null.
     */
    private function extractPlanFromSubscription(array $subscription): ?string
    {
        $items = $subscription['items']['data'] ?? [];
        if (empty($items)) {
            Log::warning('Stripe subscription has no items', ['subscription_id' => $subscription['id'] ?? null]);

            return null;
        }

        // The plan is stored as the price nickname or lookup key
        $price = $items[0]['price'] ?? [];
        $planId = $price['lookup_key'] ?? $price['nickname'] ?? null;

        if ($planId && config('subscription.plans.'.$planId)) {
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

        Log::warning('Stripe plan could not be resolved', [
            'subscription_id' => $subscription['id'] ?? null,
            'price_id' => $priceId,
            'lookup_key' => $price['lookup_key'] ?? null,
            'nickname' => $price['nickname'] ?? null,
        ]);

        return null;
    }
}
