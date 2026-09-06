<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\Billing\SubscriptionPlanResolver;
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
        $lock = Cache::lock($lockKey, $lockTtl);

        try {
            if (! $lock->get()) {
                Log::info('Stripe webhook skipped (already processed or in-flight)', [
                    'event_id' => $eventId,
                    'type' => $request->input('type'),
                ]);

                return response('OK', 200);
            }

            Log::info('Stripe webhook received', ['event_id' => $eventId, 'type' => $request->input('type')]);
            parent::handleWebhook($request);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed', [
                'event_id' => $eventId,
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
            ]);
            // Liberar el lock para que Stripe pueda reintentar el evento en la
            // siguiente rafaga (la doc de Cashier dice release() solo se llama
            // en happy path; sin esto un error 500 deja el evento bloqueado 24h).
            $lock->release();
            throw $e;
        }

        $lock->release();

        return response('OK', 200);
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
     * Handle invoice payment failed. Marca payment_failed_at sin degradar:
     * el downgrade real lo hace el comando programado
     * `subscription:downgrade-expire` tras SUBSCRIPTION_GRACE_DAYS días.
     * Esto da margen al cliente para corregir el método de pago (p.ej.
     * tarjeta caducada) sin perder sus features inmediatamente.
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

        $org->update([
            // Solo marcar el timestamp. El comando artisan
            // `subscription:downgrade-expire` degrada a starter tras
            // SUBSCRIPTION_GRACE_DAYS si llega el caso.
            'payment_failed_at' => $org->payment_failed_at ?? now(),
        ]);
        Cache::forget("billing.dashboard.{$org->id}.1");
    }

    /**
     * Clear payment_failed_at when a new subscription is created.
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
                'payment_failed_at' => null,
            ]);
            Cache::forget("billing.dashboard.{$org->id}.1");
        }

        Log::info('Subscription created', ['organization_id' => $org->id, 'plan' => $plan]);
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
     * Delegates to SubscriptionPlanResolver so the logic lives in one place.
     */
    private function extractPlanFromSubscription(array $subscription): ?string
    {
        return app(SubscriptionPlanResolver::class)
            ->fromStripeSubscription($subscription);
    }
}
