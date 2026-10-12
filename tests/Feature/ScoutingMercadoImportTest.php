<?php

namespace Tests\Feature;

use App\Models\ModeloMercado;
use App\Models\Organization;
use App\Models\ScoutingMercado;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoutingMercadoImportTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
    }

    public function test_store_mercado_creates_scouting_with_modelos(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'generado_el' => '2026-08-11T12:00:00+02:00',
                'origen' => 'chat-ia',
                'scouting_id' => 'scouting-test-2026-08-11',
                'preferencias_usuario' => 'Deportivos y premium, 15-40k€',
            ],
            'modelos_escaneados' => 3,
            'modelos_con_hueco' => 2,
            'modelos_sin_hueco' => 1,
            'resumen_ejecutivo' => '2 modelos con hueco claro, 1 descartado.',
            'modelos' => [
                [
                    'modelo' => 'VW Golf GTI Clubsport',
                    'segmento' => 'Nicho',
                    'hueco_pct' => 22.4,
                    'n_uds_de' => 12,
                    'mediana_es' => 34500.00,
                    'mediana_de' => 26800.00,
                    'vendibilidad_estimada' => 85,
                    'recomendacion_aprox' => '🟢 Medir ya',
                    'mejor_anuncio_url' => 'https://www.mobile.de/golf-gti',
                    'fuente_cobertura' => [
                        'coches_net' => 'OK',
                        'mobile_de' => 'OK',
                        'auto_uncle' => 'OK',
                    ],
                ],
                [
                    'modelo' => 'BMW M240i',
                    'segmento' => 'Nicho',
                    'hueco_pct' => 14.8,
                    'n_uds_de' => 6,
                    'vendibilidad_estimada' => 82,
                    'recomendacion_aprox' => '🟡 Justo',
                    'mejor_anuncio_url' => 'https://www.autoscout24.de/bmw-m240i',
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/import-mercado?org=JJ%20Import%20Motors',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'created',
                'scouting_id' => 'scouting-test-2026-08-11',
                'modelos_count' => 2,
            ]);

        $this->assertDatabaseHas('scouting_mercado', [
            'scouting_id' => 'scouting-test-2026-08-11',
            'flujo' => 'C',
            'modelos_escaneados' => 3,
            'modelos_con_hueco' => 2,
            'organization_id' => $org->id,
        ]);

        $this->assertDatabaseHas('modelos_mercado', [
            'modelo' => 'VW Golf GTI Clubsport',
            'segmento' => 'Nicho',
            'hueco_pct' => 22.4,
            'n_uds_de' => 12,
        ]);

        $this->assertDatabaseHas('modelos_mercado', [
            'modelo' => 'BMW M240i',
            'recomendacion_aprox' => '🟡 Justo',
        ]);
    }

    public function test_store_mercado_updates_existing_scouting(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);

        $scouting = ScoutingMercado::create([
            'scouting_id' => 'scouting-test-2026-08-11',
            'schema_version' => 1,
            'flujo' => 'C',
            'generado_el' => '2026-08-10 10:00:00',
            'origen' => 'chat-ia',
            'modelos_escaneados' => 2,
            'modelos_con_hueco' => 1,
            'modelos_sin_hueco' => 1,
            'organization_id' => $org->id,
        ]);

        $scouting->modelos()->create([
            'modelo' => 'Old Model',
            'segmento' => 'Nicho',
        ]);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'generado_el' => '2026-08-11T12:00:00+02:00',
                'scouting_id' => 'scouting-test-2026-08-11',
            ],
            'modelos_escaneados' => 3,
            'modelos_con_hueco' => 2,
            'modelos_sin_hueco' => 1,
            'modelos' => [
                [
                    'modelo' => 'New Model',
                    'segmento' => 'Premium',
                    'hueco_pct' => 18.5,
                    'n_uds_de' => 5,
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(200); // actualización → 200 (no 201)

        $scouting->refresh();
        $this->assertEquals(3, $scouting->modelos_escaneados);
        $this->assertEquals(2, $scouting->modelos_con_hueco);

        // Old modelo should be deleted
        $this->assertDatabaseMissing('modelos_mercado', [
            'modelo' => 'Old Model',
        ]);

        // New modelo should exist
        $this->assertDatabaseHas('modelos_mercado', [
            'modelo' => 'New Model',
            'hueco_pct' => 18.5,
        ]);
    }

    public function test_store_mercado_rejects_invalid_flujo(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A', // Wrong flujo (should be 'C')
                'scouting_id' => 'test',
            ],
            'modelos' => [],
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => "Invalid _meta.flujo. Expected 'C', got 'A'."]);
    }

    public function test_store_mercado_rejects_missing_scouting_id(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                // Missing scouting_id
            ],
            'modelos' => [],
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Missing _meta.scouting_id.']);
    }

    public function test_store_mercado_rejects_missing_modelos(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'scouting_id' => 'test',
            ],
            // Missing modelos block
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => "Missing required top-level block: 'modelos'"]);
    }

    public function test_store_mercado_rejects_invalid_token(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'flujo' => 'C',
                'scouting_id' => 'test',
            ],
            'modelos' => [],
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => 'wrong-token']
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing X-Import-Token.']);
    }

    public function test_store_mercado_returns_503_when_token_not_configured(): void
    {
        config(['services.importnex_chat.token' => null]);

        $payload = [
            '_meta' => [
                'flujo' => 'C',
                'scouting_id' => 'test',
            ],
            'modelos' => [],
        ];

        $response = $this->postJson(
            '/api/import-mercado',
            $payload,
            ['X-Import-Token' => 'any-token']
        );

        $response->assertStatus(503)
            ->assertJson(['error' => 'Import bridge not configured on this server.']);
    }

    public function test_scouting_mercado_model_has_correct_casts(): void
    {
        $scouting = new ScoutingMercado([
            'generado_el' => '2026-08-11 12:00:00',
            'schema_version' => '1',
            'modelos_escaneados' => '5',
        ]);

        $this->assertInstanceOf(Carbon::class, $scouting->generado_el);
        $this->assertIsInt($scouting->schema_version);
        $this->assertIsInt($scouting->modelos_escaneados);
    }

    public function test_modelo_mercado_model_has_correct_casts(): void
    {
        $modelo = new ModeloMercado([
            'hueco_pct' => '22.4',
            'n_uds_de' => '12',
            'mediana_es' => '34500.50',
            'fuente_cobertura' => ['mobile_de' => 'OK'],
        ]);

        // decimal:2 cast convierte a string con formato
        $this->assertIsString($modelo->hueco_pct);
        $this->assertEquals('22.40', $modelo->hueco_pct);

        $this->assertIsInt($modelo->n_uds_de);

        $this->assertIsString($modelo->mediana_es);
        $this->assertEquals('34500.50', $modelo->mediana_es);

        $this->assertIsArray($modelo->fuente_cobertura);
    }

    public function test_scouting_mercado_has_many_modelos(): void
    {
        $scouting = ScoutingMercado::create([
            'scouting_id' => 'test-'.uniqid(),
            'schema_version' => 1,
            'flujo' => 'C',
            'generado_el' => now(),
            'origen' => 'test',
            'organization_id' => Organization::factory()->create()->id,
        ]);

        $scouting->modelos()->create([
            'modelo' => 'Test Model 1',
        ]);

        $scouting->modelos()->create([
            'modelo' => 'Test Model 2',
        ]);

        $this->assertCount(2, $scouting->modelos);
    }

    public function test_store_mercado_same_scouting_id_isolated_by_organization(): void
    {
        // Auditoría 3 (#1) — scouting_id es unique PER organización, no global.
        // Dos orgs pueden usar el mismo scouting_id sin pisarse.
        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $orgB = Organization::factory()->create(['name' => 'Org B']);

        $base = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'generado_el' => '2026-08-11T12:00:00+02:00',
                'origen' => 'chat-ia',
                'scouting_id' => 'scouting-dup-2026-08-11',
            ],
            'modelos_escaneados' => 1,
            'modelos_con_hueco' => 1,
            'modelos_sin_hueco' => 0,
            'resumen_ejecutivo' => 'A',
            'modelos' => [
                ['modelo' => 'Modelo A', 'hueco_pct' => 12, 'n_uds_de' => 5],
            ],
        ];

        // Subir con org A
        $this->postJson('/api/import-mercado?org=Org%20A', $base, ['X-Import-Token' => $this->token])
            ->assertStatus(201);

        // Subir con org B (mismo scouting_id)
        $payloadB = $base;
        $payloadB['resumen_ejecutivo'] = 'B';
        $payloadB['modelos'][0]['modelo'] = 'Modelo B';
        $this->postJson('/api/import-mercado?org=Org%20B', $payloadB, ['X-Import-Token' => $this->token])
            ->assertStatus(201);

        // Ambos scoutings existen, cada uno en su org
        $this->assertSame(2, ScoutingMercado::count());

        $scoutingA = ScoutingMercado::where('scouting_id', 'scouting-dup-2026-08-11')
            ->where('organization_id', $orgA->id)
            ->first();
        $scoutingB = ScoutingMercado::where('scouting_id', 'scouting-dup-2026-08-11')
            ->where('organization_id', $orgB->id)
            ->first();

        $this->assertNotNull($scoutingA);
        $this->assertNotNull($scoutingB);
        $this->assertSame('A', $scoutingA->resumen_ejecutivo);
        $this->assertSame('B', $scoutingB->resumen_ejecutivo);
        $this->assertSame('Modelo A', $scoutingA->modelos()->first()->modelo);
        $this->assertSame('Modelo B', $scoutingB->modelos()->first()->modelo);
    }

    public function test_index_scouting_returns_list_for_org(): void
    {
        // Auditoría 3 (#7) — GET /api/scouting (lectura de Flujo C)
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);
        Organization::factory()->create(['name' => 'Otra Org']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'C',
                'generado_el' => '2026-08-11T12:00:00+02:00',
                'origen' => 'chat-ia',
                'scouting_id' => 'scouting-list-1',
            ],
            'modelos_escaneados' => 1,
            'modelos_con_hueco' => 1,
            'modelos_sin_hueco' => 0,
            'resumen_ejecutivo' => 'Lista test',
            'modelos' => [
                ['modelo' => 'VW Golf', 'hueco_pct' => 15, 'n_uds_de' => 3],
            ],
        ];

        $this->postJson('/api/import-mercado', $payload, ['X-Import-Token' => $this->token])
            ->assertStatus(201);

        $response = $this->getJson('/api/scouting', ['X-Import-Token' => $this->token]);
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'organization_id' => $org->id,
                'total' => 1,
            ]);

        $this->assertSame('VW Golf', $response->json('scoutings.0.modelos.0.modelo'));
    }
}
