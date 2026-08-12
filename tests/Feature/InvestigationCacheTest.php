<?php

namespace Tests\Feature;

use App\Models\InvestigationCache;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestigationCacheTest extends TestCase
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

    public function test_store_investigation_cache_creates_new_entry(): void
    {
        $payload = [
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'potencia' => 280,
            'combustible' => 'Gasolina',
            'aspectos' => [
                'problemas_comunes' => [
                    'hallazgo' => 'Desgaste prematuro de embrague en modelos 2012-2014',
                    'fuente' => 'https://www.opel-astra-forum.com',
                    'valoracion' => 'desfavorable',
                    'fecha' => '2026-08-01',
                ],
                'fiabilidad' => [
                    'hallazgo' => 'Motor 2.0 Turbo fiable con mantenimiento regular',
                    'fuente' => 'https://www.km77.com',
                    'valoracion' => 'favorable',
                    'fecha' => '2026-08-01',
                ],
                'recalls' => [
                    'hallazgo' => 'Campaña de airbag Takata (2015-2016)',
                    'fuente' => 'https://www.dgt.es',
                    'valoracion' => 'desfavorable',
                    'fecha' => '2026-08-01',
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/investigation-cache',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'saved',
                'clave_modelo' => 'opel-astra-280cv-gasolina',
            ])
            ->assertJsonStructure([
                'status',
                'clave_modelo',
                'aspectos_validos' => [
                    'problemas_comunes',
                    'fiabilidad',
                    'recalls',
                ],
            ]);

        $this->assertDatabaseHas('investigation_cache', [
            'clave_modelo' => 'opel-astra-280cv-gasolina',
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'potencia' => 280,
            'combustible' => 'Gasolina',
        ]);
    }

    public function test_store_investigation_cache_updates_existing_entry(): void
    {
        // Crear entrada inicial (scoped por organización — §10.3)
        $cache = InvestigationCache::create([
            'clave_modelo' => 'opel-astra-280cv-gasolina',
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'potencia' => 280,
            'combustible' => 'Gasolina',
            'organization_id' => $this->org->id,
            'aspectos' => [
                'problemas_comunes' => [
                    'hallazgo' => 'Dato antiguo',
                    'fuente' => 'https://old-source.com',
                    'valoracion' => 'neutro',
                    'fecha' => '2025-01-01',
                ],
            ],
        ]);

        $payload = [
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'potencia' => 280,
            'combustible' => 'Gasolina',
            'aspectos' => [
                'problemas_comunes' => [
                    'hallazgo' => 'Dato actualizado',
                    'fuente' => 'https://new-source.com',
                    'valoracion' => 'favorable',
                    'fecha' => '2026-08-01',
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/investigation-cache',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201);

        $cache->refresh();
        $this->assertEquals('Dato actualizado', $cache->aspectos['problemas_comunes']['hallazgo']);
        $this->assertEquals('2026-08-01', $cache->aspectos['problemas_comunes']['fecha']);
    }

    public function test_get_investigation_cache_returns_valid_aspects(): void
    {
        InvestigationCache::create([
            'clave_modelo' => 'opel-astra-280cv-gasolina',
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'potencia' => 280,
            'combustible' => 'Gasolina',
            'organization_id' => $this->org->id,
            'aspectos' => [
                'problemas_comunes' => [
                    'hallazgo' => 'Dato válido',
                    'fuente' => 'https://source.com',
                    'valoracion' => 'favorable',
                    'fecha' => now()->subMonths(12)->format('Y-m-d'), // 12 meses (dentro de 18 meses de validez)
                ],
                'recalls' => [
                    'hallazgo' => 'Dato caducado',
                    'fuente' => 'https://source.com',
                    'valoracion' => 'desfavorable',
                    'fecha' => now()->subMonths(8)->format('Y-m-d'), // 8 meses (fuera de 6 meses de validez)
                ],
            ],
        ]);

        $response = $this->getJson(
            '/api/investigation-cache?marca=Opel&modelo=Astra&potencia=280&combustible=Gasolina',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'found',
                'clave_modelo' => 'opel-astra-280cv-gasolina',
            ])
            ->assertJsonStructure([
                'status',
                'clave_modelo',
                'aspectos_validos',
                'aspectos_caducados',
            ]);

        $data = $response->json();
        $this->assertArrayHasKey('problemas_comunes', $data['aspectos_validos']);
        $this->assertArrayHasKey('recalls', $data['aspectos_caducados']);
    }

    public function test_get_investigation_cache_returns_404_when_not_found(): void
    {
        $response = $this->getJson(
            '/api/investigation-cache?marca=BMW&modelo=M3&potencia=450',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'not_found',
                'clave_modelo' => 'bmw-m3-450cv',
            ]);
    }

    public function test_store_investigation_cache_requires_marca_and_modelo(): void
    {
        $payload = [
            'potencia' => 280,
            'aspectos' => [],
        ];

        $response = $this->postJson(
            '/api/investigation-cache',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Missing required field: marca']);
    }

    public function test_store_investigation_cache_requires_aspectos_as_array(): void
    {
        $payload = [
            'marca' => 'Opel',
            'modelo' => 'Astra',
            'aspectos' => 'invalid',
        ];

        $response = $this->postJson(
            '/api/investigation-cache',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Field "aspectos" must be an object']);
    }

    public function test_get_investigation_cache_requires_marca_and_modelo(): void
    {
        // Sin ningún parámetro: faltan ambos (§10.2 mensaje mejorado)
        $response = $this->getJson(
            '/api/investigation-cache',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Missing required query params: marca, modelo']);

        // Con marca pero sin modelo: solo falta modelo
        $response = $this->getJson(
            '/api/investigation-cache?marca=Opel',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Missing required query params: modelo']);
    }

    public function test_investigation_cache_rejects_invalid_token(): void
    {
        $response = $this->postJson(
            '/api/investigation-cache',
            ['marca' => 'Opel', 'modelo' => 'Astra', 'aspectos' => []],
            ['X-Import-Token' => 'wrong-token']
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing X-Import-Token.']);
    }

    public function test_investigation_cache_returns_503_when_token_not_configured(): void
    {
        config(['services.importnex_chat.token' => null]);

        $response = $this->postJson(
            '/api/investigation-cache',
            ['marca' => 'Opel', 'modelo' => 'Astra', 'aspectos' => []],
            ['X-Import-Token' => 'any-token']
        );

        $response->assertStatus(503)
            ->assertJson(['error' => 'Import bridge not configured on this server.']);
    }

    public function test_investigation_cache_model_generates_correct_key(): void
    {
        $clave = InvestigationCache::generarClave('Opel', 'Astra', 280, 'Gasolina');
        $this->assertEquals('opel-astra-280cv-gasolina', $clave);

        $clave = InvestigationCache::generarClave('Volkswagen', 'Golf GTI', 300, null);
        $this->assertEquals('volkswagen-golf-gti-300cv', $clave);

        $clave = InvestigationCache::generarClave('BMW', 'M3', null, 'Gasolina');
        $this->assertEquals('bmw-m3-gasolina', $clave);
    }

    public function test_investigation_cache_model_detects_expired_aspects(): void
    {
        $cache = new InvestigationCache([
            'clave_modelo' => 'test',
            'marca' => 'Test',
            'modelo' => 'Test',
            'aspectos' => [
                'problemas_comunes' => [
                    'hallazgo' => 'Válido',
                    'fecha' => now()->subMonths(12)->format('Y-m-d'), // 12 meses (dentro de 18)
                ],
                'recalls' => [
                    'hallazgo' => 'Caducado',
                    'fecha' => now()->subMonths(8)->format('Y-m-d'), // 8 meses (fuera de 6)
                ],
                'fiabilidad' => [
                    'hallazgo' => 'Sin fecha',
                    // Sin fecha = caducado
                ],
            ],
        ]);

        $this->assertFalse($cache->estaCaducado('problemas_comunes'));
        $this->assertTrue($cache->estaCaducado('recalls'));
        $this->assertTrue($cache->estaCaducado('fiabilidad'));

        $validos = $cache->aspectosValidos();
        $this->assertCount(1, $validos);
        $this->assertArrayHasKey('problemas_comunes', $validos);
    }
}
