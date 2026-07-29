<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use App\Services\CarVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CarVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_show_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('cars.verify.show', $car));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Verify')->where('car.id', $car->id));
    }

    public function test_verify_sync_fails_gracefully_without_api_key(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        // No API key configured -> should fail and revert status
        $response = $this->post(route('cars.verify-sync', $car));
        $response->assertRedirect();

        $this->assertEquals('Located', $car->fresh()->status);
    }

    public function test_verify_sync_with_mocked_claude_success(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'traffic_light' => 'green',
                        'valuation' => 'Good deal',
                        'recommendation' => 'Buy',
                        'red_flags' => [],
                        'tips' => ['Inspect'],
                    ]),
                ]],
            ], 200),
        ]);

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        config(['services.anthropic.api_key' => 'sk-test-fake']);

        $response = $this->post(route('cars.verify-sync', $car));
        $response->assertRedirect(route('cars.show', $car->id));

        $car->refresh();
        $this->assertEquals('Pending review', $car->status);
        $this->assertEquals('green', $car->traffic_light);
        $this->assertEquals('Good deal', $car->valuation);
    }

    public function test_discard_reverts_car_status(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Pending review',
            'valuation' => 'Test valuation',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.verify.discard', $car));
        $response->assertRedirect(route('cars.show', $car->id));

        $car->refresh();
        $this->assertEquals('Located', $car->status);
        $this->assertNull($car->valuation);
    }

    public function test_apply_moves_car_to_valuing(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Pending review',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.verify.apply', $car));
        $response->assertRedirect(route('cars.show', $car->id));

        $this->assertEquals('Valuing', $car->fresh()->status);
    }
}
