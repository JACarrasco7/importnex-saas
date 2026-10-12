<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
    /**
     * Show a paginated list of publicly available cars.
     *
     * GET /marketplace
     */
    public function index(Request $request): Response
    {
        // Criterio de visibilidad pública: scope único en el modelo
        // (`Car::scopePublicMarketplace()`) — toggle del operador + org pública
        // + coche no vendido/descartado. Antes exigía `status=Delivered`
        // (coche YA entregado), así que la web nunca mostraba nada.
        $cars = Car::query()
            ->publicMarketplace()
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('brand', 'like', "%$s%")
                        ->orWhere('model', 'like', "%$s%")
                        ->orWhere('vin', 'like', "%$s%");
                });
            })
            ->when($request->input('verdict'), fn ($q, $v) => $q->where('verdict', $v))
            ->when($request->input('traffic_light'), fn ($q, $tl) => $q->where('traffic_light', $tl))
            ->when($request->input('min_price'), fn ($q, $p) => $q->where('purchase_price', '>=', $p))
            ->when($request->input('max_price'), fn ($q, $p) => $q->where('purchase_price', '<=', $p))
            ->when($request->input('year_min'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) >= ?', [$y]))
            ->when($request->input('year_max'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) <= ?', [$y]))
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Load photos and organization for each car
        $cars->load(['photos', 'organization']);

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        // Opciones de filtro cacheadas (invalidadas por CarObserver al cambiar marketplace)
        $filterOptions = Cache::remember('marketplace.filter_options', 1800, function () {
            $base = fn () => Car::query()->publicMarketplace();

            return [
                'brands' => $base()->distinct()->orderBy('brand')->pluck('brand')->values(),
                'fuels' => $base()->whereNotNull('fuel')->distinct()->orderBy('fuel')->pluck('fuel')->values(),
                'transmissions' => $base()->whereNotNull('transmission')->distinct()->orderBy('transmission')->pluck('transmission')->values(),
                'doors' => $base()->whereNotNull('doors')->distinct()->orderBy('doors')->pluck('doors')->values(),
                'colors' => $base()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color')->values(),
            ];
        });

        return Inertia::render('Public/MarketplaceIndex', [
            'cars' => $cars,
            'verdicts' => $verdicts,
            'lights' => $lights,
            'filterOptions' => $filterOptions,
            'filters' => $request->only(['search', 'verdict', 'traffic_light', 'min_price', 'max_price', 'year_min', 'year_max']),
        ]);
    }

    /**
     * Compare up to 4 marketplace cars side by side.
     *
     * GET /marketplace/compare?ids=1,2,3
     */
    public function compare(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        $cars = Car::query()
            ->publicMarketplace()
            ->whereIn('id', $ids->all())
            ->with(['photos', 'organization'])
            ->get();

        return Inertia::render('Public/MarketplaceCompare', [
            'cars' => $cars,
            'requestedIds' => $ids->all(),
        ]);
    }

    /**
     * Show a single car from the marketplace.
     *
     * GET /marketplace/{car}
     */
    public function show(Car $car): Response
    {
        // Verificar visibilidad pública con el MISMO criterio que el listado
        // (scope único en Car — antes estaba copiado aquí y se desincronizó).
        $visible = Car::query()->publicMarketplace()->whereKey($car->getKey())->exists();
        if (! $visible) {
            abort(404);
        }

        $car->increment('marketplace_views');

        $car->load(['photos', 'organization']);

        // Pre-compute derived data for the enriched valuation UI
        $car->researchGaps;       // touch accessor
        $car->comparablesStats;    // touch accessor
        $car->calculateTotalCost(); // touch method

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $car,
            'derived' => [
                'total_cost' => $car->calculateTotalCost(),
                'iedmt' => $car->calculateIEDMT(),
                'research_gaps' => $car->researchGaps,
                'comparables_stats' => $car->comparablesStats,
            ],
        ]);
    }
}
