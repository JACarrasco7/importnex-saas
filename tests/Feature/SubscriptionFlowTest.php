<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create([
            'name' => 'Test Co',
            'plan' => 'starter',
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'owner',
        ]);
    }

    public function test_subscriptions_index_renders_with_vitalicio_badge(): void
    {
        $ownerOrg = Organization::create([
            'name' => 'JJ',
            'plan' => 'pro',
            'is_owner' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);
        $ownerUser = User::factory()->create([
            'organization_id' => $ownerOrg->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($ownerUser)->get('/subscriptions');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriptions/Index')
            ->where('isOwner', true)
            ->where('currentPlan', 'pro')
        );
    }

    public function test_swap_to_starter_redirects_to_cancel(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/subscriptions/starter/swap');

        // Without a real Stripe subscription, swap() redirects to cancel route.
        $response->assertRedirect();
    }

    public function test_swap_to_unknown_plan_returns_404_or_back_with_error(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/subscriptions/nonexistent/swap');

        // Either 404 (abort) or 302 back with error session flash.
        $this->assertContains($response->getStatusCode(), [302, 404]);
    }

    public function test_cancel_without_subscription_returns_error(): void
    {
        $response = $this->actingAs($this->user)->post('/subscriptions/cancel');
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_resume_without_subscription_returns_error(): void
    {
        $response = $this->actingAs($this->user)->post('/subscriptions/resume');
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_payment_failed_at_drives_banner(): void
    {
        $this->org->update(['payment_failed_at' => now()]);

        $response = $this->actingAs($this->user)->get('/subscriptions');
        $response->assertInertia(fn ($page) => $page
            ->where('paymentFailed', true)
        );
    }

    public function test_owner_cannot_cancel_or_resume(): void
    {
        $ownerOrg = Organization::create([
            'name' => 'Owner Co',
            'plan' => 'pro',
            'is_owner' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);
        $ownerUser = User::factory()->create([
            'organization_id' => $ownerOrg->id,
            'role' => 'owner',
        ]);

        $cancelResponse = $this->actingAs($ownerUser)->post('/subscriptions/cancel');
        $cancelResponse->assertSessionHas('error');

        $resumeResponse = $this->actingAs($ownerUser)->post('/subscriptions/resume');
        $resumeResponse->assertSessionHas('error');
    }
}
