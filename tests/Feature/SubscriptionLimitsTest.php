<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SubscriptionLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_blocks_11th_car(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $this->assertTrue($org->limitReached('cars'));
        $this->assertEquals(0, $org->available('cars'));
    }

    public function test_starter_plan_blocks_51st_client(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        Client::factory()->count(50)->create(['organization_id' => $org->id]);

        $this->assertTrue($org->limitReached('clients'));
    }

    public function test_pro_plan_allows_100_cars(): void
    {
        $org = Organization::factory()->create(['plan' => 'pro']);
        Car::factory()->count(50)->create(['organization_id' => $org->id]);

        $this->assertFalse($org->limitReached('cars'));
        $this->assertEquals(50, $org->available('cars'));
    }

    public function test_enterprise_plan_has_no_practical_limit(): void
    {
        $org = Organization::factory()->create(['plan' => 'enterprise']);
        Car::factory()->count(100)->create(['organization_id' => $org->id]);

        $this->assertFalse($org->limitReached('cars'));
        $this->assertGreaterThan(800, $org->available('cars'));
    }

    public function test_has_active_subscription_returns_true_with_future_trial(): void
    {
        $org = Organization::factory()->create(['trial_ends_at' => now()->addDays(7)]);

        $this->assertTrue($org->hasActiveSubscription());
    }

    public function test_has_active_subscription_returns_false_after_trial_expires(): void
    {
        $org = Organization::factory()->create(['trial_ends_at' => now()->subDays(7)]);

        $this->assertFalse($org->hasActiveSubscription());
    }

    public function test_available_returns_zero_when_limit_reached(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $this->assertEquals(0, $org->available('cars'));
    }

    public function test_available_returns_max_0_never_negative(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        Car::factory()->count(15)->create(['organization_id' => $org->id]);

        $this->assertEquals(0, $org->available('cars'));
    }
}
