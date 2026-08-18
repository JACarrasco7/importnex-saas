<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * #6 — API pública del catálogo bajo pedido (sin auth).
 * Devuelve solo modelos publicados de organizaciones públicas/globales.
 * GET /api/public/market
 */
class PublicMarketController extends Controller
{
    public function index(): JsonResponse
    {
        $models = MarketModel::query()
            ->where('publicar_en_catalogo', true)
            ->where(function ($q) {
                $q->whereNull('organization_id')
                    ->orWhereHas('organization', fn ($org) => $org->where('is_public', true));
            })
            ->orderByDesc('hueco_pct')
            ->get()
            ->map(fn (MarketModel $m) => [
                'slug' => $m->slug,
                'modelo' => $m->modelo,
                'version' => $m->version,
                'categoria' => $m->categoria,
                'segmento' => $m->segmento,
                'rango_precio' => $m->rango_precio,
                'tipo_cliente' => $m->tipo_cliente,
                'precio_desde_de' => (int) $m->precio_desde_de,
                'mediana_de' => (int) $m->mediana_de,
                'mediana_es' => (int) $m->mediana_es,
                'hueco_pct' => (float) $m->hueco_pct,
                'hueco_neto_pct' => $m->hueco_neto_pct !== null ? (float) $m->hueco_neto_pct : null,
                'mejor_mercado' => $m->mejor_mercado,
                'veredicto' => $m->veredicto,
                'foto_url' => $m->foto_url,
            ]);

        return response()->json([
            'status' => 'ok',
            'total' => $models->count(),
            'catalogo' => $models,
        ]);
    }

    /**
     * Stats públicas del catálogo (GET /api/public/market/stats).
     */
    public function stats(): JsonResponse
    {
        $stats = Cache::remember('market:public-stats', 1800, function () {
            $base = fn () => MarketModel::query()
                ->where('publicar_en_catalogo', true)
                ->where(function ($q) {
                    $q->whereNull('organization_id')
                        ->orWhereHas('organization', fn ($org) => $org->where('is_public', true));
                });

            return [
                'total' => $base()->count(),
                'verdes' => $base()->verdes()->count(),
                'oportunidades' => $base()->oportunidades()->count(),
                'hueco_medio' => round((float) $base()->whereNotNull('hueco_pct')->avg('hueco_pct'), 1),
                'por_segmento' => $base()
                    ->whereNotNull('segmento')
                    ->groupBy('segmento')
                    ->selectRaw('segmento, round(avg(hueco_pct),1) as hueco_medio, count(*) as n')
                    ->orderByDesc('hueco_medio')
                    ->get(),
            ];
        });

        return response()->json([
            'status' => 'ok',
            'stats' => $stats,
        ]);
    }
}
