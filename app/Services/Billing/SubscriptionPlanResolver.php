<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Log;

/**
 * Resolves a plan key (e.g. 'pro', 'enterprise') from various inputs:
 *   - Stripe subscription payload (webhook)
 *   - Stripe price ID
 *   - Stripe price lookup_key / nickname
 *   - Direct config key fallback
 *
 * Single source of truth so Organization, StripeWebhookController and tests
 * share the same lookup semantics.
 */
class SubscriptionPlanResolver
{
    /**
     * Resolve plan key from a Stripe subscription payload.
     *
     * @param  array<string,mixed>  $subscription
     */
    public function fromStripeSubscription(array $subscription): ?string
    {
        $items = $subscription['items']['data'] ?? [];
        if (empty($items)) {
            Log::warning('Stripe subscription has no items', [
                'subscription_id' => $subscription['id'] ?? null,
            ]);

            return null;
        }

        $price = $items[0]['price'] ?? [];

        return $this->fromStripePrice($price);
    }

    /**
     * Resolve plan key from a Stripe price object.
     *
     * @param  array<string,mixed>  $price
     */
    public function fromStripePrice(array $price): ?string
    {
        // 1. lookup_key (preferred — set on Stripe dashboard)
        $planId = $price['lookup_key'] ?? $price['nickname'] ?? null;
        if ($planId && $this->isValidPlanKey($planId)) {
            return $planId;
        }

        // 2. Match by price.id against config
        $priceId = $price['id'] ?? null;
        if ($priceId) {
            foreach (config('subscription.plans') as $key => $plan) {
                if (($plan['stripe_price_id'] ?? null) === $priceId) {
                    return $key;
                }
            }
        }

        Log::warning('Stripe plan could not be resolved', [
            'price_id' => $priceId,
            'lookup_key' => $price['lookup_key'] ?? null,
            'nickname' => $price['nickname'] ?? null,
        ]);

        return null;
    }

    /**
     * Returns all configured plan keys (used by UI to render plan cards dynamically).
     *
     * @return array<int,string>
     */
    public function allKeys(): array
    {
        return array_keys(config('subscription.plans'));
    }

    /**
     * Validate that a string is a configured plan key.
     */
    public function isValidPlanKey(string $key): bool
    {
        return array_key_exists($key, config('subscription.plans'));
    }

    /**
     * Resolve plan config or null if not configured.
     *
     * @return array<string,mixed>|null
     */
    public function config(string $key): ?array
    {
        return config("subscription.plans.{$key}");
    }
}
