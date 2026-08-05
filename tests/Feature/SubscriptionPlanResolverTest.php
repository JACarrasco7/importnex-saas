<?php

namespace Tests\Feature;

use App\Services\Billing\SubscriptionPlanResolver;
use Tests\TestCase;

class SubscriptionPlanResolverTest extends TestCase
{
    protected SubscriptionPlanResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(SubscriptionPlanResolver::class);
    }

    public function test_resolves_by_lookup_key(): void
    {
        $plan = $this->resolver->fromStripeSubscription([
            'id' => 'sub_test',
            'items' => [
                'data' => [
                    ['price' => ['lookup_key' => 'pro', 'id' => 'price_x']],
                ],
            ],
        ]);

        $this->assertSame('pro', $plan);
    }

    public function test_resolves_by_nickname_as_fallback(): void
    {
        $plan = $this->resolver->fromStripeSubscription([
            'id' => 'sub_test',
            'items' => [
                'data' => [
                    ['price' => ['lookup_key' => null, 'nickname' => 'enterprise', 'id' => 'price_y']],
                ],
            ],
        ]);

        $this->assertSame('enterprise', $plan);
    }

    public function test_resolves_by_price_id_when_set_in_config(): void
    {
        config(['subscription.plans.pro.stripe_price_id' => 'price_test_123']);

        $plan = $this->resolver->fromStripeSubscription([
            'id' => 'sub_test',
            'items' => [
                'data' => [
                    ['price' => ['id' => 'price_test_123']],
                ],
            ],
        ]);

        $this->assertSame('pro', $plan);
    }

    public function test_returns_null_when_subscription_has_no_items(): void
    {
        $plan = $this->resolver->fromStripeSubscription(['id' => 'sub_test']);
        $this->assertNull($plan);
    }

    public function test_returns_null_when_no_match(): void
    {
        $plan = $this->resolver->fromStripeSubscription([
            'id' => 'sub_test',
            'items' => [
                'data' => [
                    ['price' => ['id' => 'price_unknown']],
                ],
            ],
        ]);

        $this->assertNull($plan);
    }

    public function test_all_keys_returns_configured_plans(): void
    {
        $keys = $this->resolver->allKeys();

        $this->assertContains('starter', $keys);
        $this->assertContains('pro', $keys);
        $this->assertContains('enterprise', $keys);
    }

    public function test_is_valid_plan_key(): void
    {
        $this->assertTrue($this->resolver->isValidPlanKey('pro'));
        $this->assertFalse($this->resolver->isValidPlanKey('nonexistent'));
    }

    public function test_config_returns_plan_array_or_null(): void
    {
        $this->assertIsArray($this->resolver->config('pro'));
        $this->assertSame(100, $this->resolver->config('pro')['cars_limit']);
        $this->assertNull($this->resolver->config('nonexistent'));
    }
}
