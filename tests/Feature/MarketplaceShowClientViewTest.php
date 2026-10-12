<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests de la ficha publica despues del refactor 12-oct-2026:
 *  - La valoracion (verdict_reasoning) NO se muestra al cliente.
 *  - Los contras (cons) tampoco.
 *  - Notas, import progress, 9 research aspects tampoco.
 *  - El costBreakdown llega como prop Inertia con los honorarios correctos.
 */
class MarketplaceShowClientViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeCar(array $attrs = []): Car
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => Str::random(6)]);

        return Car::factory()->create(array_merge([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 18000,
            'marketplace_views' => 0,
        ], $attrs));
    }

    public function test_verdict_reasoning_is_no_t_exposed_in_inertia(): void
    {
        $car = $this->makeCar([
            'verdict_reasoning' => 'INTERNAL: el coche tiene 3 propietarios y golpe en trasera.',
        ]);

        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('car.verdict_reasoning'));
    }

    public function test_cons_are_no_t_exposed_in_inertia(): void
    {
        $car = $this->makeCar([
            'pros' => [['text' => 'Buen estado', 'weight' => 'high']],
            'cons' => [['text' => 'Pintura desgastada', 'weight' => 'medium']],
        ]);

        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('car.cons'));
    }

    public function test_verdict_and_traffic_light_no_t_exposed_in_inertia(): void
    {
        // El verdict (texto admin: "Comprar con confianza baja...") y el
        // traffic_light (badge "neutral") son señales internas — no deben
        // llegar al cliente (decision 12-oct-2026 tras feedback del usuario).
        $car = $this->makeCar([
            'verdict' => 'Comprar con confianza baja (cobertura parcial 2/7)',
            'verdict_confidence' => 'low',
            'verdict_at' => now(),
            'traffic_light' => 'amber',
        ]);

        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('car.verdict')
            ->missing('car.verdict_confidence')
            ->missing('car.verdict_at')
            ->missing('car.traffic_light'));
    }

    public function test_notes_and_red_flags_no_t_exposed_in_inertia(): void
    {
        $car = $this->makeCar([
            'notes' => 'INTERNAL: revisar correa distribucion',
            'red_flags' => ['rayon puerta'],
        ]);

        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('car.notes')
            ->missing('car.red_flags')
            ->missing('derived.milestones_progress'));
    }

    public function test_cost_breakdown_exposed_in_inertia_with_correct_honorarios(): void
    {
        $car = $this->makeCar([
            'purchase_price' => 25000,
            'transport' => 1500,
            'itv_fee' => 300,
            'coc_fee' => 200,
            'dgt_fees' => 100,
        ]);
        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('costBreakdown.car_price', 25000)
            ->where('costBreakdown.transport', 1500)
            ->where('costBreakdown.processing', 600)
            ->where('costBreakdown.honorarios', 1000)
            ->where('costBreakdown.has_data', true));
    }

    public function test_cost_breakdown_uses_premium_honorarios_for_big_car(): void
    {
        $car = $this->makeCar([
            'purchase_price' => 32000,
            'transport' => 1000,
        ]);
        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('costBreakdown.honorarios', 1500));
    }

    public function test_dossier_like_spec_items_excludes_internal_fields(): void
    {
        // El dossier car-dossier.blade.php solo muestra: Marca, Modelo, Version,
        // Ano, Kilometros, Combustible, Cambio, Traccion. NO VIN, no Puertas,
        // no CO2, no color. Decision 12-oct-2026: solo lo bueno / relevante.
        $car = $this->makeCar([
            'vin' => 'WBA12345678901234',
            'mileage' => 50000,
            'co2' => 130,
            'color' => 'Negro',
        ]);
        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();

        // El HTML no debe contener el VIN.
        $body = $response->getContent();
        $this->assertStringNotContainsString(
            'WBA12345678901234',
            $body,
            'La ficha publica NO debe exponer el VIN del coche (decision dossier-like)'
        );
    }
}
