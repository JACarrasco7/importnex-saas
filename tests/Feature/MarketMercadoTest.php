<?php

namespace Tests\Feature;

use App\Models\MarketLead;
use App\Models\MarketModel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketMercadoTest extends TestCase
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

    protected function makeModel(array $overrides = []): MarketModel
    {
        return MarketModel::create(array_merge([
            'slug' => 'test-model-'.uniqid(),
            'modelo' => 'Test Model',
            'categoria' => 'alta_rotacion',
            'segmento' => 'compacto',
            'rango_precio' => '8-14k',
            'tipo_cliente' => 'diario_eficiencia',
            'hueco_pct' => 12.5,
            'hueco_neto_pct' => 8.0,
            'mediana_de' => 12000,
            'mediana_es' => 13500,
            'precio_desde_de' => 10000,
            'veredicto' => 'verde',
            'mejor_mercado' => 'DE',
            'publicar_en_catalogo' => true,
        ], $overrides));
    }

    public function test_catalogo_publico_solo_muestra_modelos_publicados(): void
    {
        $publico = $this->makeModel();
        $this->makeModel(['publicar_en_catalogo' => false, 'slug' => 'oculto-'.uniqid()]);

        $this->getJson('/api/public/market')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('catalogo.0.slug', $publico->slug);
    }

    public function test_lead_se_crea_desde_el_catalogo(): void
    {
        $model = $this->makeModel();

        $this->postJson("/mercado/{$model->id}/interes", [
            'nombre' => 'María',
            'contacto' => 'maria@test.es',
            'presupuesto' => 14000,
        ])->assertRedirect();

        $this->assertDatabaseHas('market_leads', [
            'market_model_id' => $model->id,
            'contacto' => 'maria@test.es',
            'estado' => 'nuevo',
        ]);
    }

    public function test_lead_requiere_contacto(): void
    {
        $model = $this->makeModel();

        $this->postJson("/mercado/{$model->id}/interes", [])->assertStatus(422);

        $this->assertSame(0, MarketLead::count());
    }

    public function test_coste_puesto_en_huelva(): void
    {
        $model = $this->makeModel(['precio_desde_de' => 10000, 'iedmt_estimado' => 500]);

        $coste = $model->costePuestoEnHuelva();

        // 10000 + 900 + 114 + 115 + 500 + 1500 = 13129
        $this->assertSame(13129.0, $coste['total']);
    }

    public function test_scopes_de_veredicto_y_caducidad(): void
    {
        $this->makeModel();
        $this->makeModel(['veredicto' => 'rojo', 'slug' => 'rojo-'.uniqid()]);
        $this->makeModel([
            'slug' => 'caducado-'.uniqid(),
            'refrescar_antes_de' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(2, MarketModel::verdes()->count());
        $this->assertSame(1, MarketModel::caducados()->count());
    }

    public function test_vendibilidad_fallback_se_calcula(): void
    {
        $model = $this->makeModel(['vendibilidad' => null]);

        $this->assertNotNull($model->calcularVendibilidad());
        $this->assertTrue($model->calcularVendibilidad() >= 0 && $model->calcularVendibilidad() <= 100);
    }

    public function test_admin_aisla_modelos_por_organizacion(): void
    {
        $orgA = Organization::factory()->create(['is_public' => true]);
        $orgB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $orgA->id]);

        $this->makeModel(['organization_id' => $orgA->id, 'slug' => 'orga-'.uniqid()]);
        $this->makeModel(['organization_id' => $orgB->id, 'slug' => 'orgb-'.uniqid()]);
        $this->makeModel(['organization_id' => null, 'slug' => 'global-'.uniqid()]);

        // El scope que usa el panel admin: solo modelos de orgA + los globales (null)
        $slugs = MarketModel::deOrganizacion($orgA->id)->pluck('slug');

        $this->assertTrue($slugs->contains(fn ($s) => str_starts_with($s, 'orga-')));
        $this->assertFalse($slugs->contains(fn ($s) => str_starts_with($s, 'orgb-')));
        $this->assertTrue($slugs->contains(fn ($s) => str_starts_with($s, 'global-')));
    }

    public function test_update_marca_veredicto_como_humano(): void
    {
        $model = $this->makeModel(['veredicto' => 'amarillo']);
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $model->update(['organization_id' => $org->id]);

        $this->actingAs($user)->patch("/mercado/admin/{$model->id}", ['veredicto' => 'verde']);

        $this->assertDatabaseHas('market_models', [
            'id' => $model->id,
            'veredicto' => 'verde',
            'veredicto_fuente' => 'humano',
        ]);
    }

    public function test_stats_publicas_devuelven_agregados(): void
    {
        $this->makeModel();
        $this->makeModel(['veredicto' => 'rojo', 'slug' => 'rojo-'.uniqid()]);

        $this->getJson('/api/public/market/stats')
            ->assertOk()
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.verdes', 1)
            ->assertJsonPath('stats.hueco_medio', 12.5);
    }

    public function test_api_market_lista_modelos_de_la_org(): void
    {
        $orgA = Organization::where('name', 'JJ Import Motors')->first();
        $orgB = Organization::factory()->create();

        $this->makeModel(['organization_id' => $orgA->id, 'slug' => 'apia-'.uniqid()]);
        $this->makeModel(['organization_id' => $orgB->id, 'slug' => 'apib-'.uniqid()]);
        $this->makeModel(['organization_id' => null, 'slug' => 'apiglobal-'.uniqid()]);

        $response = $this->getJson('/api/market', ['X-Import-Token' => $this->token]);

        $response->assertOk();
        $slugs = collect($response->json('models'))->pluck('slug');
        $this->assertTrue($slugs->contains(fn ($s) => str_starts_with($s, 'apia-')));
        $this->assertFalse($slugs->contains(fn ($s) => str_starts_with($s, 'apib-')));
        $this->assertTrue($slugs->contains(fn ($s) => str_starts_with($s, 'apiglobal-')));
    }

    public function test_api_market_stats_devuelven_agregados_de_la_org(): void
    {
        $this->makeModel();
        $this->makeModel(['veredicto' => 'rojo', 'slug' => 'rojo-'.uniqid()]);

        $this->getJson('/api/market/stats', ['X-Import-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.verdes', 1);
    }

    public function test_coste_oculto_para_modelo_no_publicado(): void
    {
        $oculto = $this->makeModel(['publicar_en_catalogo' => false, 'slug' => 'oculto-'.uniqid()]);

        $this->getJson("/mercado/{$oculto->id}/coste")->assertStatus(404);
    }

    public function test_lead_rechazado_para_modelo_no_publicado(): void
    {
        $oculto = $this->makeModel(['publicar_en_catalogo' => false, 'slug' => 'oculto-'.uniqid()]);

        $this->postJson("/mercado/{$oculto->id}/interes", ['contacto' => 'x@test.es'])->assertStatus(404);

        $this->assertSame(0, MarketLead::count());
    }

    public function test_reporte_por_categoria_respeta_organizacion(): void
    {
        $org = Organization::factory()->create();
        $other = Organization::factory()->create();

        $this->makeModel(['organization_id' => $org->id, 'categoria' => 'showstoppers', 'slug' => 'show-'.uniqid()]);
        $this->makeModel(['organization_id' => $org->id, 'categoria' => 'gemas', 'slug' => 'gema-'.uniqid()]);
        $this->makeModel(['organization_id' => $other->id, 'slug' => 'otra-'.uniqid()]); // default alta_rotacion

        // Misma lógica que el reporte: solo modelos de esta org
        $porCategoria = MarketModel::deOrganizacion($org->id)->get()->groupBy('categoria');

        $this->assertTrue($porCategoria->has('showstoppers'));
        $this->assertTrue($porCategoria->has('gemas'));
        $this->assertFalse($porCategoria->has('alta_rotacion')); // el de la otra org no cuenta
    }
}
