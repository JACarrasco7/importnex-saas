<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\CarPublicLink;
use App\Models\Organization;
use App\Support\CostBreakdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Las dos fichas publicas del coche (ficha del marketplace y dossier
 * /c/{token}) tras la revision del 20-nov-2026:
 *  - Mismo total aproximado en las dos (antes 10.035 € frente a 10.364 €).
 *  - Previsualizacion del enlace (WhatsApp) con el coche, el total y la foto.
 *  - La ficha del marketplace enseña la valoracion de la unidad y lo que se
 *    comprueba antes de reservar, como el dossier.
 *  - Nada interno en el HTML: ni la org completa, ni `derived`, ni la
 *    descripcion del anuncio de origen, ni frases internas en los textos.
 */
class PublicFichasCoherenciaTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::disk('local')->deleteDirectory('cars');
        Cache::flush();
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
            'version' => 'MPS 2.3 DISI Turbo 260 cv',
            'year' => '12/2011',
            'mileage' => 176000,
            'pais_origen' => 'Alemania',
            'purchase_price' => 7499,
            'transport' => 900,
            'itv_fee' => 150,
            'coc_fee' => 0,
            'dgt_fees' => 100,
            'other_fees' => 430,
            'co2' => 0,
            'boe_confirmed' => false,
            'manual_tax_base' => 0,
            'market_min' => 11990,
            'market_avg' => 12945,
            'market_max' => 13900,
            'description' => 'Naim Autohandel, Tel. 0171 1234567',
            'equipment' => ['Bose', 'Xenon', 'Navegador', 'Keyless'],
            'pros' => [
                'Cadena de distribución ya cambiada, con facturas de Mazda',
                'Unos 1.600 € por debajo del MPS más barato de España puesto en Huelva con honorarios',
            ],
        ], $attributes));
    }

    private function writeZipContent(Car $car): void
    {
        Storage::disk('local')->put(
            "cars/{$car->id}/contenido/ficha-publicitaria.txt",
            "[VALORACION]\nMPS de serie con la cadena ya cambiada y facturas de Mazda. Pendientes: ver facturas, VIN y «sin accidentes» por escrito.\n".
            "[POR_QUE]\nLa cadena es el punto que más preocupa en un MPS y aquí ya está cambiada.\n"
        );
        Storage::disk('local')->put(
            "cars/{$car->id}/contenido/json/ficha-cliente.json",
            json_encode(['ficha' => [
                'argumentos' => ['**Cadena ya cambiada:** con facturas de Mazda'],
                'equipamiento' => ['Bose', 'Xenon'],
                'verificado' => ['2 propietarios'],
                'pendiente_comprobar' => ['VIN y campañas de Mazda'],
            ]])
        );
    }

    public function test_marketplace_html_has_share_preview_for_whatsapp(): void
    {
        $car = $this->makeCar();
        CarPhoto::create([
            'car_id' => $car->id,
            'organization_id' => $this->organization->id,
            'url' => 'cars/'.$car->id.'/photos/001.jpg',
            'photo_type' => 'exterior',
            'sort_order' => 1,
        ]);
        $total = CostBreakdown::forCar($car->fresh(), (float) $car->fresh()->calculateIEDMT())['total'];

        $html = $this->get('/marketplace/'.$car->id)->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Mazda 3 MPS 2.3 DISI Turbo 260 cv · JJ Import Motors">', $html);
        $this->assertStringContainsString('~ '.number_format($total, 0, ',', '.').' € puesto en Huelva (aprox.)', $html);
        $this->assertStringContainsString('cars/'.$car->id.'/photos/001.jpg', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="es_ES">', $html);
        $this->assertStringNotContainsString('og:description" content="Plataforma profesional', $html);
    }

    public function test_shared_profile_link_has_share_preview_too(): void
    {
        $car = $this->makeCar();
        $link = CarPublicLink::generateFor($car);

        $html = $this->get('/p/'.$link->token)->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Mazda 3 MPS 2.3 DISI Turbo 260 cv · JJ Import Motors">', $html);
        $this->assertStringContainsString('/p/'.$link->token, $html);
    }

    public function test_marketplace_index_has_jj_share_preview(): void
    {
        $this->makeCar();

        $html = $this->get('/marketplace')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Coches localizados · JJ Import Motors">', $html);
        $this->assertStringContainsString('/img/og-image.jpg', $html);
        $this->assertFileExists(public_path('img/og-image.jpg'));
    }

    public function test_site_wide_meta_and_schema_have_no_placeholder_or_saas_copy(): void
    {
        $html = $this->get(route('pricing'))->getContent();

        $this->assertStringNotContainsString('verificación AI', $html);
        $this->assertStringNotContainsString('600 000 000', $html);
        $this->assertStringNotContainsString('AutoDealer', $html);
        $this->assertStringContainsString('+34 675 70 14 39', $html);
    }

    public function test_listing_payload_has_estimated_total_and_honest_saving(): void
    {
        $favourable = $this->makeCar();
        $notFavourable = $this->makeCar(['market_min' => 9000, 'market_avg' => 9500, 'market_max' => 10000, 'estimated_saving' => 1600]);
        $total = CostBreakdown::forCar($favourable->fresh(), (float) $favourable->fresh()->calculateIEDMT())['total'];

        $this->get('/marketplace/compare?ids='.$favourable->id.','.$notFavourable->id)->assertInertia(function ($page) use ($favourable, $notFavourable, $total) {
            $cars = collect($page->toArray()['props']['cars'])->keyBy('id');
            $this->assertEqualsWithDelta($total, $cars[$favourable->id]['estimated_total'], 0.01);
            $this->assertEqualsWithDelta(round(11990 - $total), $cars[$favourable->id]['estimated_saving'], 0.01);
            $this->assertNull($cars[$notFavourable->id]['estimated_saving']);
            $this->assertArrayNotHasKey('organization_id', $cars[$favourable->id]['photos'][0] ?? []);
        });
    }

    public function test_marketplace_payload_has_nothing_internal(): void
    {
        $car = $this->makeCar();

        $response = $this->get('/marketplace/'.$car->id);

        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->missing('derived')
            ->missing('car.description')
            ->missing('car.professional_fees')
            ->missing('car.new_price')
            ->missing('car.deposit')
            ->missing('car.organization.plan')
            ->missing('car.organization.stripe_id')
            ->missing('car.organization.ai_provider')
            ->where('car.organization.name', $this->organization->name));
        $this->assertStringNotContainsString('0171 1234567', $response->getContent());
    }

    public function test_marketplace_pros_drop_internal_savings_claims(): void
    {
        $car = $this->makeCar();

        $this->get('/marketplace/'.$car->id)->assertInertia(fn ($page) => $page
            ->has('car.pros', 1)
            ->where('car.pros.0', 'Cadena de distribución ya cambiada, con facturas de Mazda'));
    }

    public function test_marketplace_shows_valuation_and_checks_from_the_zip(): void
    {
        $car = $this->makeCar();
        $this->writeZipContent($car);

        $this->get('/marketplace/'.$car->id)->assertInertia(fn ($page) => $page
            ->where('valuation', [
                'MPS de serie con la cadena ya cambiada y facturas de Mazda.',
                'La cadena es el punto que más preocupa en un MPS y aquí ya está cambiada.',
            ])
            ->where('checks.verificado', ['2 propietarios'])
            ->where('checks.pendiente', ['VIN y campañas de Mazda']));
    }

    public function test_marketplace_without_zip_has_empty_valuation(): void
    {
        $car = $this->makeCar();

        $this->get('/marketplace/'.$car->id)->assertInertia(fn ($page) => $page
            ->where('valuation', [])
            ->where('checks.verificado', [])
            ->where('checks.pendiente', []));
    }

    public function test_dossier_total_matches_the_marketplace_total(): void
    {
        $car = $this->makeCar();
        $this->writeZipContent($car);
        $link = CarPublicLink::generateFor($car);
        $total = CostBreakdown::forCar($car->fresh(), (float) $car->fresh()->calculateIEDMT())['total'];

        $response = $this->get('/c/'.$link->token);

        $response->assertOk();
        $precio = $response->viewData('precioCliente');
        $this->assertEqualsWithDelta($total, $precio['total_estimado'], 0.01);
        $this->assertEqualsWithDelta($total, array_sum($precio['desglose']), 0.01);
        $this->assertArrayHasKey('Gestoría, placas y seguro del traslado', $precio['desglose']);
        $this->assertArrayHasKey('Honorarios JJ Import Motors', $precio['desglose']);
        $this->assertLessThanOrEqual($total, $precio['total_min']);
        $this->assertGreaterThanOrEqual($total, $precio['total_max']);
        $response->assertSee('~ '.number_format($total, 0, ',', '.').' €', false);
    }

    public function test_dossier_hides_market_block_when_not_favourable(): void
    {
        $car = $this->makeCar(['market_min' => 9000, 'market_avg' => 9500, 'market_max' => 10000]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get('/c/'.$link->token);

        $response->assertOk();
        $response->assertDontSee('Cómo está de precio');
    }

    public function test_dossier_shows_market_block_when_favourable(): void
    {
        $car = $this->makeCar();
        $link = CarPublicLink::generateFor($car);

        $this->get('/c/'.$link->token)->assertOk()->assertSee('Cómo está de precio');
    }

    public function test_dossier_text_is_clean_and_complete(): void
    {
        $car = $this->makeCar(['marketplace_views' => 3]);
        $this->writeZipContent($car);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get('/c/'.$link->token);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('Pendientes: ver facturas', $html);
        $this->assertStringContainsString('<strong>Cadena ya cambiada:</strong>', $html);
        $this->assertStringNotContainsString('**Cadena', $html);
        $this->assertStringContainsString('Equipamiento y extras (4)', $html);
        $this->assertStringNotContainsString('visitas', $html);
        $this->assertStringContainsString('puesto en Huelva (aprox.)', $html);
        $this->assertStringContainsString(urlencode('Mazda 3 MPS 2.3 DISI Turbo 260 cv'), $html);
    }

    public function test_dossier_warns_private_seller_and_fixed_fees_have_no_tilde(): void
    {
        $car = $this->makeCar(['seller' => 'Privatanbieter']);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get('/c/'.$link->token);

        $response->assertOk();
        $response->assertSee('Lo vende un particular', false);
        $response->assertSee('<span class="i">1.000 €</span>', false);
        $response->assertSee('<title>Mazda 3 MPS 2.3 DISI Turbo 260 cv · JJ Import Motors</title>', false);
    }

    public function test_seller_rating_pros_are_not_published(): void
    {
        $car = $this->makeCar(['pros' => [
            'Un solo propietario y de serie',
            'Concesionario con buena valoración (4,0/5, 100 % «como se describe»)',
        ]]);

        $this->get('/marketplace/'.$car->id)->assertInertia(fn ($page) => $page
            ->where('car.pros', ['Un solo propietario y de serie']));
    }
}
