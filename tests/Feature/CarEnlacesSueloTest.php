<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El modelo Car expone `enlacesSuelo`: enlaces "ver suelo" a mobile.de (DE) y
 * coches.net (ES) generados desde los datos del coche, ordenados por precio
 * ascendente. Cubren los coches cuyo ZIP no trae `mercado.busquedas_realizadas[]`.
 *
 * Formatos según el spec canónico de la skill (`playbook_filtrado.md`, 24-ago):
 *  - mobile.de  -> suchen.mobile.de/fahrzeuge/search.html?...&sb=p, `pw` en kW
 *  - coches.net -> /segunda-mano/?MakeIds[0]=..&Versions[0]=..&fi=Price&or=1
 */
class CarEnlacesSueloTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_un_enlace_por_portal_con_precio_de_referencia(): void
    {
        $enlaces = $this->cocheCon([])->enlacesSuelo;

        $this->assertCount(2, $enlaces);
        $this->assertSame('mobile.de', $enlaces[0]['portal']);
        $this->assertSame('DE', $enlaces[0]['pais']);
        $this->assertSame('coches.net', $enlaces[1]['portal']);
        $this->assertSame('ES', $enlaces[1]['pais']);
        $this->assertFalse($enlaces[0]['en_informe']);
        $this->assertFalse($enlaces[1]['en_informe']);
    }

    public function test_mobile_de_usa_kw_en_la_potencia_con_margen_de_4(): void
    {
        // 335 cv × 0,7355 = 246,39 kW → 246 ± 4 = 242:250 (en CV sería 335).
        $url = $this->cocheCon([])->enlacesSuelo[0]['url'];

        $this->assertStringStartsWith('https://suchen.mobile.de/fahrzeuge/search.html?', $url);
        $this->assertStringContainsString('pw=242%3A250', $url);
        $this->assertStringContainsString('sb=p', $url);             // precio asc = suelo
        $this->assertStringContainsString('dam=0', $url);            // sin siniestros
        $this->assertStringContainsString('isSearchRequest=true', $url);
        $this->assertStringContainsString('od=up', $url);
        $this->assertStringContainsString('fr=2018%3A2020', $url);   // ±1 año
        // El dominio /es/ está deprecado: cae en modo formulario sin tarjetas.
        $this->assertStringNotContainsString('www.mobile.de/es', $url);
    }

    public function test_mobile_de_usa_ms_con_el_model_id_verificado(): void
    {
        // VW Golf Mk7.5 = 12603 (mapa de IDs verificados de mobile.de).
        $url = $this->cocheCon(['brand' => 'VW', 'model' => 'Golf 7.5 TCR'])->enlacesSuelo[0]['url'];

        // `ms` son CINCO campos: makeId;modelId;;;
        $this->assertStringContainsString('ms=25200%3B12603%3B%3B%3B', $url);
        $this->assertStringNotContainsString('q=', $url);
    }

    public function test_mobile_de_cae_a_texto_libre_si_no_hay_model_id(): void
    {
        // Sin modelId conocido, `ms` deja la página en modo formulario (0 tarjetas).
        $url = $this->cocheCon([])->enlacesSuelo[0]['url'];

        $this->assertStringContainsString('q=Volkswagen+Arteon', $url);
        $this->assertStringNotContainsString('ms=', $url);
    }

    public function test_mobile_de_reutiliza_el_model_id_de_las_busquedas_del_informe(): void
    {
        $url = $this->cocheCon([
            'busquedas_realizadas' => [
                ['pais' => 'DE', 'portal' => 'mobile.de', 'url' => 'https://suchen.mobile.de/fahrzeuge/search.html?ms=25200%3B64%3B%3B%3B&sb=p', 'descripcion' => 'Arteon 320 CV'],
            ],
        ])->enlacesSuelo[0]['url'];

        $this->assertStringContainsString('ms=25200%3B64%3B%3B%3B', $url);
        $this->assertStringNotContainsString('q=', $url);
    }

    public function test_coches_net_usa_make_ids_versions_y_potencia_en_cv(): void
    {
        // Verificado en navegador 13-sep-2026: MakeIds[0]=47&Versions[0]=Golf
        // -> "VOLKSWAGEN GOLF de segunda mano y ocasión | Coches.net".
        $url = $this->cocheCon([])->enlacesSuelo[1]['url'];

        $this->assertSame(
            'https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Arteon&PowerHpFrom=330&PowerHpTo=340&fi=Price&or=1',
            $url
        );
    }

    public function test_coches_net_limpia_la_generacion_del_modelo(): void
    {
        // `Versions[0]` es texto libre: la generación/variante rompe el filtro.
        $url = $this->cocheCon(['brand' => 'VW', 'model' => 'Golf 7.5 TCR'])->enlacesSuelo[1]['url'];

        $this->assertStringContainsString('Versions[0]=Golf', $url);
        $this->assertStringNotContainsString('7.5', $url);
    }

    public function test_coches_net_conserva_modelo_numerico(): void
    {
        $url = $this->cocheCon(['brand' => 'BMW', 'model' => '3 Series'])->enlacesSuelo[1]['url'];

        $this->assertStringContainsString('MakeIds[0]=11', $url);
        $this->assertStringContainsString('Versions[0]=3+Series', $url);
    }

    public function test_sin_potencia_omite_los_filtros_de_potencia(): void
    {
        $enlaces = $this->cocheCon(['cv' => 0])->enlacesSuelo;

        $this->assertStringNotContainsString('pw=', $enlaces[0]['url']);
        $this->assertStringNotContainsString('PowerHp', $enlaces[1]['url']);
    }

    public function test_sin_anio_omite_el_filtro_de_fecha(): void
    {
        $url = $this->cocheCon(['year' => ''])->enlacesSuelo[0]['url'];

        $this->assertStringNotContainsString('fr=', $url);
    }

    public function test_sin_marca_o_modelo_no_genera_enlaces(): void
    {
        $this->assertSame([], $this->cocheCon(['brand' => ''])->enlacesSuelo);
        $this->assertSame([], $this->cocheCon(['model' => ''])->enlacesSuelo);
    }

    public function test_marca_en_informe_cuando_el_zip_ya_trae_la_busqueda_de_ese_portal(): void
    {
        $enlaces = $this->cocheCon([
            'busquedas_realizadas' => [
                ['pais' => 'DE', 'portal' => 'mobile.de', 'url' => 'https://suchen.mobile.de/x', 'descripcion' => 'Arteon'],
                ['pais' => 'ES', 'portal' => 'wallapop', 'url' => 'https://es.wallapop.com/w', 'descripcion' => 'Arteon'],
            ],
        ])->enlacesSuelo;

        $this->assertTrue($enlaces[0]['en_informe']);   // mobile.de ya venía en el informe
        $this->assertFalse($enlaces[1]['en_informe']);  // coches.net no
    }

    public function test_marca_desconocida_usa_texto_libre_y_no_filtra_marca(): void
    {
        $enlaces = $this->cocheCon(['brand' => 'Tesla', 'model' => 'Model 3'])->enlacesSuelo;

        $this->assertStringContainsString('q=Tesla+Model+3', $enlaces[0]['url']);
        $this->assertStringNotContainsString('ms=', $enlaces[0]['url']);
        $this->assertStringNotContainsString('MakeIds', $enlaces[1]['url']);
        $this->assertStringContainsString('Versions[0]=Model', $enlaces[1]['url']);
    }

    public function test_alias_corto_de_marca_resuelve_los_ids(): void
    {
        $enlaces = $this->cocheCon(['brand' => 'VW', 'model' => 'Golf 7.5 TCR'])->enlacesSuelo;

        $this->assertStringContainsString('ms=25200%3B12603%3B%3B%3B', $enlaces[0]['url']);
        $this->assertSame(
            'https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Golf&PowerHpFrom=330&PowerHpTo=340&fi=Price&or=1',
            $enlaces[1]['url']
        );
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function cocheCon(array $attrs): Car
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        return Car::factory()->create(array_merge([
            'organization_id' => $org->id,
            'brand' => 'Volkswagen',
            'model' => 'Arteon',
            'year' => '06/2019',
            'cv' => 335,
        ], $attrs));
    }
}
