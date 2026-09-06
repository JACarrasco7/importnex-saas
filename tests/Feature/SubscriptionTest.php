<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_has_default_plan(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $this->assertEquals('starter', $org->plan);
    }

    public function test_starter_limits_cars_to_10(): void
    {
        $plan = config('subscription.plans.starter');
        $this->assertEquals(10, $plan['cars_limit']);
    }

    public function test_pro_limits_cars_to_100(): void
    {
        $plan = config('subscription.plans.pro');
        $this->assertEquals(100, $plan['cars_limit']);
    }

    public function test_enterprise_limits_cars_to_1000(): void
    {
        $plan = config('subscription.plans.enterprise');
        $this->assertEquals(1000, $plan['cars_limit']);
    }

    public function test_starter_limits_clients_to_50(): void
    {
        $plan = config('subscription.plans.starter');
        $this->assertEquals(50, $plan['clients_limit']);
    }

    public function test_pro_limits_clients_to_500(): void
    {
        $plan = config('subscription.plans.pro');
        $this->assertEquals(500, $plan['clients_limit']);
    }

    public function test_trial_is_14_days_by_default(): void
    {
        $trial = config('subscription.trial_days');
        $this->assertEquals(14, $trial);
    }

    public function test_organization_can_check_if_on_trial(): void
    {
        $org = Organization::factory()->create();
        $onTrial = $org->onTrial('main');
        $this->assertFalse($onTrial);
    }

    public function test_change_plan_updates_plan_field(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $org->update(['plan' => 'pro']);
        $this->assertEquals('pro', $org->fresh()->plan);
    }

    public function test_subscriptions_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get('/subscriptions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriptions/Index')
            ->has('plans')
            ->has('currentPlan')
        );
    }

    public function test_plan_detail_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get('/subscriptions/pro');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriptions/Show')
            ->where('plan', 'pro')
            ->has('planData')
        );
    }

    public function test_nonexistent_plan_returns_404(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get('/subscriptions/invalid');

        $response->assertStatus(404);
    }

    public function test_cars_are_filtered_by_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $otherOrg = Organization::factory()->create();
        $otherCar = Car::factory()->create(['organization_id' => $otherOrg->id]);
        $myCar = Car::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals(1, $org->cars()->count());
        $this->assertEquals(1, $otherOrg->cars()->count());
    }

    public function test_clients_are_filtered_by_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $otherOrg = Organization::factory()->create();
        $otherClient = Client::factory()->create(['organization_id' => $otherOrg->id]);
        $myClient = Client::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals(1, $org->clients()->count());
        $this->assertEquals(1, $otherOrg->clients()->count());
    }

    public function test_downgrade_command_only_affects_orgs_past_grace_period(): void
    {
        config(['subscription.payment_failed_grace_days' => 7]);

        $fresh = Organization::factory()->create(['plan' => 'pro']);
        $expired = Organization::factory()->create([
            'plan' => 'pro',
            'payment_failed_at' => now()->subDays(8),
        ]);
        $justFailed = Organization::factory()->create([
            'plan' => 'pro',
            'payment_failed_at' => now()->subDays(2),
        ]);

        $this->artisan('subscription:downgrade-expired-grace')->assertSuccessful();

        $this->assertSame('pro', $fresh->fresh()->plan, 'Sin payment_failed_at no se degrada');
        $this->assertSame('starter', $expired->fresh()->plan, 'Pasados 7 días se degrada');
        $this->assertSame('pro', $justFailed->fresh()->plan, 'Dentro del grace no se degrada');
    }

    public function test_downgrade_command_dry_run_does_not_modify(): void
    {
        config(['subscription.payment_failed_grace_days' => 7]);
        $org = Organization::factory()->create([
            'plan' => 'pro',
            'payment_failed_at' => now()->subDays(10),
        ]);

        $this->artisan('subscription:downgrade-expired-grace --dry-run')->assertSuccessful();
        $this->assertSame('pro', $org->fresh()->plan);
    }
}
