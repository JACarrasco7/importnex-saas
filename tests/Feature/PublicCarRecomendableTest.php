<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A1+A2+A3 auditoría 09-sep-2026: ficha pública del cliente.
 *
 *  A1: los argumentos públicos NO contienen cifras del mercado alemán,
 *      "banda alemana", "cuartil", "escasez", ni "vendibilidad".
 *  A2: la fuente de los argumentos públicos es ficha-cliente.json /
 *      PUBLICIDAD_ARGUMENTO; NUNCA el bloque interno A_FAVOR del esqueleto.
 *  A3: si el veredicto interno desaconseja la unidad y no hay
 *      ficha-cliente.json, devolvemos car-unavailable.
 */
class PublicCarRecomendableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // RefreshDatabase limpia la BD pero NO el storage local; sin esto,
        // los archivos creados por un test contaminan al siguiente.
        Storage::disk('local')->deleteDirectory('cars');
    }

    public function test_dossier_devuelve_car_unavailable_si_recomendacion_es_no_comprar_y_no_hay_ficha(): void
    {
        [, $link] = $this->cocheCon([
            'recommendation' => 'No importar a este precio',
            'valuation' => 'Mala compra para cliente',
        ]);

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('public.car-unavailable');
    }

    public function test_dossier_se_muestra_si_hay_ficha_cliente_json(): void
    {
        [$car] = $this->cocheCon([
            'recommendation' => 'No importar a este precio',
        ]);

        Storage::disk('local')->put(
            "cars/{$car->id}/contenido/json/ficha-cliente.json",
            json_encode([
                'ficha' => [
                    'argumentos' => ['Tracción total 4Motion utilizable todo el año'],
                    'spec' => [['etiqueta' => 'Año', 'valor' => '2023']],
                ],
            ])
        );
        $link = $car->publicLinks()->latest()->first();

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('public.car-dossier');
    }

    public function test_dossier_se_muestra_si_recomendacion_es_comprar(): void
    {
        [, $link] = $this->cocheCon([
            'recommendation' => 'Comprar — buen precio y buen equipamiento',
        ]);

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('public.car-dossier');
    }

    public function test_dossier_no_contiene_fugas_del_informe_arteon(): void
    {
        [$car, $link] = $this->cocheCon([
            'recommendation' => 'Comprar',
            'valuation' => 'Buena compra',
        ]);

        // Simulamos el ZIP del Arteon: A_FAVOR con cifras del mercado alemán
        // (esto NO debe filtrarse al cliente) y ficha-cliente.json con argumentos
        // escritos para el cliente (esto SÍ).
        Storage::disk('local')->put(
            "cars/{$car->id}/contenido/ficha-publicitaria.txt",
            "[A_FAVOR]\n".
            "Precio en cuartil bajo de la banda alemana (31.929 € vs 31.000-34.685 €)\n".
            "Mercado español muy estrecho (solo 7 unidades) → alta escasez\n".
            "Vendibilidad alta (84/100)\n".
            "[PUBLICIDAD_ARGUMENTO]\n".
            "Tracción total 4Motion utilizable todo el año\n".
            "[PRECIO]\n31929 €\n"
        );
        Storage::disk('local')->put(
            "cars/{$car->id}/contenido/json/ficha-cliente.json",
            json_encode([
                'ficha' => [
                    'argumentos' => [
                        'Tracción total 4Motion utilizable todo el año',
                    ],
                    'spec' => [['etiqueta' => 'Año', 'valor' => '2023']],
                ],
            ])
        );

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $body = $response->getContent();
        // NO debe aparecer ningún dato interno del A_FAVOR.
        $this->assertStringNotContainsString('31.929', $body);
        $this->assertStringNotContainsString('cuartil bajo', $body);
        $this->assertStringNotContainsString('banda alemana', $body);
        $this->assertStringNotContainsString('Vendibilidad', $body);
        $this->assertStringNotContainsString('84/100', $body);
        $this->assertStringNotContainsString('muy estrecho', $body);
        $this->assertStringNotContainsString('alta escasez', $body);
        // SÍ debe aparecer el argumento del cliente.
        $this->assertStringContainsString('4Motion', $body);
    }

    public function test_dossier_cae_a_car_unavailable_si_link_esta_revocado(): void
    {
        [, $link] = $this->cocheCon(['recommendation' => 'Comprar']);
        $link->forceFill(['revoked_at' => now()])->save();

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('public.car-unavailable');
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array{0: Car, 1: CarPublicLink}
     */
    private function cocheCon(array $attrs): array
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(array_merge([
            'organization_id' => $org->id,
            'brand' => 'Volkswagen',
            'model' => 'Arteon',
            'recommendation' => 'Comprar',
            'valuation' => 'Buena compra',
        ], $attrs));
        $link = CarPublicLink::generateFor($car);

        return [$car, $link];
    }
}
