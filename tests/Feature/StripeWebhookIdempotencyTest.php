<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Stripe webhook signature verification.
 *
 * NOTE: Full webhook testing requires Stripe CLI to generate signed payloads.
 * Here we just verify the route exists and is properly registered.
 */
class StripeWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_route_is_registered(): void
    {
        $routes = app('router')->getRoutes();
        $webhookRoute = null;
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'stripe/webhook')) {
                $webhookRoute = $route;
                break;
            }
        }

        $this->assertNotNull($webhookRoute, 'Stripe webhook route should be registered');
        $this->assertContains('POST', $webhookRoute->methods());
    }
}
