<?php

namespace App\Http\Controllers;

use App\Models\Cierre;
use App\Services\KpiCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard de KPIs del skill importacion-vehiculos.
 *
 * §3.8 — Frontend sobre el endpoint de cierres (§3.5).
 * Filtros opcionales: brand (marca, denormalizada en cierres) y pais (plataforma/origen).
 */
class KpiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $periodo = $request->query('periodo', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', (string) $periodo)) {
            $periodo = now()->format('Y-m');
        }

        [$year, $month] = explode('-', $periodo);
        $brand = $request->query('brand');
        $pais = $request->query('pais');

        $base = Cierre::with('car')
            ->where('organization_id', $orgId)
            ->whereYear('fecha_investigacion', $year)
            ->whereMonth('fecha_investigacion', $month)
            ->when($brand, fn ($q) => $q->where('brand', $brand))
            ->when($pais, fn ($q) => $q->where('plataforma', $pais));

        $cierres = $base->orderByDesc('fecha_investigacion')->orderByDesc('id')->get();

        $kpi = KpiCalculator::calcular($cierres);
        $counts = $kpi['_counts'];

        // Tendencia últimos 6 meses (mismos filtros)
        $scope = fn ($q) => $q
            ->when($brand, fn ($q2) => $q2->where('brand', $brand))
            ->when($pais, fn ($q2) => $q2->where('plataforma', $pais));
        $tendencia = array_map(fn ($t) => [
            'periodo' => $t['periodo'],
            'precision' => $t['precision_veredictos'],
            'volumen' => $t['volumen'],
        ], KpiCalculator::historico($orgId, 6, $scope));

        // Selectores: marcas y plataformas (de los cierres de la organización)
        $marcas = Cierre::where('organization_id', $orgId)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        $paises = Cierre::where('organization_id', $orgId)
            ->whereNotNull('plataforma')
            ->where('plataforma', '!=', '')
            ->distinct()
            ->orderBy('plataforma')
            ->pluck('plataforma');

        return Inertia::render('Kpis/Index', [
            'periodo' => $periodo,
            'filtros' => [
                'brand' => $brand,
                'pais' => $pais,
            ],
            'marcas' => $marcas,
            'paises' => $paises,
            'kpis' => [
                'precision_veredictos' => [
                    'valor' => $kpi['precision_veredictos'],
                    'objetivo' => 80,
                    'tipo' => 'mayor_mejor',
                    'unidad' => '%',
                    'detalle' => "{$counts['vendidos_positivos']} vendidos de {$counts['positivos']} veredictos 'Comprar'",
                ],
                'tiempo_hasta_venta' => [
                    'valor' => $kpi['tiempo_hasta_venta'],
                    'objetivo' => 15,
                    'tipo' => 'menor_mejor',
                    'unidad' => 'días',
                    'detalle' => $counts['con_tiempo'] > 0 ? "Media de {$counts['con_tiempo']} ventas" : 'Sin ventas en el periodo',
                ],
                'desviacion_precio' => [
                    'valor' => $kpi['desviacion_precio'],
                    'objetivo' => 5,
                    'tipo' => 'menor_mejor',
                    'unidad' => '%',
                    'detalle' => 'Desviación media (precio_final vs precio_objetivo)',
                ],
                'tasa_falsos_positivos' => [
                    'valor' => $kpi['tasa_falsos_positivos'],
                    'objetivo' => 20,
                    'tipo' => 'menor_mejor',
                    'unidad' => '%',
                    'detalle' => 'Veredictos "Comprar" que no se vendieron',
                ],
            ],
            'tendencia' => $tendencia,
            'cierres' => $cierres->map(fn (Cierre $c) => [
                'id' => $c->id,
                'coche_id' => $c->coche_id,
                'car_id' => $c->car_id,
                'brand' => $c->brand ?: $c->car?->brand,
                'model' => $c->model ?: $c->car?->model,
                'plataforma' => $c->plataforma,
                'fecha_investigacion' => $c->fecha_investigacion?->format('Y-m-d'),
                'veredicto' => $c->veredicto,
                'precio_objetivo' => $c->precio_objetivo,
                'fecha_venta' => $c->fecha_venta?->format('Y-m-d'),
                'precio_final' => $c->precio_final,
                'cliente' => $c->cliente,
                'dias_hasta_venta' => $c->dias_hasta_venta,
                'estado' => $c->estado,
                'desviacion_pct' => $c->desviacionPorcentaje(),
            ]),
        ]);
    }
}
