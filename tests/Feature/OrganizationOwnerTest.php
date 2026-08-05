<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_owner_returns_true_when_flag_is_set(): void
    {
        $org = Organization::create([
            'name' => 'Vitalicio Test',
            'plan' => 'pro',
            'is_owner' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertTrue($org->isOwner());
        // Owner bypass: limits are NEVER reached regardless of usage.
        $this->assertFalse($org->limitReached('cars'));
        $this->assertFalse($org->limitReached('clients'));
        $this->assertFalse($org->limitReached('contacts'));
    }

    public function test_is_owner_returns_false_for_regular_org(): void
    {
        $org = Organization::create([
            'name' => 'Regular Test',
            'plan' => 'starter',
            'is_owner' => false,
        ]);

        $this->assertFalse($org->isOwner());
    }

    public function test_owner_has_unlimited_usage(): void
    {
        $org = Organization::create([
            'name' => 'Vitalicio Test',
            'plan' => 'pro',
            'is_owner' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $usage = $org->usageFor('cars');

        $this->assertTrue($usage['unlimited']);
        $this->assertNull($usage['limit']);
        $this->assertNull($usage['available']);
        $this->assertFalse($usage['reached']);
    }

    public function test_non_owner_is_limited_by_plan(): void
    {
        $org = Organization::create([
            'name' => 'Regular Test',
            'plan' => 'starter',
            'is_owner' => false,
        ]);

        $usage = $org->usageFor('cars');

        $this->assertFalse($usage['unlimited']);
        $this->assertSame(10, $usage['limit']);
    }

    public function test_owner_can_be_created_via_factory_or_helper(): void
    {
        $org = Organization::create([
            'name' => 'JJ Import Motors',
            'plan' => 'pro',
            'is_owner' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertSame('JJ Import Motors', $org->fresh()->name);
        $this->assertTrue($org->fresh()->isOwner());
    }
}
