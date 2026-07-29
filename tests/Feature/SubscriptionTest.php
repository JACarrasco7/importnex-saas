<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Car;
use App\Models\Client;
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
        $this->assertEquals(10, $plan['car_limit']);
    }

    public function test_pro_limits_cars_to_100(): void
    {
        $plan = config('subscription.plans.pro');
        $this->assertEquals(100, $plan['car_limit']);
    }

    public function test_enterprise_limits_cars_to_1000(): void
    {
        $plan = config('subscription.plans.enterprise');
        $this->assertEquals(1000, $plan['car_limit']);
    }

    public function test_starter_limits_clients_to_50(): void
    {
        $plan = config('subscription.plans.starter');
        $this->assertEquals(50, $plan['client_limit']);
    }

    public function test_pro_limits_clients_to_500(): void
    {
        $plan = config('subscription.plans.pro');
        $this->assertEquals(500, $plan['client_limit']);
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
}
