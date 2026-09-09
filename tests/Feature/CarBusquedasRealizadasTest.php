<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C2 auditoría 09-sep-2026: el modelo Car expone las URLs de las búsquedas
 * de mercado (mobile.de / autoscout24 / coches.net / wallapop) agrupadas
 * por país mediante el accessor `busquedasPorPais`. El panel admin las
 * muestra en la pestaña Mercado.
 */
class CarBusquedasRealizadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_getter_devuelve_diccionario_por_pais(): void
    {
        $car = $this->cocheCon([
            'busquedas_realizadas' => [
                ['pais' => 'DE', 'portal' => 'mobile.de', 'url' => 'https://suchen.mobile.de/x', 'descripcion' => 'VW Arteon 320 CV 2023-2027'],
                ['pais' => 'DE', 'portal' => 'autoscout24.de', 'url' => 'https://www.autoscout24.de/y', 'descripcion' => 'Arteon 2023'],
                ['pais' => 'ES', 'portal' => 'coches.net', 'url' => 'https://www.coches.net/z', 'descripcion' => 'Arteon 320 CV'],
                ['pais' => 'ES', 'portal' => 'wallapop', 'url' => 'https://es.wallapop.com/w', 'descripcion' => 'VW Arteon'],
            ],
        ]);

        $out = $car->busquedasPorPais;

        $this->assertArrayHasKey('DE', $out);
        $this->assertArrayHasKey('ES', $out);
        $this->assertArrayHasKey('otros', $out);
        $this->assertCount(2, $out['DE']);
        $this->assertCount(2, $out['ES']);
        $this->assertCount(0, $out['otros']);
        $this->assertSame('mobile.de', $out['DE'][0]['portal']);
        $this->assertSame('wallapop', $out['ES'][1]['portal']);
        $this->assertSame('https://suchen.mobile.de/x', $out['DE'][0]['url']);
    }

    public function test_getter_descarta_items_sin_url(): void
    {
        $car = $this->cocheCon([
            'busquedas_realizadas' => [
                ['pais' => 'DE', 'portal' => 'mobile.de', 'url' => 'https://x', 'descripcion' => 'OK'],
                ['pais' => 'DE', 'portal' => 'autoscout24.de', 'descripcion' => 'sin url → descartado'],
            ],
        ]);

        $out = $car->busquedasPorPais;
        // Solo el primero pasa (con url), el segundo sin url se descarta.
        $this->assertCount(1, $out['DE']);
        $this->assertSame('mobile.de', $out['DE'][0]['portal']);
    }

    public function test_getter_acepta_pais_desconocido_y_lo_mete_en_otros(): void
    {
        $car = $this->cocheCon([
            'busquedas_realizadas' => [
                ['pais' => 'FR', 'portal' => 'leboncoin', 'url' => 'https://leboncoin.fr/x', 'descripcion' => 'VW Arteon'],
            ],
        ]);

        $out = $car->busquedasPorPais;
        $this->assertCount(0, $out['DE']);
        $this->assertCount(0, $out['ES']);
        $this->assertCount(1, $out['otros']);
        $this->assertSame('leboncoin', $out['otros'][0]['portal']);
    }

    public function test_getter_devuelve_diccionario_vacio_si_sin_busquedas(): void
    {
        $car = $this->cocheCon([]);

        $out = $car->busquedasPorPais;
        $this->assertSame(['DE' => [], 'ES' => [], 'otros' => []], $out);
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
            'brand' => 'VW',
            'model' => 'Arteon',
            'recommendation' => 'Comprar',
        ], $attrs));
    }
}
