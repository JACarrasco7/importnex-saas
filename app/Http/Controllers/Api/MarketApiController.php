<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketApiController extends Controller
{
    /**
     * Listado del mapa de mercado (GET /api/market) — puente chat→SaaS.
     * Filtros: categoria · veredicto · mejor_mercado · min_hueco · con_negocio.
     * Orden por defecto: hueco_pct desc.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->attributes->get('import_org')?->id;

        $models = MarketModel::query()
            ->deOrganizacion($orgId)
            ->when($request->input('categoria'), fn ($q, $c) => $q->where('categoria', $c))
            ->when($request->input('segmento'), fn ($q, $s) => $q->where('segmento', $s))
            ->when($request->input('tipo_cliente'), fn ($q, $t) => $q->porTipoCliente($t))
            ->when($request->input('veredicto'), fn ($q, $v) => $q->where('veredicto', $v))
            ->when($request->input('mejor_mercado'), fn ($q, $m) => $q->where('mejor_mercado', $m))
            ->when($request->boolean('con_negocio'), fn ($q) => $q->where('hueco_neto_pct', '>', 0))
            ->when($request->input('min_hueco') !== null, fn ($q) => $q->where('hueco_pct', '>=', (float) $request->input('min_hueco')))
            ->orderByDesc('hueco_pct')
            ->get();

        return response()->json([
            'status' => 'ok',
            'total' => $models->count(),
            'models' => $models,
        ]);
    }

    /**
     * #9 — Estadísticas agregadas del mapa (GET /api/market/stats).
     */
    public function stats(Request $request): JsonResponse
    {
        $orgId = $request->attributes->get('import_org')?->id;
        $cacheKey = 'market:stats:'.($orgId ?? 'global');

        $stats = Cache::remember($cacheKey, 1800, function () use ($orgId) {
            $base = fn () => MarketModel::query()->deOrganizacion($orgId);

            return [
                'total' => $base()->count(),
                'verdes' => $base()->verdes()->count(),
                'oportunidades' => $base()->oportunidades()->count(),
                'caducados' => $base()->caducados()->count(),
                'hueco_medio' => round((float) $base()->whereNotNull('hueco_pct')->avg('hueco_pct'), 1),
                'hueco_medio_segmento' => $base()
                    ->whereNotNull('segmento')
                    ->groupBy('segmento')
                    ->selectRaw('segmento, round(avg(hueco_pct),1) as hueco_medio, count(*) as n')
                    ->orderByDesc('hueco_medio')
                    ->get(),
                'por_veredicto' => $base()
                    ->groupBy('veredicto')
                    ->selectRaw('veredicto, count(*) as n')
                    ->pluck('n', 'veredicto'),
            ];
        });

        return response()->json([
            'status' => 'ok',
            'stats' => $stats,
        ]);
    }
}
