<?php

namespace Tests\Feature;

use App\Events\CarImported;
use App\Listeners\NotifyImportWebhook;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotifyImportWebhookTest extends TestCase
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

    public function test_evento_car_imported_se_despacha_al_importar(): void
    {
        Event::fake([CarImported::class]);

        $response = $this->postJson(
            '/api/import-valuation',
            $this->validPayload(),
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201);
        Event::assertDispatched(CarImported::class, function ($e) use ($response) {
            $data = $response->json();

            return $e->car->id === $data['car_id'] && $e->flujo === 'A';
        });
    }

    public function test_listener_no_pide_post_si_webhook_url_vacia(): void
    {
        config(['services.importnex_chat.webhook_url' => null]);

        Http::fake();
        $car = $this->makeCar();

        (new NotifyImportWebhook)->handle(new CarImported(
            car: $car,
            flujo: 'A',
            carUrl: url("/cars/{$car->id}"),
        ));

        Http::assertNothingSent();
    }

    public function test_listener_envia_post_con_payload_y_headers_cuando_url_configurada(): void
    {
        config([
            'services.importnex_chat.webhook_url' => 'http://127.0.0.1:8765/',
            'services.importnex_chat.webhook_secret' => 'super-secret',
            'services.importnex_chat.webhook_timeout' => 2,
        ]);

        Http::fake();
        $car = $this->makeCar();

        (new NotifyImportWebhook)->handle(new CarImported(
            car: $car,
            flujo: 'B',
            carUrl: url("/cars/{$car->id}"),
            schemaVersion: '1',
        ));

        Http::assertSent(function ($request) use ($car) {
            return $request->url() === 'http://127.0.0.1:8765/'
                && $request->method() === 'POST'
                && $request['event'] === 'car.imported'
                && $request['flujo'] === 'B'
                && $request['car_id'] === $car->id
                && $request->hasHeader('X-Webhook-Signature')
                && $request->hasHeader('Content-Type');
        });
    }

    public function test_listener_no_lanza_excepcion_si_webhook_cae(): void
    {
        config(['services.importnex_chat.webhook_url' => 'http://127.0.0.1:1/']);

        Http::fake(function () {
            throw new \RuntimeException('connection refused');
        });

        $car = $this->makeCar();

        // No debe lanzar: el listener come la excepcion.
        (new NotifyImportWebhook)->handle(new CarImported(
            car: $car,
            flujo: 'A',
            carUrl: url("/cars/{$car->id}"),
        ));

        $this->assertTrue(true); // si llego aqui sin throw, OK
    }

    public function test_dry_run_no_despacha_evento(): void
    {
        Event::fake([CarImported::class]);

        $response = $this->postJson(
            '/api/import-valuation?dry=1',
            $this->validPayload(),
            ['X-Import-Token' => $this->token]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'dry_run_ok');

        Event::assertNotDispatched(CarImported::class);
    }

    // ---- helpers ----

    private function makeCar(): Car
    {
        $org = Organization::first();

        return Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);
    }

    private function validPayload(): array
    {
        return [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'generado_el' => '2026-09-12T12:00:00+02:00',
                'origen' => 'chat-ia',
                'coche_id' => 'opel-astra-2019-webhook',
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
