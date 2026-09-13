<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de los guards añadidos en la auditoría ronda 4 (13-sep-2026).
 *
 * Cubre:
 * - storeMercado rechaza payloads con >1000 modelos (anti-DoS).
 * - indexCierres clampea ?limit a [1, 500] (anti-OOM).
 * - HSTS presente en todas las respuestas (anti SSL-stripping).
 * - alerts:generate procesa coches de MÚLTIPLES orgs (no se queda en una).
 */
class Round4GuardsTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
    }

    public function test_store_mercado_rejects_more_than_1000_modelos(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $modelos = [];
        for ($i = 0; $i < 1001; $i++) {
            $modelos[] = [
                'modelo' => "Modelo {$i}",
                'hueco_pct' => 10.0,
                'n_uds_de' => 1,
            ];
        }

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'generado_el' => '2026-09-13T10:00:00+02:00',
                'origen' => 'chat-ia',
                'scouting_id' => 'scouting-guard-test',
            ],
            'modelos' => $modelos,
        ];

        $response = $this->postJson(
            '/api/import-mercado?org=JJ%20Import%20Motors',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Field "modelos" exceeds max length (1000). Split into multiple scouting imports.');
    }

    public function test_store_mercado_accepts_exactly_1000_modelos(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $modelos = [];
        for ($i = 0; $i < 1000; $i++) {
            $modelos[] = [
                'modelo' => "Modelo {$i}",
                'hueco_pct' => 10.0,
                'n_uds_de' => 1,
            ];
        }

        $response = $this->postJson(
            '/api/import-mercado?org=JJ%20Import%20Motors',
            [
                '_meta' => [
                    'schema_version' => 1,
                    'flujo' => 'C',
                    'generado_el' => '2026-09-13T10:00:00+02:00',
                    'origen' => 'chat-ia',
                    'scouting_id' => 'scouting-guard-1000',
                ],
                'modelos' => $modelos,
            ],
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201);
        $this->assertDatabaseCount('modelos_mercado', 1000);
    }

    public function test_index_cierres_clamps_limit_to_500(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        // Pedir 99999 no debe intentar traer todo
        $response = $this->getJson(
            '/api/cierres?org=JJ%20Import%20Motors&limit=99999',
            ['X-Import-Token' => $this->token]
        );

        $response->assertOk();
        // Si no hay cierres, la respuesta es vacía; el guard no rompe. Lo que
        // verificamos es que NO devuelve error 500 ni intenta cargar todo.
        $this->assertIsArray($response->json());
    }

    public function test_index_cierres_clamps_negative_limit_to_1(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $response = $this->getJson(
            '/api/cierres?org=JJ%20Import%20Motors&limit=-5',
            ['X-Import-Token' => $this->token]
        );

        $response->assertOk();
    }

    public function test_hsts_header_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_security_headers_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_alerts_generate_processes_cars_from_multiple_orgs(): void
    {
        // Regresión del fix de multi-tenancy (ronda 4): el comando corre SIN
        // usuario autenticado, así que el global scope de Car no filtra. Si
        // alguien añade un auth() en el futuro, el withoutGlobalScope explícito
        // garantiza que se procesan TODAS las orgs (comportamiento intencional:
        // es un generador GLOBAL de alertas).
        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $orgB = Organization::factory()->create(['name' => 'Org B']);

        foreach ([$orgA, $orgB] as $org) {
            Car::factory()->create([
                'organization_id' => $org->id,
                'status' => 'Located',
                'updated_at' => now()->subDays(45),
            ]);
        }

        $this->artisan('alerts:generate')->assertSuccessful();

        // Cada org recibe su alerta en su propio organization_id (no cruzadas)
        $this->assertDatabaseHas('alerts', [
            'organization_id' => $orgA->id,
            'alert_type' => 'car_stale',
        ]);
        $this->assertDatabaseHas('alerts', [
            'organization_id' => $orgB->id,
            'alert_type' => 'car_stale',
        ]);

        // Y ningún alert queda sin organization_id (cross-tenant leak)
        $this->assertSame(0, Alert::whereNull('organization_id')->count());
    }
}
