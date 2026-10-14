<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicMarketplaceController;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketplaceFilterCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_filter_options_are_cached(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Audi',
            'fuel' => 'Diesel',
            'color' => 'Black',
        ]);

        // Clear any existing cache
        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        // First request: cache miss, populates cache
        $this->get('/marketplace')->assertOk();
        $this->assertTrue(Cache::has(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS), 'Cache should be populated after first request');

        // Verify cache contains expected structure
        $cached = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);
        $this->assertArrayHasKey('brands', $cached);
        $this->assertArrayHasKey('fuels', $cached);
        $this->assertArrayHasKey('transmissions', $cached);
        $this->assertArrayHasKey('doors', $cached);
        $this->assertArrayHasKey('colors', $cached);
        $this->assertContains('Audi', $cached['brands']);
        $this->assertContains('Diesel', $cached['fuels']);
    }

    public function test_marketplace_cache_returns_same_filter_options(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'BMW',
        ]);

        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        // Trigger first request to populate cache
        $this->get('/marketplace')->assertOk();
        $cached = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        // Trigger second request (should use cache, data should be identical)
        $this->get('/marketplace')->assertOk();
        $cachedAfter = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        $this->assertSame($cached, $cachedAfter, 'Cache should return identical results');
        $this->assertContains('BMW', $cached['brands']);
    }

    public function test_cache_flushes_when_car_is_created_with_is_marketplace(): void
    {
        Cache::put(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS, ['brands' => [], 'fuels' => [], 'transmissions' => [], 'doors' => [], 'colors' => []], 60);

        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $this->assertFalse(
            Cache::has(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS),
            'Cache should be flushed when new marketplace car is created'
        );
    }

    public function test_cache_flushes_when_is_marketplace_toggled(): void
    {
        Cache::put(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS, ['stale' => true], 60);

        $org = Organization::factory()->create(['is_public' => true]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => false,
        ]);

        Cache::put(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS, ['fresh' => true], 60);

        $car->update(['is_marketplace' => true]);

        $this->assertFalse(
            Cache::has(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS),
            'Cache should flush when is_marketplace changes'
        );
    }

    public function test_filter_options_have_expected_structure(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Mercedes',
            'fuel' => 'Gasoline',
        ]);

        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);
        $this->get('/marketplace')->assertOk();
        $options = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('brands', $options);
        $this->assertArrayHasKey('fuels', $options);
        $this->assertArrayHasKey('transmissions', $options);
        $this->assertArrayHasKey('doors', $options);
        $this->assertArrayHasKey('colors', $options);
        $this->assertContains('Mercedes', $options['brands']);
        $this->assertContains('Gasoline', $options['fuels']);
    }

    public function test_filter_options_only_expose_canonical_transmissions(): void
    {
        // La BD guarda strings descriptivos ("Automatico DSG de 7 marchas con
        // levas") que no deben aparecer como opcion del filtro — el cliente
        // busca "Automatico", no el nombre comercial del cambio.
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'transmission' => 'Automatico DSG de 7 marchas con levas',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'transmission' => 'manual',
        ]);

        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);
        $this->get('/marketplace')->assertOk();
        $options = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        $this->assertContains('manual', $options['transmissions']);
        $this->assertNotContains('Automatico DSG de 7 marchas con levas', $options['transmissions']);
    }

    public function test_filter_counts_are_cached_with_brand_and_deal_totals(): void
    {
        // Los chips de marca y el boton "Muy buen precio" muestran cuantos
        // coches hay en cada uno. Sin estos contadores la UI parece vacia.
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->count(2)->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Seat',
            'estimated_saving' => 200, // por debajo del umbral: NO es oferta
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Kia',
            'estimated_saving' => 4000, // por encima del umbral: SI es oferta
        ]);

        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_COUNTS);
        $this->get('/marketplace')->assertOk();
        $counts = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_COUNTS);

        $this->assertIsArray($counts);
        $this->assertSame(2, $counts['brands']['Seat']);
        $this->assertSame(1, $counts['brands']['Kia']);
        $this->assertSame(1, $counts['deal'], 'Solo el coche con ahorro >= 1500 EUR cuenta como oferta');
        $this->assertSame(3, $counts['total']);
    }

    public function test_deal_filter_requires_meaningful_saving(): void
    {
        // Antes bastaba estimated_saving > 0 (un ahorro de 50 EUR colaba).
        $org = Organization::factory()->create(['is_public' => true]);
        $cheapDeal = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Audi',
            'estimated_saving' => 100,
        ]);
        $realDeal = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'BMW',
            'estimated_saving' => 3000,
        ]);

        $response = $this->get('/marketplace?deal=1')->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('cars.data', 1)
                ->where('cars.data.0.id', $realDeal->id))
        );

        $this->assertDatabaseHas('cars', ['id' => $cheapDeal->id]);
    }

    public function test_index_passes_filter_counts_to_the_page(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Volvo',
            'estimated_saving' => 2500,
        ]);

        $this->get('/marketplace')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MarketplaceIndex')
                ->where('filterCounts.brands.Volvo', 1)
                ->where('filterCounts.deal', 1)
                ->where('filterCounts.total', 1));
    }

    public function test_filter_options_normalize_colors_to_the_base_palette(): void
    {
        // La BD guarda el nombre comercial completo. Como opcion de filtro
        // el cliente necesita "Azul", no "Azul Lapislazuli metalizado".
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Azul Lapislázuli metalizado (Lapis-Blau)',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Azul Metálico',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Negro Deep Black nacarado',
        ]);

        Cache::forget(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);
        $this->get('/marketplace')->assertOk();
        $options = Cache::get(PublicMarketplaceController::CACHE_KEY_FILTER_OPTIONS);

        // Dos variantes de azul -> una sola opcion "Azul".
        $this->assertSame(['Azul', 'Negro'], $options['colors']->all());
    }

    public function test_color_filter_matches_all_variants_of_the_base_color(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        $blue1 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Azul Lapislázuli metalizado (Lapis-Blau)',
        ]);
        $blue2 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Azul Metálico',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'color' => 'Negro',
        ]);

        // sort=oldest para que el orden sea determinista (por defecto es
        // 'recent', created_at desc, y los factories crean blue2 despues).
        $response = $this->get('/marketplace?color=Azul&sort=oldest')->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('cars.data', 2)
                ->where('cars.data.0.id', $blue1->id)
                ->where('cars.data.1.id', $blue2->id))
        );
    }
}
