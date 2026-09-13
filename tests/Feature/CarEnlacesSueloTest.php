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
 * Los formatos de URL siguen los verificados por Claude (12/15-ago-2026):
 *  - mobile.de  -> suchen.mobile.de/fahrzeuge/search.html?...&sb=p
 *  - coches.net -> /segunda-mano/coches/<slug>?...&fi=Price&or=1
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

    public function test_mobile_de_ordena_por_precio_y_filtra_marca_anio_y_potencia(): void
    {
        $url = $this->cocheCon([])->enlacesSuelo[0]['url'];

        $this->assertStringStartsWith('https://suchen.mobile.de/fahrzeuge/search.html?', $url);
        $this->assertStringContainsString('sb=p', $url);            // orden por precio asc = suelo
        $this->assertStringContainsString('dam=0', $url);           // sin siniestros
        $this->assertStringContainsString('ms=25200%3B%3B%3B', $url); // VW
        $this->assertStringContainsString('fr=2018%3A2020', $url);  // ±1 año
        $this->assertStringContainsString('pw=301%3A369', $url);    // 335 cv ±10%
        // El dominio /es/ está deprecado: cae en modo formulario sin tarjetas.
        $this->assertStringNotContainsString('www.mobile.de/es', $url);
    }

    public function test_coches_net_usa_el_path_de_marca_modelo_y_ordena_por_precio(): void
    {
        $url = $this->cocheCon([])->enlacesSuelo[1]['url'];

        $this->assertStringStartsWith('https://www.coches.net/volkswagen/arteon/segunda-mano/?', $url);
        $this->assertStringContainsString('fi=Price', $url);        // campo de orden
        $this->assertStringContainsString('or=1', $url);            // ascendente = suelo
        $this->assertStringContainsString('MinYear=2018', $url);
        $this->assertStringContainsString('PowerHpFrom=301', $url);
        $this->assertStringContainsString('PowerHpTo=369', $url);
        // Regla dura SKILL.md v3.3.8: nunca filtrar por `Versions[]`.
        $this->assertStringNotContainsString('Versions', $url);
    }

    public function test_coches_net_usa_solo_el_primer_token_del_modelo(): void
    {
        // Verificado en navegador: `golf-7-5-tcr` degrada a la página de marca,
        // `golf` sí devuelve "VOLKSWAGEN Golf de segunda mano".
        $url = $this->cocheCon(['model' => 'Golf 7.5 TCR'])->enlacesSuelo[1]['url'];

        $this->assertStringStartsWith('https://www.coches.net/volkswagen/golf/segunda-mano/?', $url);
    }

    public function test_coches_net_con_modelo_numerico_usa_dos_tokens(): void
    {
        $url = $this->cocheCon(['brand' => 'BMW', 'model' => '3 Series'])->enlacesSuelo[1]['url'];

        $this->assertStringStartsWith('https://www.coches.net/bmw/3-series/segunda-mano/?', $url);
    }

    public function test_sin_potencia_omite_los_filtros_de_cv(): void
    {
        $enlaces = $this->cocheCon(['cv' => 0])->enlacesSuelo;

        $this->assertStringNotContainsString('pw=', $enlaces[0]['url']);
        $this->assertStringNotContainsString('PowerHp', $enlaces[1]['url']);
    }

    public function test_sin_anio_omite_los_filtros_de_fecha(): void
    {
        $enlaces = $this->cocheCon(['year' => ''])->enlacesSuelo;

        $this->assertStringNotContainsString('fr=', $enlaces[0]['url']);
        $this->assertStringNotContainsString('MinYear', $enlaces[1]['url']);
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

    public function test_acepta_marca_con_alias_corto(): void
    {
        $enlaces = $this->cocheCon(['brand' => 'VW'])->enlacesSuelo;

        $this->assertStringContainsString('ms=25200%3B%3B%3B', $enlaces[0]['url']);
        $this->assertStringStartsWith('https://www.coches.net/volkswagen/arteon/segunda-mano/?', $enlaces[1]['url']);
    }

    public function test_marca_desconocida_no_rompe_la_url(): void
    {
        $url = $this->cocheCon(['brand' => 'Tesla', 'model' => 'Model 3'])->enlacesSuelo[0]['url'];

        $this->assertStringStartsWith('https://suchen.mobile.de/fahrzeuge/search.html?', $url);
        $this->assertStringNotContainsString('ms=', $url);
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
