<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valida que Car::calculateIEDMT() usa los coeficientes correctos del Anexo IV
 * (Orden HAC/1501/2025) según config/iedmt.php — single source of truth.
 *
 * Auditoría #1 (2026-08-12): los coeficientes hardcodeados eran incorrectos
 * en 5 de 12 valores (año 10 usaba 14% en vez de 17%).
 */
class IedmtCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCar(string $year, int $co2, float $taxBase, bool $boe = false): Car
    {
        $org = Organization::factory()->create();

        return Car::factory()->create([
            'organization_id' => $org->id,
            'year' => "01/{$year}",
            'co2' => $co2,
            'manual_tax_base' => $taxBase,
            'new_price' => $taxBase,
            'boe_confirmed' => $boe,
        ]);
    }

    public function test_coefficients_come_from_config(): void
    {
        $coefs = config('iedmt.coeficientes_antiguedad');

        // Verifica la tabla del skill costes.md §IEDMT (Anexo IV)
        $expected = [
            0 => 1.00,   // ≤1
            1 => 0.84,   // 1-2
            2 => 0.67,   // 2-3
            3 => 0.56,   // 3-4
            4 => 0.47,   // 4-5
            5 => 0.39,   // 5-6
            6 => 0.34,   // 6-7
            7 => 0.28,   // 7-8
            8 => 0.24,   // 8-9
            9 => 0.19,   // 9-10
            10 => 0.17,  // 10-11  (antes 0.14 — bug)
            11 => 0.13,  // 11-12
            12 => 0.10,  // >12
        ];

        $this->assertSame($expected, $coefs);
    }

    public function test_iedmt_for_10_year_old_car_uses_17_percent(): void
    {
        $car = $this->makeCar('2016', 165, 30000);
        // Año 10 → 0.17 (corregido, antes 0.14), CO2 165 → 9.75%
        $expected = 30000 * 0.17 * 0.0975;

        $this->assertEquals($expected, (float) $car->calculateIEDMT());
    }

    public function test_iedmt_for_new_car_uses_100_percent(): void
    {
        $car = $this->makeCar((string) now()->year, 180, 25000);
        // Año 0 → 1.00, CO2 180 → 9.75%
        $expected = 25000 * 1.00 * 0.0975;

        $this->assertEquals($expected, (float) $car->calculateIEDMT());
    }

    public function test_iedmt_for_old_car_over_12_years_clamps_to_10_percent(): void
    {
        $car = $this->makeCar('2005', 100, 20000);
        // 21 años → clamp a 0.10, CO2 100 → 0%
        $this->assertEquals(0.0, $car->calculateIEDMT());
    }

    public function test_iedmt_zero_when_no_co2(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'year' => '01/2019',
            'co2' => 0,
            'manual_tax_base' => 20000,
        ]);

        $this->assertEquals(0, $car->calculateIEDMT());
    }

    public function test_co2_bands(): void
    {
        $base = 10000;
        // Año 2019 → 7 años → coeficiente 0.28
        $coef = 0.28;

        // ≤120 → 0%
        $this->assertEquals(0, $this->makeCar('2019', 110, $base)->calculateIEDMT());

        // 121-159 → 4.75%
        $this->assertEquals($base * $coef * 0.0475, $this->makeCar('2019', 150, $base)->calculateIEDMT());

        // 160-199 → 9.75%
        $this->assertEquals($base * $coef * 0.0975, $this->makeCar('2019', 170, $base)->calculateIEDMT());

        // ≥200 → 14.75%
        $this->assertEquals($base * $coef * 0.1475, $this->makeCar('2019', 210, $base)->calculateIEDMT());
    }

    public function test_boe_confirmed_uses_new_price_as_base(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'year' => '01/2021', // 5 años → 0.39
            'co2' => 150,
            'manual_tax_base' => 10000,
            'new_price' => 12000,
            'boe_confirmed' => true,
        ]);

        // Con boe_confirmed usa new_price (12000), no manual_tax_base (10000)
        $this->assertEquals(12000 * 0.39 * 0.0475, (float) $car->calculateIEDMT());
    }
}
