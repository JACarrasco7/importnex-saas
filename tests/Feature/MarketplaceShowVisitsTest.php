<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\MarketplaceVisit;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests del contador de visitas por coche (cars.marketplace_views) y de
 * la ficha publica simplificada estilo dossier.
 */
class MarketplaceShowVisitsTest extends TestCase
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

    public function test_first_show_view_increments_per_car_counter(): void
    {
        $car = $this->makeCar();
        $this->get('/marketplace/'.$car->id)->assertOk();
        $this->assertSame(1, (int) $car->fresh()->marketplace_views);
    }

    public function test_per_car_views_exposed_in_inertia(): void
    {
        $car = $this->makeCar(['marketplace_views' => 42]);
        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->where('car.marketplace_views', 43)); // 42 base + 1 por esta vista
    }

    public function test_both_global_and_per_car_counters_increment_independently(): void
    {
        $car = $this->makeCar();

        // 1) Una visita a la home del marketplace: solo sube el global.
        $this->get('/marketplace')->assertOk();
        $this->assertSame(1, (int) MarketplaceVisit::current()->visits_count);
        $this->assertSame(0, (int) $car->fresh()->marketplace_views);

        // 2) Una visita a la ficha del coche: sube el per-car.
        $this->get('/marketplace/'.$car->id)->assertOk();
        $this->assertSame(1, (int) MarketplaceVisit::current()->visits_count);
        $this->assertSame(1, (int) $car->fresh()->marketplace_views);

        // 3) Otra visita a la ficha: sube el per-car pero NO el global.
        $this->get('/marketplace/'.$car->id)->assertOk();
        $this->assertSame(1, (int) MarketplaceVisit::current()->visits_count);
        $this->assertSame(2, (int) $car->fresh()->marketplace_views);
    }

    public function test_shared_profile_token_does_not_increment_per_car_counter(): void
    {
        // El link /p/{token} es una "vista compartible" y NO debe inflar
        // el contador de la ficha (asi lo dejo la decision del 17-sep-2026).
        $car = $this->makeCar();
        $link = CarPublicLink::generateFor($car);

        $this->get('/p/'.$link->token)->assertOk();
        $this->assertSame(0, (int) $car->fresh()->marketplace_views);
        $this->assertSame(1, (int) $link->fresh()->views_count);
    }

    public function test_dossier_style_sections_are_rendered(): void
    {
        // La ficha debe mostrar las secciones publicas (pros, mercado, breakdown)
        // pero NO la valoracion interna (decision 12-oct-2026).
        $car = $this->makeCar([
            'pros' => [['text' => 'Buen estado general', 'weight' => 'high']],
            'valuation' => 'INTERNAL: no se debe mostrar al cliente.',
            'market_min' => 15000,
            'market_max' => 22000,
            'market_avg' => 18500,
        ]);

        $response = $this->get('/marketplace/'.$car->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->has('car.pros', 1)
            ->missing('car.valuation')  // 12-oct-2026: la valoracion NO se envia
            ->has('car.market_min')
            ->has('car.market_max')
            ->has('costBreakdown'));
    }
}
