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

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
        $this->org = Organization::factory()->create(['name' => 'JJ Import Motors']);
    }

    private function validPayload(): array
    {
        return [
            '_meta' => [
                'schema_version' => 1,
                'generado_el' => '2026-09-12T12:00:00+02:00',
                'origen' => 'chat-ia',
                'coche_id' => 'opel-astra-2019-dryrun',
                'client_id' => null,
            ],
            'vehiculo' => [
                'marca' => 'Opel', 'modelo' => 'Astra', 'version' => '1.4 Turbo',
                'anio' => 2019, 'km' => 50000, 'combustible' => 'gasolina',
                'cambio' => 'manual', 'cv' => 125, 'puertas' => 5,
            ],
            'anuncio' => [
                'portal' => 'mobile.de', 'url' => 'https://example.com/x',
                'vendedor' => 'Test Dealer', 'precio_publicado' => 12000,
                'moneda' => 'EUR', 'fotos' => [],
            ],
            'investigacion' => [
                'mecanica' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'electrico' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'carroceria' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'interior' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'mantenimiento' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'fiabilidad' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'recalls' => ['hallazgo' => 'Ninguno', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'homologacion' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
                'seguro' => ['hallazgo' => 'OK', 'fuente' => 'x', 'valoracion' => 'favorable', 'fecha' => '2026-08-01'],
            ],
            'balance' => [
                'pros' => [['text' => 'Buen estado', 'weight' => 1]],
                'cons' => [['text' => 'Color raro', 'weight' => 1]],
            ],
            'veredicto' => [
                'decision' => 'buy', 'confianza' => 'high',
                'razonamiento' => 'OK', 'cambiaria' => 'precio',
            ],
            'costes' => [
                'coste_compra' => 12000, 'transporte' => 900,
                'ausfuhr' => 114, 'itv_import' => 115, 'iedmt' => 0,
                'gestoria' => 200, 'total_puesto' => 13329,
            ],
            'mercado' => [
                'comparables' => [], 'posicion' => 'media',
                'recomendacion_precio' => 12500,
            ],
        ];
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
}
