<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
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
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Located',
        ]);

        $this->actingAs($user);

        // No API key configured -> should fail and NOT touch anything
        $response = $this->post(route('cars.verify-sync', $car));
        $response->assertRedirect();

        $fresh = $car->fresh();
        $this->assertEquals('Located', $fresh->status);
        $this->assertNull($fresh->ai_analysis_json);
        $this->assertNull($fresh->ai_verified_at);
    }

    public function test_verify_sync_with_mocked_ai_success(): void
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
                        'verdict' => 'Buy',
                        'market_avg' => 15000,
                        'estimated_saving' => 1200,
                        'pros' => ['Clean'],
                        'cons' => ['High mileage'],
                    ]),
                ]],
            ], 200),
        ]);

        $org = Organization::factory()->withAi('anthropic', 'claude-3-5-sonnet-latest', 'sk-test-fake')->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Located',
            'traffic_light' => 'neutral',
            'valuation' => null,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.verify-sync', $car));
        $response->assertRedirect(route('cars.show', $car->id));

        $car->refresh();
        // La verificación NO modifica nada existente: solo guarda el análisis.
        $this->assertEquals('Located', $car->status);
        $this->assertEquals('neutral', $car->traffic_light);
        $this->assertNull($car->valuation);
        // El análisis completo queda en ai_analysis_json + timestamp.
        $this->assertNotNull($car->ai_verified_at);
        $this->assertIsArray($car->ai_analysis_json);
        $this->assertEquals('green', $car->ai_analysis_json['traffic_light']);
        $this->assertEquals('Good deal', $car->ai_analysis_json['valuation']);
        $this->assertEquals('Buy', $car->ai_analysis_json['verdict']);
        $this->assertEquals(15000, $car->ai_analysis_json['market_avg']);
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
            'ai_analysis_json' => [
                'valuation' => 'AI says it is overpriced',
                'verdict' => 'Discard',
                'market_avg' => 15000,
            ],
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.verify.apply', $car), [
            'fields' => ['valuation', 'verdict', 'market_avg'],
        ]);
        $response->assertRedirect(route('cars.show', $car->id));

        $fresh = $car->fresh();
        $this->assertEquals('Valuing', $fresh->status);
        $this->assertEquals('AI says it is overpriced', $fresh->valuation);
        $this->assertEquals('Discard', $fresh->verdict);
        $this->assertEquals(15000.0, (float) $fresh->market_avg);
        $this->assertNull($fresh->ai_analysis_json, 'ai_analysis_json should be cleared after applying');
    }

    public function test_apply_skips_unselected_fields_and_unfilled_proposals(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Pending review',
            'description' => 'Already set by user',
            'ai_analysis_json' => [
                'valuation' => 'AI valuation text',
                'description' => 'AI description text',
                'verdict' => null,
            ],
        ]);

        $this->actingAs($user);

        // Apply ONLY valuation. Leave description (already set) untouched.
        $response = $this->post(route('cars.verify.apply', $car), [
            'fields' => ['valuation', 'verdict'], // verdict has null proposal => skipped
        ]);
        $response->assertRedirect();

        $fresh = $car->fresh();
        $this->assertEquals('AI valuation text', $fresh->valuation);
        $this->assertEquals('Already set by user', $fresh->description, 'description must be left intact');
        $this->assertNull($fresh->verdict);
    }
}
