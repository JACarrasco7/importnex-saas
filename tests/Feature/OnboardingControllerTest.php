<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create([
            'name' => 'Test Org',
            'plan' => 'starter',
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'owner',
        ]);
    }

    public function test_index_creates_progress_and_renders_wizard(): void
    {
        $response = $this->actingAs($this->user)->get('/onboarding');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Onboarding/Wizard')
            ->has('progress')
            ->has('stepData')
            ->where('stepData.title', 'Bienvenido a JJ Import Motors')
        );

        $this->assertDatabaseHas('user_onboarding_progress', [
            'user_id' => $this->user->id,
            'organization_id' => $this->org->id,
            'current_step' => 1,
        ]);
    }

    public function test_index_redirects_when_completed(): void
    {
        \App\Models\UserOnboardingProgress::create([
            'user_id' => $this->user->id,
            'organization_id' => $this->org->id,
            'step_organization_created' => true,
            'step_first_vehicle_added' => true,
            'step_team_invited' => true,
            'step_plan_selected' => true,
            'current_step' => 5,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/onboarding');
        $response->assertRedirect('/dashboard');
    }

    public function test_skip_marks_progress_as_skipped(): void
    {
        \App\Models\UserOnboardingProgress::create([
            'user_id' => $this->user->id,
            'organization_id' => $this->org->id,
            'current_step' => 1,
        ]);

        $response = $this->actingAs($this->user)->get('/onboarding/skip');

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('user_onboarding_progress', [
            'user_id' => $this->user->id,
            'skipped_at' => now()->toDateString(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->get('/onboarding');
        $response->assertRedirect('/login');
    }
}