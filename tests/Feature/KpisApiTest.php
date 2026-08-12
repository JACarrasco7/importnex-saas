<?php

namespace Tests\Feature;

use App\Models\Cierre;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpisApiTest extends TestCase
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
    // GET /api/kpis
    // ────────────────────────────────────────────────────────────────────────

    public function test_kpis_requires_token(): void
    {
        $response = $this->getJson('/api/kpis');
        $response->assertStatus(401);
    }

    public function test_kpis_returns_aggregated_metrics_and_historico(): void
    {
        $mes = now()->format('Y-m-d');

        Cierre::create([
            'organization_id' => $this->org->id,
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'fecha_investigacion' => $mes,
            'veredicto' => 'Comprar',
            'precio_objetivo' => 10000,
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_final' => 10500,
            'dias_hasta_venta' => 5,
            'estado' => 'vendido',
        ]);

        $response = $this->getJson('/api/kpis?periodo='.now()->format('Y-m').'&months=3', [
            'X-Import-Token' => $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'organization_id',
                'periodo_actual',
                'kpis_periodo' => ['precision_veredictos_pct', 'tiempo_medio_venta_dias', 'desviacion_media_pct', 'tasa_falsos_positivos_pct', 'volumen'],
                'historico',
            ])
            ->assertJson([
                'periodo_actual' => now()->format('Y-m'),
                'kpis_periodo' => [
                    'precision_veredictos_pct' => 100,
                    'tiempo_medio_venta_dias' => 5,
                    'desviacion_media_pct' => 5,
                    'tasa_falsos_positivos_pct' => 0,
                    'volumen' => 1,
                ],
            ]);

        $historico = $response->json('historico');
        $this->assertCount(3, $historico);
    }

    public function test_kpis_clamp_months_parameter(): void
    {
        // months=999 → debe limitar a 24
        $response = $this->getJson('/api/kpis?months=999', [
            'X-Import-Token' => $this->token,
        ]);
        $response->assertStatus(200);
        $this->assertCount(24, $response->json('historico'));
    }

    public function test_kpis_rejects_invalid_periodo(): void
    {
        $response = $this->getJson('/api/kpis?periodo=invalid', [
            'X-Import-Token' => $this->token,
        ]);
        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid periodo format. Expected YYYY-MM.']);
    }

    public function test_kpis_returns_404_for_unknown_org(): void
    {
        $response = $this->getJson('/api/kpis?org=NoExiste', [
            'X-Import-Token' => $this->token,
        ]);
        $response->assertStatus(404);
    }

    public function test_kpis_returns_503_when_token_not_configured(): void
    {
        config(['services.importnex_chat.token' => '']);

        $response = $this->getJson('/api/kpis');
        $response->assertStatus(503);
    }

    public function test_kpis_isolated_by_organization(): void
    {
        $otherOrg = Organization::factory()->create(['name' => 'Otra Org']);

        // Cierre vendido de Otra Org
        Cierre::create([
            'organization_id' => $otherOrg->id,
            'coche_id' => 'bmw-320d-1',
            'fecha_investigacion' => now()->format('Y-m-d'),
            'veredicto' => 'Comprar',
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_objetivo' => 10000,
            'precio_final' => 10000,
            'dias_hasta_venta' => 5,
            'estado' => 'vendido',
        ]);

        // Consultar como JJ Import Motors (org default) → no debe ver el de Otra Org
        $response = $this->getJson('/api/kpis?periodo='.now()->format('Y-m'), [
            'X-Import-Token' => $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'organization_id' => $this->org->id,
                'kpis_periodo' => ['volumen' => 0],
            ]);

        // Consultar explícitamente Otra Org → sí lo ve
        $response2 = $this->getJson('/api/kpis?org=Otra%20Org&periodo='.now()->format('Y-m'), [
            'X-Import-Token' => $this->token,
        ]);
        $response2->assertStatus(200)
            ->assertJson([
                'organization_id' => $otherOrg->id,
                'kpis_periodo' => ['volumen' => 1],
            ]);
    }
}
