<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use Tests\TestCase;

/**
 * Tests del accessor `comparablesStatsByCountry` (09-sep-2026).
 *
 * Caso real: el admin ficha mostraba un único promedio de mercado
 * mezclando España y Alemania. El usuario pidió aclaración: ¿el promedio
 * es de ES o de DE? El fix fue separar stats por país y enseñar el desglose
 * explícito en la UI.
 */
class CarComparablesByCountryTest extends TestCase
{
    public function test_devuelve_cuatro_buckets_vacios_si_no_hay_comparables(): void
    {
        $car = new Car(['comparables_list' => []]);
        $stats = $car->comparables_stats_by_country;

        $this->assertEquals(['es', 'de', 'mixto', 'unknown'], array_keys($stats));
        foreach ($stats as $bucket) {
            $this->assertNull($bucket['avg']);
            $this->assertNull($bucket['min']);
            $this->assertNull($bucket['max']);
            $this->assertEquals(0, $bucket['count']);
        }
    }

    public function test_separa_por_pais_espania_alemania_y_mixto(): void
    {
        $car = new Car([
            'comparables_list' => [
                ['title' => 'BMW 320d ES', 'price' => 15000, 'url' => 'https://es/1', 'country' => 'España'],
                ['title' => 'BMW 320d ES2', 'price' => 17000, 'url' => 'https://es/2', 'country' => 'ES'],
                ['title' => 'BMW 320d DE', 'price' => 12000, 'url' => 'https://de/1', 'country' => 'Alemania'],
                ['title' => 'BMW 320d DE2', 'price' => 14000, 'url' => 'https://de/2', 'country' => 'DE'],
                ['title' => 'BMW 320d mixto', 'price' => 13000, 'url' => 'https://fr/1', 'country' => 'Francia'],
                ['title' => 'BMW 320d sin pais', 'price' => 16000, 'url' => 'https://x/1'],
            ],
        ]);

        $stats = $car->comparables_stats_by_country;

        // ES
        $this->assertEquals(2, $stats['es']['count']);
        $this->assertEquals(16000, $stats['es']['avg']);
        $this->assertEquals(15000, $stats['es']['min']);
        $this->assertEquals(17000, $stats['es']['max']);

        // DE
        $this->assertEquals(2, $stats['de']['count']);
        $this->assertEquals(13000, $stats['de']['avg']);
        $this->assertEquals(12000, $stats['de']['min']);
        $this->assertEquals(14000, $stats['de']['max']);

        // Mixto (Francia)
        $this->assertEquals(1, $stats['mixto']['count']);
        $this->assertEquals(13000, $stats['mixto']['avg']);

        // Unknown (sin campo country)
        $this->assertEquals(1, $stats['unknown']['count']);
        $this->assertEquals(16000, $stats['unknown']['avg']);
    }

    public function test_acepta_formato_legacy_campo_p(): void
    {
        $car = new Car([
            'comparables_list' => [
                ['t' => 'Audi A3 ES', 'p' => 12500, 'u' => 'https://es/1', 'pais' => 'España'],
                ['t' => 'Audi A3 DE', 'p' => 10500, 'u' => 'https://de/1', 'pais' => 'Alemania'],
            ],
        ]);

        $stats = $car->comparables_stats_by_country;

        $this->assertEquals(1, $stats['es']['count']);
        $this->assertEquals(12500, $stats['es']['avg']);
        $this->assertEquals(1, $stats['de']['count']);
        $this->assertEquals(10500, $stats['de']['avg']);
    }

    public function test_acepta_precios_con_formato_moneda_string(): void
    {
        $car = new Car([
            'comparables_list' => [
                ['title' => 'BMW 320d', 'price' => '17.500 €', 'url' => 'https://es/1', 'country' => 'España'],
                ['title' => 'BMW 320d DE', 'price' => '12.300 €', 'url' => 'https://de/1', 'country' => 'DE'],
            ],
        ]);

        $stats = $car->comparables_stats_by_country;

        $this->assertEquals(17500, $stats['es']['avg']);
        $this->assertEquals(12300, $stats['de']['avg']);
    }

    public function test_reconoce_variantes_del_nombre_del_pais(): void
    {
        $car = new Car([
            'comparables_list' => [
                ['title' => 'A', 'price' => 10000, 'country' => '🇪🇸'],
                ['title' => 'B', 'price' => 11000, 'country' => 'españa'],
                ['title' => 'C', 'price' => 12000, 'country' => 'España '],
                ['title' => 'D', 'price' => 13000, 'country' => '🇩🇪'],
                ['title' => 'E', 'price' => 14000, 'country' => 'alemania'],
            ],
        ]);

        $stats = $car->comparables_stats_by_country;

        $this->assertEquals(3, $stats['es']['count']);
        $this->assertEquals(2, $stats['de']['count']);
    }

    public function test_incluye_items_completos_para_filtrado_en_ui(): void
    {
        // La UI usa bucket.items para filtrar la lista de comparables.
        $car = new Car([
            'comparables_list' => [
                ['title' => 'BMW ES', 'price' => 15000, 'url' => 'https://es/1', 'country' => 'España'],
                ['title' => 'BMW DE', 'price' => 12000, 'url' => 'https://de/1', 'country' => 'Alemania'],
            ],
        ]);

        $stats = $car->comparables_stats_by_country;

        $this->assertCount(1, $stats['es']['items']);
        $this->assertEquals('BMW ES', $stats['es']['items'][0]['title']);
        $this->assertCount(1, $stats['de']['items']);
        $this->assertEquals('BMW DE', $stats['de']['items'][0]['title']);
    }
}
