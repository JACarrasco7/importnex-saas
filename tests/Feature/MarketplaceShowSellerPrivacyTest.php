<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Services\ValuationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ficha publica tras la revision del 20-nov-2026 (Mazda 3 MPS, coches 80/81):
 *  - Sin ciudad ni nombre del vendedor de origen (ni en la ficha ni en
 *    "Otros coches similares", que ademas enseña el total aproximado y no
 *    el precio del anuncio).
 *  - Gestoria, placas y seguro del traslado (`other_fees`) suman al total.
 *  - La comparativa con el mercado español solo aparece si favorece al
 *    cliente (total <= el mas barato de España).
 *  - Vendedor particular: solo un booleano para avisar de que no hay
 *    garantia del vendedor y de que puede haber ITP.
 *  - El importador publica la primera matriculacion MM/AAAA.
 */
class MarketplaceShowSellerPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create(['is_public' => true, 'slug' => Str::random(6)]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCar(array $attributes = []): Car
    {
        return Car::factory()->create(array_merge([
            'organization_id' => $this->organization->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'brand' => 'Mazda',
            'model' => '3',
            'purchase_price' => 9900,
            'transport' => 900,
            'itv_fee' => 150,
            'coc_fee' => 0,
            'dgt_fees' => 100,
            'other_fees' => 0,
            'boe_confirmed' => false,
            'manual_tax_base' => 0,
            'city' => 'Reutlingen (72766)',
            'seller' => 'Naim Autohandel (Profesional)',
            'market_min' => null,
            'market_avg' => null,
            'market_max' => null,
        ], $attributes));
    }

    public function test_seller_city_and_name_are_not_exposed(): void
    {
        $car = $this->makeCar();

        $response = $this->get('/marketplace/'.$car->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('car.city')
            ->missing('car.seller')
            ->where('car.seller_is_private', false));
        $this->assertStringNotContainsString('Reutlingen', $response->getContent());
        $this->assertStringNotContainsString('Naim Autohandel', $response->getContent());
    }

    public function test_related_cars_show_estimated_total_without_city(): void
    {
        $car = $this->makeCar();
        $this->makeCar(['purchase_price' => 7499, 'city' => 'Bad Mergentheim (97980)', 'other_fees' => 430]);
        $this->makeCar(['purchase_price' => 8500, 'city' => 'Kassel (34117)']);

        $response = $this->get('/marketplace/'.$car->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('relatedCars', 2)
            ->missing('relatedCars.0.city')
            ->where('relatedCars', fn ($related) => collect($related)->contains(
                fn (array $relatedCar) => (float) $relatedCar['estimated_total'] === 7499.0 + 900 + 150 + 100 + 430 + 1000
            )));
        $this->assertStringNotContainsString('Bad Mergentheim', $response->getContent());
    }

    public function test_other_fees_are_added_to_the_client_total(): void
    {
        $car = $this->makeCar(['other_fees' => 430]);

        $response = $this->get('/marketplace/'.$car->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('costBreakdown.paperwork', 430)
            ->where('costBreakdown.total', 9900 + 900 + 250 + 430 + 1000));
    }

    public function test_market_comparison_is_shown_when_total_is_below_the_cheapest_in_spain(): void
    {
        // Total 9.900 + 900 + 250 + 1.000 = 12.050 <= 12.500
        $car = $this->makeCar(['market_min' => 12500, 'market_avg' => 13000, 'market_max' => 13900]);

        $this->get('/marketplace/'.$car->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('showMarketComparison', true));
    }

    public function test_market_comparison_is_hidden_when_total_exceeds_the_cheapest_in_spain(): void
    {
        // Total 12.050 > 11.990: el bloque "Cómo está de precio" no se pinta.
        $car = $this->makeCar(['market_min' => 11990, 'market_avg' => 12945, 'market_max' => 13900]);

        $this->get('/marketplace/'.$car->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('showMarketComparison', false));
    }

    public function test_market_comparison_is_hidden_without_market_data(): void
    {
        $car = $this->makeCar();

        $this->get('/marketplace/'.$car->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('showMarketComparison', false));
    }

    public function test_private_seller_is_flagged_without_exposing_the_name(): void
    {
        $car = $this->makeCar(['seller' => 'Vendedor particular (Particular)']);

        $this->get('/marketplace/'.$car->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('car.seller_is_private', true)
                ->missing('car.seller'));
    }

    public function test_german_private_seller_label_is_detected(): void
    {
        $this->assertTrue((new Car(['seller' => 'Max Mustermann (Privat)']))->isFromPrivateSeller());
        $this->assertFalse((new Car(['seller' => 'Autohaus Beispiel GmbH (Profesional)']))->isFromPrivateSeller());
        $this->assertFalse((new Car(['seller' => null]))->isFromPrivateSeller());
    }

    public function test_importer_uses_first_registration_and_splits_fees(): void
    {
        $car = Car::factory()->make(['organization_id' => $this->organization->id]);

        $car = app(ValuationImporter::class)->apply($car, $this->importPayload([
            'anio' => 2011,
            'primera_matriculacion' => '12/2011',
        ]));
        $car->refresh();

        $this->assertSame('12/2011', $car->year);
        $this->assertSame(1000.0, (float) $car->professional_fees);
        $this->assertSame(430.0, (float) $car->other_fees);
    }

    public function test_importer_falls_back_to_year_when_first_registration_is_malformed(): void
    {
        $car = Car::factory()->make(['organization_id' => $this->organization->id]);

        $car = app(ValuationImporter::class)->apply($car, $this->importPayload([
            'anio' => 2011,
            'primera_matriculacion' => 'diciembre 2011',
        ]));

        $this->assertSame('01/2011', $car->fresh()->year);
    }

    /**
     * @param  array<string, mixed>  $vehiculo
     * @return array<string, mixed>
     */
    private function importPayload(array $vehiculo): array
    {
        return [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => array_merge([
                'marca' => 'Mazda', 'modelo' => '3', 'version' => 'MPS', 'km' => 176000,
                'combustible' => 'Gasolina', 'cambio' => 'Manual', 'potencia_cv' => 260, 'co2_gkm' => 224,
            ], $vehiculo),
            'anuncio' => ['url' => 'https://example.com/mazda-'.Str::random(6), 'precio_publicado' => 7499, 'vendedor_tipo' => 'Particular'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'media', 'razonamiento' => 'Test', 'fecha' => '2026-11-20'],
            'costes' => [
                'precio_coche' => 7499, 'pvp_nuevo' => 26200, 'transporte' => 900,
                'itv_matriculacion' => 150, 'tasa_dgt' => 99.77,
                'gestoria' => 200, 'otros' => 230, 'honorarios' => 1000,
            ],
            'mercado' => ['precio_medio' => 12945, 'comparables' => []],
        ];
    }
}
