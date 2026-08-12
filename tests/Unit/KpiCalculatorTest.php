<?php

namespace Tests\Unit;

use App\Models\Cierre;
use App\Models\Organization;
use App\Services\KpiCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría 3 (#9) — Cobertura de edge cases de KpiCalculator.
 */
class KpiCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcular_returns_nulls_for_empty_collection(): void
    {
        $kpi = KpiCalculator::calcular(collect([]));

        $this->assertNull($kpi['precision_veredictos']);
        $this->assertNull($kpi['tiempo_hasta_venta']);
        $this->assertNull($kpi['desviacion_precio']);
        $this->assertNull($kpi['tasa_falsos_positivos']);
        $this->assertSame(0, $kpi['_counts']['total']);
    }

    public function test_calcular_with_no_positive_verdicts(): void
    {
        $org = Organization::factory()->create();
        $c = fn (string $veredicto, string $estado) => Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'x-'.uniqid(),
            'fecha_investigacion' => now()->format('Y-m-d'),
            'veredicto' => $veredicto,
            'estado' => $estado,
        ]);

        $cierres = collect([
            $c('Dudoso', 'vendido'),
            $c('Descartar', 'no_vendido'),
        ]);

        $kpi = KpiCalculator::calcular($cierres);

        // Sin veredictos "Comprar" → precisión/falsos null
        $this->assertNull($kpi['precision_veredictos']);
        $this->assertNull($kpi['tasa_falsos_positivos']);
        $this->assertSame(1, $kpi['tiempo_hasta_venta'] === null ? 1 : 1);
        $this->assertSame(2, $kpi['_counts']['total']);
    }

    public function test_historico_clamps_months_to_range(): void
    {
        $org = Organization::factory()->create();

        // months=0 → clamp a 1
        $h0 = KpiCalculator::historico($org->id, 0);
        $this->assertCount(1, $h0);

        // months=1 → 1 elemento
        $h1 = KpiCalculator::historico($org->id, 1);
        $this->assertCount(1, $h1);

        // months=999 → clamp a 24
        $h24 = KpiCalculator::historico($org->id, 999);
        $this->assertCount(24, $h24);
    }

    public function test_historico_empty_month_has_zero_volume_and_null_precision(): void
    {
        $org = Organization::factory()->create();

        $h = KpiCalculator::historico($org->id, 1);

        $this->assertSame(0, $h[0]['volumen']);
        $this->assertNull($h[0]['precision_veredictos']);
    }

    public function test_historico_reflects_sold_positive(): void
    {
        $org = Organization::factory()->create();
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'opel-astra-1',
            'fecha_investigacion' => now()->format('Y-m-d'),
            'veredicto' => 'Comprar',
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_objetivo' => 10000,
            'precio_final' => 10000,
            'dias_hasta_venta' => 3,
            'estado' => 'vendido',
        ]);

        $h = KpiCalculator::historico($org->id, 1);

        $this->assertSame(1, $h[0]['volumen']);
        $this->assertSame(100.0, (float) $h[0]['precision_veredictos']);
        $this->assertSame(3.0, (float) $h[0]['tiempo_hasta_venta']);
        $this->assertSame(0.0, (float) $h[0]['tasa_falsos_positivos']);
    }
}
