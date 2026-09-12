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

    public function test_evento_car_imported_se_despacha_al_importar(): void
    {
        Event::fake([CarImported::class]);

        $response = $this->postImport();

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

        $payload = $this->validPayload();
        $this->postJson('/api/import-valuation?dry=1', $payload, ['X-Import-Token' => $this->token()])
            ->assertOk()
            ->assertJsonPath('status', 'dry_run_ok');

        Event::assertNotDispatched(CarImported::class);
    }

    // ---- helpers ----

    protected function token(): string
    {
        return $this->_token ??= 'test-token-'.bin2hex(random_bytes(16));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.importnex_chat.token' => $this->token()]);
        Organization::factory()->create(['name' => 'JJ Import Motors']);
    }

    private function postImport()
    {
        return $this->postJson(
            '/api/import-valuation',
            $this->validPayload(),
            ['X-Import-Token' => $this->token()]
        );
    }

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
                'generado_el' => '2026-09-12T12:00:00+02:00',
                'origen' => 'chat-ia',
                'coche_id' => 'opel-astra-2019-webhook',
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

    private string $_token = '';
}
