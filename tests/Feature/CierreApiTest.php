<?php

namespace Tests\Feature;

use App\Models\Cierre;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreApiTest extends TestCase
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

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/cierres
    // ────────────────────────────────────────────────────────────────────────

    public function test_store_cierre_creates_new_record(): void
    {
        $payload = [
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'precio_objetivo' => 11800,
            'fecha_venta' => '2026-08-15',
            'precio_final' => 11500,
            'cliente' => 'Juan Pérez',
            'plataforma' => 'Wallapop',
            'estado' => 'vendido',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJson(['status' => 'created'])
            ->assertJsonStructure(['status', 'cierre_id', 'dias_hasta_venta', 'desviacion_pct']);

        $this->assertDatabaseHas('cierres', [
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
            'organization_id' => $this->org->id,
        ]);

        // Verificar cálculo automático de días
        $cierre = Cierre::first();
        $this->assertEquals(5, $cierre->dias_hasta_venta);
    }

    public function test_store_cierre_rejects_missing_required_fields(): void
    {
        $response = $this->postJson(
            '/api/cierres',
            ['veredicto' => 'Comprar'],
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422);
    }

    public function test_store_cierre_rejects_invalid_estado(): void
    {
        $payload = [
            'coche_id' => 'test-coche',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'estado' => 'invalid_estado',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid "estado". Must be: vendido, no_vendido, pendiente']);
    }

    public function test_store_cierre_rejects_invalid_token(): void
    {
        $response = $this->postJson(
            '/api/cierres',
            ['coche_id' => 'test'],
            ['X-Import-Token' => 'wrong-token']
        );

        $response->assertStatus(401);
    }

    public function test_store_cierre_rejects_invalid_fecha_investigacion(): void
    {
        $payload = [
            'coche_id' => 'test-coche',
            'fecha_investigacion' => 'ayer',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid \'fecha_investigacion\' format. Expected YYYY-MM-DD.']);
    }

    public function test_store_cierre_rejects_invalid_fecha_venta(): void
    {
        $payload = [
            'coche_id' => 'test-coche',
            'fecha_investigacion' => '2026-08-10',
            'fecha_venta' => '2026-13-40',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid \'fecha_venta\' format. Expected YYYY-MM-DD.']);
    }

    public function test_store_cierre_rejects_invalid_veredicto(): void
    {
        $payload = [
            'coche_id' => 'test-coche',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Quizas comprar',
            'estado' => 'vendido',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid veredicto: \'Quizas comprar\'. Must be: Comprar, Comprar si baja..., Dudoso, Descartar']);
    }

    public function test_store_cierre_calculates_desviacion_correctly(): void
    {
        $payload = [
            'coche_id' => 'test-coche',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'precio_objetivo' => 10000,
            'precio_final' => 10500, // 5% por encima
            'estado' => 'vendido',
        ];

        $response = $this->postJson(
            '/api/cierres',
            $payload,
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(201)
            ->assertJson(['desviacion_pct' => 5.0]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/cierres
    // ────────────────────────────────────────────────────────────────────────

    public function test_index_cierres_returns_list_with_kpis(): void
    {
        // Crear 3 cierres: 2 vendidos con veredicto positivo, 1 no vendido
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-1',
            'fecha_investigacion' => '2026-08-05',
            'veredicto' => 'Comprar',
            'precio_objetivo' => 10000,
            'fecha_venta' => '2026-08-15',
            'precio_final' => 10500,
            'dias_hasta_venta' => 10,
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-2',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar si baja de precio',
            'precio_objetivo' => 20000,
            'fecha_venta' => '2026-08-20',
            'precio_final' => 19000,
            'dias_hasta_venta' => 10,
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-3',
            'fecha_investigacion' => '2026-08-15',
            'veredicto' => 'Comprar',
            'precio_objetivo' => 15000,
            'estado' => 'no_vendido',
        ]);

        $response = $this->getJson(
            '/api/cierres',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'organization_id',
                'total',
                'kpis' => [
                    'precision_veredictos_pct',
                    'tiempo_medio_venta_dias',
                    'desviacion_media_pct',
                    'tasa_falsos_positivos_pct',
                ],
                'cierres',
            ])
            ->assertJson(['total' => 3, 'status' => 'ok']);
    }

    public function test_index_cierres_filters_by_periodo(): void
    {
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-ago',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-sep',
            'fecha_investigacion' => '2026-09-10',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
        ]);

        $response = $this->getJson(
            '/api/cierres?periodo=2026-08',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(200)
            ->assertJson(['total' => 1]);
    }

    public function test_index_cierres_rejects_invalid_periodo_format(): void
    {
        $response = $this->getJson(
            '/api/cierres?periodo=2026-8', // Invalid, debe ser 2026-08
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(422);
    }

    public function test_index_cierres_filters_by_estado(): void
    {
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-1',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'coche-2',
            'fecha_investigacion' => '2026-08-10',
            'veredicto' => 'Comprar',
            'estado' => 'pendiente',
        ]);

        $response = $this->getJson(
            '/api/cierres?estado=vendido',
            ['X-Import-Token' => $this->token]
        );

        $response->assertStatus(200)
            ->assertJson(['total' => 1]);
    }

    public function test_index_cierres_rejects_invalid_token(): void
    {
        $response = $this->getJson(
            '/api/cierres',
            ['X-Import-Token' => 'wrong-token']
        );

        $response->assertStatus(401);
    }
}
