<?php

namespace App\Services;

use App\Models\Cierre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Calculadora de KPIs de cierres — fuente única de verdad.
 *
 * §5 (auditoría 2026-08-12) — Elimina la duplicación entre KpiController (web)
 * e ImportValuationApiController::kpis() (API). Ambos deben usar esta clase.
 *
 * KPIs calculados:
 *  - precision_veredictos: % de veredictos "Comprar" que se vendieron
 *  - tiempo_hasta_venta: media de dias_hasta_venta en vendidos
 *  - desviacion_precio: media de desviacionPorcentaje en vendidos
 *  - tasa_falsos_positivos: % de "Comprar" que NO se vendieron
 */
class KpiCalculator
{
    /**
     * Calcula los 4 KPIs de una colección de cierres.
     *
     * Acepta Eloquent\Collection (de Eloquent) o Support\Collection (tests).
     */
    public static function calcular(Collection|SupportCollection $cierres): array
    {
        $positivos = $cierres->filter(
            fn (Cierre $c) => str_starts_with(strtolower($c->veredicto), 'comprar')
        );
        $vendidos = $cierres->where('estado', 'vendido');
        $vendidosPositivos = $vendidos->filter(
            fn (Cierre $c) => str_starts_with(strtolower($c->veredicto), 'comprar')
        );

        $desviaciones = $vendidos->map->desviacionPorcentaje()->filter(fn ($d) => $d !== null);
        $tiempos = $vendidos->pluck('dias_hasta_venta')->filter(fn ($d) => $d !== null);

        $totalPositivos = $positivos->count();

        return [
            'precision_veredictos' => $totalPositivos > 0
                ? round(($vendidosPositivos->count() / $totalPositivos) * 100, 1)
                : null,
            'tiempo_hasta_venta' => $tiempos->count() > 0 ? round($tiempos->avg(), 1) : null,
            'desviacion_precio' => $desviaciones->count() > 0 ? round($desviaciones->avg(), 2) : null,
            'tasa_falsos_positivos' => $totalPositivos > 0
                ? round((($totalPositivos - $vendidosPositivos->count()) / $totalPositivos) * 100, 1)
                : null,
            // Metadatos para el detalle
            '_counts' => [
                'total' => $cierres->count(),
                'positivos' => $totalPositivos,
                'vendidos' => $vendidos->count(),
                'vendidos_positivos' => $vendidosPositivos->count(),
                'con_tiempo' => $tiempos->count(),
                'con_desviacion' => $desviaciones->count(),
            ],
        ];
    }

    /**
     * Serie temporal de KPIs por mes (para gráficos de tendencia).
     *
     * @param  int  $months  número de meses hacia atrás (incl. actual)
     * @param  callable|null  $scopeQuery  recibe el Builder de Cierre para aplicar filtros extra
     */
    public static function historico(int $organizationId, int $months, ?callable $scopeQuery = null): array
    {
        $months = max(1, min(24, $months));
        $historico = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $p = now()->startOfMonth()->subMonths($i);

            $query = Cierre::where('organization_id', $organizationId)
                ->whereYear('fecha_investigacion', $p->year)
                ->whereMonth('fecha_investigacion', $p->month);

            if ($scopeQuery) {
                $scopeQuery($query);
            }

            $cierresMes = $query->get();
            $kpi = self::calcular($cierresMes);

            $historico[] = [
                'periodo' => $p->format('Y-m'),
                'precision_veredictos' => $kpi['precision_veredictos'],
                'tiempo_hasta_venta' => $kpi['tiempo_hasta_venta'],
                'tasa_falsos_positivos' => $kpi['tasa_falsos_positivos'],
                'volumen' => $cierresMes->count(),
            ];
        }

        return $historico;
    }
}
