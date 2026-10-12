<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MarketplaceCompareTest extends TestCase
{
    use RefreshDatabase;

    private function publicCar(Organization $org, array $overrides = []): Car
    {
        return Car::factory()->create(array_merge([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 15000,
        ], $overrides));
    }

    public function test_compare_renders_with_requested_public_cars(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $a = $this->publicCar($org);
        $b = $this->publicCar($org);

        $response = $this->get("/marketplace/compare?ids={$a->id},{$b->id}");

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/MarketplaceCompare')
            ->has('cars', 2)
            ->where('requestedIds', [$a->id, $b->id]));
    }

    /**
     * Criterio de exclusión (25-sep-2026): manda el toggle `is_marketplace`;
     * solo excluyen hechos objetivos — organización no pública y coche que ya
     * no está en venta (`Delivered` vendido / `Discarded` descartado).
     *
     * El VEREDICTO y los estados intermedios (`Reserved`, `Processing`…) NO
     * excluyen: el operador decide con el toggle.
     */
    public function test_compare_excludes_non_public_cars(): void
    {
        $publicOrg = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $privateOrg = Organization::factory()->create(['is_public' => false]);

        $visible = $this->publicCar($publicOrg);
        $notPublicOrg = $this->publicCar($privateOrg);
        $notMarketplace = $this->publicCar($publicOrg, ['is_marketplace' => false]);
        $sold = $this->publicCar($publicOrg, ['status' => 'Delivered']);
        $discarded = $this->publicCar($publicOrg, ['status' => 'Discarded']);

        $ids = implode(',', [
            $visible->id, $notPublicOrg->id, $notMarketplace->id, $sold->id, $discarded->id,
        ]);

        $response = $this->get("/marketplace/compare?ids={$ids}");

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('cars', 1)
            ->where('cars.0.id', $visible->id));
    }

    /**
     * Un veredicto negativo o un estado intermedio NO impiden publicar: antes
     * se exigía `verdict IN (Buy, Buy if price drops)` y sorprendía al
     * operador (marcaba el toggle y el coche no salía).
     */
    public function test_compare_incluye_estados_en_venta_y_cualquier_veredicto(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test2']);

        $reserved = $this->publicCar($org, ['status' => 'Reserved']);
        $discardVerdict = $this->publicCar($org, ['verdict' => 'Discard']);
        $doubtful = $this->publicCar($org, ['verdict' => 'Doubtful']);

        $ids = implode(',', [$reserved->id, $discardVerdict->id, $doubtful->id]);

        $response = $this->get("/marketplace/compare?ids={$ids}");

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/MarketplaceCompare')
            ->has('cars', 3));
    }

    public function test_compare_caps_at_four_cars(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $cars = collect(range(1, 6))->map(fn () => $this->publicCar($org));
        $ids = $cars->pluck('id')->implode(',');

        $response = $this->get("/marketplace/compare?ids={$ids}");

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('cars', 4)
            ->count('requestedIds', 4));
    }

    public function test_compare_ignores_invalid_ids(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $car = $this->publicCar($org);

        $response = $this->get("/marketplace/compare?ids=abc,,{$car->id},{$car->id},-5");

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('cars', 1)
            ->where('requestedIds.0', $car->id));
    }

    public function test_compare_without_ids_renders_empty_state(): void
    {
        $response = $this->get('/marketplace/compare');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/MarketplaceCompare')
            ->has('cars', 0)
            ->where('requestedIds', []));
    }
}
