<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_index_loads_for_authenticated_user(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Billing/Index')
            ->has('invoices')
            ->has('stats')
            ->has('hasStripeId')
        );
    }

    public function test_billing_show_returns_404_for_unknown_invoice(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('billing.show', 'in_nonexistent'));
        $response->assertNotFound();
    }

    public function test_billing_download_returns_404_when_no_stripe_id(): void
    {
        $org = Organization::factory()->create(['stripe_id' => null]);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('billing.download', 'in_xxx'));
        $response->assertNotFound();
    }

    public function test_billing_portal_redirect_returns_404_when_no_stripe_id(): void
    {
        $org = Organization::factory()->create(['stripe_id' => null]);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('billing.portal'));
        $response->assertNotFound();
    }

    public function test_billing_index_includes_no_stripe_warning_when_no_id(): void
    {
        $org = Organization::factory()->create(['stripe_id' => null]);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('billing.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('hasStripeId', false)
            ->where('stripePortalUrl', null)
        );
    }
}
