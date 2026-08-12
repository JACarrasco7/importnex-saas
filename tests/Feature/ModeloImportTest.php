<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeloImportTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
    }

    public function test_store_modelo_creates_car_with_flujo_b(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'B',
                'generado_el' => '2026-08-11T12:00:00+02:00',
                'origen' => 'chat-ia',
                'coche_id' => 'vw-golf-gti-2017-modelo',
            ],
            'vehiculo' => [
                'marca' => 'Volkswagen',
                'modelo' => 'Golf',
                'version' => 'GTI Clubsport',
                'anio' => 2017,
                'km' => 62000,
                'combustible' => 'Gasolina',
                'cambio' => 'DSG',
                'potencia_cv' => 300,
                'co2_gkm' => 158,
            ],
            'anuncio' => [
                'portal' => 'mobile.de',
                'url' => 'https://www.mobile.de/golf-gti-clubsport-2017',
                'pais_origen' => 'Alemania',
                'precio_publicado' => 28900,
                'moneda' => 'EUR',
                'vendedor_tipo' => 'Profesional',
            ],
            'investigacion' => [
                'problemas_comunes' => [
                    'hallazgo' => 'DSG7 DQ381 fiable con mantenimiento',
                    'fuente' => 'https://example.com',
                    'valoracion' => 'favorable',
                ],
                'recalls' => [
                    'hallazgo' => 'Sin recalls conocidos',
                    'fuente' => 'https://kfz-rueckrufe.de',
                    'valoracion' => 'favorable',
                ],
                'precio_mercado' => [
                    'hallazgo' => 'Mediana ES: 34.500€, Mediana DE: 26.800€',
                    'fuente' => 'Coches.net + mobile.de',
                    'valoracion' => 'favorable',
                ],
                'fiabilidad' => [
                    'hallazgo' => 'Motor EA888 Gen3 fiable',
                    'fuente' => 'Foros VW',
                    'valoracion' => 'favorable',
                ],
                'homologacion' => [
                    'hallazgo' => 'Modelo común en España',
                    'fuente' => 'DGT',
                    'valoracion' => 'favorable',
                ],
                'etiqueta_ambiental' => [
                    'hallazgo' => 'Etiqueta C (2017 gasolina)',
                    'fuente' => 'DGT',
                    'valoracion' => 'neutro',
                ],
                'seguro' => [
                    'hallazgo' => 'Prima media 800-1200€/año',
                    'fuente' => 'Comparadores',
                    'valoracion' => 'neutro',
                ],
                'piezas' => [
                    'hallazgo' => 'Disponibilidad alta en España',
                    'fuente' => 'Recambios',
                    'valoracion' => 'favorable',
                ],
                'otros' => [
                    'hallazgo' => '',
                    'fuente' => '',
                    'valoracion' => '',
                ],
            ],
            'balance' => [
                'a_favor' => [
                    ['texto' => 'Hueco 22.4% vs España', 'peso' => 'alto'],
                    ['texto' => 'Vendibilidad alta (85/100)', 'peso' => 'alto'],
                ],
                'en_contra' => [
                    ['texto' => 'Sin comparable ajustado todavía', 'peso' => 'medio'],
                ],
            ],
            'veredicto' => [
                'recomendacion' => 'Investigar más',
                'confianza' => 'media',
                'razonamiento' => 'Modelo con buen hueco y vendibilidad. Pendiente comparable ajustado.',
                'precio_objetivo' => 26000,
                'fecha' => '11/08/2026',
            ],
            'costes' => [
                'precio_coche' => 28900,
                'pvp_nuevo' => 45000,
                'transporte' => 900,
                'itv_matriculacion' => 95,
                'tasa_dgt' => 20.61,
                'iedmt_estimado' => 280,
                'iedmt_sin_minoracion' => 365,
                'gestoria' => 0,
                'otros' => 114,
                'coste_total' => 30309.61,
                'honorarios' => 1500,
                'precio_cliente' => 31809.61,
            ],
            'mercado' => [
                'comparables' => [
                    [
                        'titulo' => 'VW Golf GTI 2017',
                        'precio' => 34500,
                        'km' => 65000,
                        'url' => 'https://www.coches.net/golf-gti-2017',
                        'pais' => 'España',
                    ],
                ],
                'precio_medio' => 34500,
                'precio_min' => 31200,
                'precio_max' => 38000,
                'ahorro_estimado' => 4190.39,
                'semaforo' => 'green',
            ],
            'avisos' => [
                'Pendiente comparable ajustado (elegir 1-2 unidades)',
            ],
        ];

        $response = $this->postJson(
            '/api/import-modelo?org=JJ%20Import%20Motors',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'created',
                'flujo' => 'B',
            ]);

        $this->assertDatabaseHas('cars', [
            'brand' => 'Volkswagen',
            'model' => 'Golf',
        ]);

        // Verificar que el coche se creó (el año se normaliza a "MM/YYYY")
        $car = Car::where('brand', 'Volkswagen')
            ->where('model', 'Golf')
            ->first();

        $this->assertNotNull($car);
        $this->assertEquals('01/2017', $car->year);
    }

    public function test_store_modelo_rejects_invalid_flujo(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A', // Wrong flujo (should be 'B')
            ],
            'vehiculo' => ['marca' => 'VW'],
            'anuncio' => ['url' => 'https://example.com'],
            'veredicto' => ['recomendacion' => 'Comprar'],
            'costes' => ['precio_coche' => 10000],
            'mercado' => ['precio_medio' => 15000],
        ];

        $response = $this->postJson(
            '/api/import-modelo',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => "Invalid _meta.flujo. Expected 'B', got 'A'."]);
    }

    public function test_store_modelo_strips_publicidad_block(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'B',
            ],
            'vehiculo' => [
                'marca' => 'Volkswagen',
                'modelo' => 'Golf',
                'version' => 'GTI',
                'anio' => 2017,
                'km' => 62000,
                'combustible' => 'Gasolina',
                'cambio' => 'DSG',
                'potencia_cv' => 300,
                'co2_gkm' => 158,
            ],
            'anuncio' => [
                'portal' => 'mobile.de',
                'url' => 'https://www.mobile.de/golf-gti-2017',
                'precio_publicado' => 28900,
            ],
            'investigacion' => [
                'problemas_comunes' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'recalls' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'precio_mercado' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'fiabilidad' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'homologacion' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'etiqueta_ambiental' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'seguro' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'piezas' => ['hallazgo' => 'Test', 'fuente' => 'Test', 'valoracion' => 'neutro'],
                'otros' => ['hallazgo' => '', 'fuente' => '', 'valoracion' => ''],
            ],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => [
                'recomendacion' => 'Investigar más',
                'confianza' => 'media',
                'razonamiento' => 'Test',
                'precio_objetivo' => 26000,
                'fecha' => '11/08/2026',
            ],
            'costes' => [
                'precio_coche' => 28900,
                'pvp_nuevo' => 45000,
                'transporte' => 900,
                'coste_total' => 30309.61,
                'honorarios' => 1500,
            ],
            'mercado' => [
                'comparables' => [],
                'precio_medio' => 34500,
                'semaforo' => 'green',
            ],
            'publicidad' => [
                'titular' => 'Este bloque debería eliminarse',
                'claim' => 'Flujo B no debe tener publicidad',
            ],
        ];

        $response = $this->postJson(
            '/api/import-modelo',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201);

        // Verificar que el coche se creó sin error (publicidad fue eliminado)
        $this->assertDatabaseHas('cars', [
            'brand' => 'Volkswagen',
            'model' => 'Golf',
        ]);
    }

    public function test_store_modelo_rejects_invalid_token(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $payload = [
            '_meta' => ['flujo' => 'B'],
        ];

        $response = $this->postJson(
            '/api/import-modelo',
            $payload,
            ['X-Import-Token' => 'wrong-token']
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing X-Import-Token.']);
    }

    public function test_store_modelo_returns_503_when_token_not_configured(): void
    {
        config(['services.importnex_chat.token' => null]);

        $payload = [
            '_meta' => ['flujo' => 'B'],
        ];

        $response = $this->postJson(
            '/api/import-modelo',
            $payload,
            ['X-Import-Token' => 'any-token']
        );

        $response->assertStatus(503)
            ->assertJson(['error' => 'Import bridge not configured on this server.']);
    }
}
