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
     * Plan swap, cancel at period end, past_due… Cashier sincroniza primero la
     * fila local de `subscriptions` (estado, fechas); luego se ajusta el plan.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload) ?? $this->successMethod();

        $subscription = $payload['data']['object'];
        $org = $this->organizationFor($subscription['customer'] ?? null);
        if (! $org) {
            return $response;
        }

        $plan = $this->extractPlanFromSubscription($subscription);
        if ($plan) {
            $org->update(['plan' => $plan]);
        }

        Log::info('Subscription updated', ['organization_id' => $org->id, 'plan' => $plan, 'status' => $subscription['status'] ?? null]);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload) ?? $this->successMethod();

        $org = $this->organizationFor($payload['data']['object']['customer'] ?? null);
        if (! $org) {
            return $response;
        }

        // La organización propietaria conserva su plan aunque se borre la suscripción.
        if ($org->isOwner()) {
            Log::info('Subscription deleted but org is owner, plan kept', ['organization_id' => $org->id]);

            return $response;
        }

        $org->update(['plan' => 'starter']);

        Log::info('Subscription deleted', ['organization_id' => $org->id]);

        return $response;
    }

    /**
     * Pago fallido: marca payment_failed_at sin degradar. El comando
     * `subscription:downgrade-expired-grace` degrada tras SUBSCRIPTION_GRACE_DAYS.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $invoice = $payload['data']['object'] ?? [];
        $org = $this->organizationFor($invoice['customer'] ?? null);

        if ($org && ! $org->isOwner()) {
            Log::warning('Invoice payment failed', [
                'organization_id' => $org->id,
                'invoice_id' => $invoice['id'] ?? null,
                'attempt_count' => $invoice['attempt_count'] ?? null,
            ]);

            $org->update(['payment_failed_at' => $org->payment_failed_at ?? now()]);
            Cache::forget("billing.dashboard.{$org->id}.1");
        }

        return $this->successMethod();
    }

    /**
     * Suscripción creada (también desde el panel de Stripe): Cashier crea la
     * fila local; aquí se fija el plan y se limpia el aviso de pago fallido.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload) ?? $this->successMethod();

        $org = $this->organizationFor($payload['data']['object']['customer'] ?? null);
        if (! $org) {
            return $response;
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

        return $response;
    }

    protected function handleCustomerSubscriptionTrialWillEnd(array $payload): Response
    {
        $subscription = $payload['data']['object'] ?? [];
        $org = $this->organizationFor($subscription['customer'] ?? null);

        if ($org) {
            Log::info('Trial will end', [
                'organization_id' => $org->id,
                'trial_ends_at' => $subscription['trial_end'] ?? null,
            ]);
        }

        return $this->successMethod();
    }

    /** Suscripciones creadas desde el panel de Stripe usan el mismo tipo que la app. */
    protected function newSubscriptionType(array $payload): string
    {
        return Organization::SUBSCRIPTION_TYPE;
    }

    /**
     * El cliente de Stripe es la organización (Cashier::useCustomerModel en
     * AppServiceProvider), no el usuario.
     */
    private function organizationFor(?string $stripeCustomerId): ?Organization
    {
        if (! $stripeCustomerId) {
            return null;
        }

        return Organization::where('stripe_id', $stripeCustomerId)->first();
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
