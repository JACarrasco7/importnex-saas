<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportValuationDryRunTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
        Organization::factory()->create(['name' => 'JJ Import Motors']);
    }

    public function test_dry_run_query_param_no_escribe_en_bd(): void
    {
        $carsBefore = Car::count();

        $response = $this->postJson(
            '/api/import-valuation?dry=1',
            $this->validPayload(),
            ['X-Import-Token' => $this->token]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'dry_run_ok')
            ->assertJsonPath('would_create', true)
            ->assertJsonPath('car_id', null);

        $this->assertSame($carsBefore, Car::count(), 'dry_run no debe crear coches');
    }

    public function test_dry_run_header_tambien_funciona(): void
    {
        $carsBefore = Car::count();

        $response = $this->postJson(
            '/api/import-valuation',
            $this->validPayload(),
            ['X-Import-Token' => $this->token, 'X-Dry-Run' => '1']
        );

        $response->assertOk()
            ->assertJsonPath('status', 'dry_run_ok');

        $this->assertSame($carsBefore, Car::count());
    }

    public function test_sin_dry_run_si_escribe_en_bd(): void
    {
        $carsBefore = Car::count();

        $response = $this->postJson(
            '/api/import-valuation',
            $this->validPayload(),
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJsonPath('status', 'created');

        $this->assertSame($carsBefore + 1, Car::count());
    }

    public function test_dry_run_devuelve_422_si_json_invalido(): void
    {
        $payload = $this->validPayload();
        unset($payload['vehiculo']);

        $response = $this->postJson(
            '/api/import-valuation?dry=1',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422);
        $this->assertSame(0, Car::count());
    }

    private function validPayload(): array
    {
        return [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'generado_el' => '2026-09-12T12:00:00+02:00',
                'origen' => 'chat-ia',
                'coche_id' => 'opel-astra-2019-dryrun',
            ],
            'vehiculo' => [
                'marca' => 'Opel', 'modelo' => 'Astra', 'version' => '1.4 Turbo',
                'anio' => 2019, 'km' => 50000, 'combustible' => 'Gasolina',
                'cambio' => 'Manual', 'potencia_cv' => 125, 'co2_gkm' => 120,
            ],
            'anuncio' => [
                'portal' => 'mobile.de', 'url' => 'https://example.com/x',
                'pais_origen' => 'Alemania', 'precio_publicado' => 12000,
                'moneda' => 'EUR', 'vendedor_tipo' => 'Profesional',
            ],
            'investigacion' => [
                'problemas_comunes' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'recalls' => ['hallazgo' => 'Ninguno', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'precio_mercado' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'fiabilidad' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'homologacion' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'etiqueta_ambiental' => ['hallazgo' => 'C', 'fuente' => 'DGT', 'valoracion' => 'neutro'],
                'seguro' => ['hallazgo' => '600 EUR/an', 'fuente' => 'x', 'valoracion' => 'neutro'],
                'piezas' => ['hallazgo' => 'Alta', 'fuente' => 'x', 'valoracion' => 'favorable'],
                'otros' => ['hallazgo' => '', 'fuente' => '', 'valoracion' => ''],
            ],
            'balance' => [
                'a_favor' => [['texto' => 'Buen estado', 'peso' => 'alto']],
                'en_contra' => [['texto' => 'Color raro', 'peso' => 'medio']],
            ],
            'veredicto' => [
                'recomendacion' => 'Comprar', 'confianza' => 'alta',
                'razonamiento' => 'Precio ajustado a mercado.', 'precio_objetivo' => 11500,
                'fecha' => '12/09/2026',
            ],
            'costes' => [
                'precio_coche' => 12000, 'pvp_nuevo' => 24000,
                'transporte' => 900, 'itv_matriculacion' => 115,
                'tasa_dgt' => 20.61, 'iedmt_estimado' => 0,
                'gestoria' => 200, 'otros' => 114,
                'coste_total' => 13349.61, 'honorarios' => 1500,
                'precio_cliente' => 14849.61,
            ],
            'mercado' => [
                'comparables' => [], 'precio_medio' => 12500,
                'precio_min' => 11500, 'precio_max' => 13500,
                'ahorro_estimado' => 500, 'semaforo' => 'green',
            ],
            'avisos' => [],
        ];
    }
}
