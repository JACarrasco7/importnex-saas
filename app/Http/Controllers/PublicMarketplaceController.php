<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
    /**
     * Whitelisted filters for marketplace index.
     * Cualquier parametro que no este aqui se descarta antes de tocar la query.
     */
    private const FILTER_RULES = [
        'search' => ['nullable', 'string', 'max:200'],
        'verdict' => ['nullable', 'string', 'in:Buy,Buy if price drops,Doubtful,Discard'],
        'traffic_light' => ['nullable', 'string', 'in:green,amber,red,neutral'],
        'min_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        'max_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        'year_min' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        'year_max' => ['nullable', 'integer', 'min:1900', 'max:2100'],
    ];

    /**
     * Show a paginated list of publicly available cars.
     *
     * GET /marketplace
     */
    public function index(Request $request): Response
    {
        // Validar inputs SIN lanzar 422: el filtro invalido simplemente
        // se descarta y la query devuelve todos los coches publicos.
        $validator = Validator::make($request->all(), self::FILTER_RULES);
        $filters = $validator->valid();

        // Bounds para el frontend (min/max aceptables en inputs)
        $filterBounds = [
            'price' => ['min' => 0, 'max' => 9999999],
            'year' => ['min' => 1900, 'max' => (int) date('Y') + 1],
        ];

        $cars = Car::query()
            ->whereHas('organization', function ($query) {
                $query->where('is_public', true);
            })
            ->where('is_marketplace', true)
            ->whereIn('status', ['Delivered'])
            ->whereIn('verdict', ['Buy', 'Buy if price drops'])
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('brand', 'like', "%$s%")
                        ->orWhere('model', 'like', "%$s%")
                        ->orWhere('vin', 'like', "%$s%");
                });
            })
            ->when($filters['verdict'] ?? null, fn ($q, $v) => $q->where('verdict', $v))
            ->when($filters['traffic_light'] ?? null, fn ($q, $tl) => $q->where('traffic_light', $tl))
            ->when(isset($filters['min_price']), fn ($q, $p) => $q->where('purchase_price', '>=', $p))
            ->when(isset($filters['max_price']), fn ($q, $p) => $q->where('purchase_price', '<=', $p))
            ->when(isset($filters['year_min']), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) >= ?', [$y]))
            ->when(isset($filters['year_max']), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) <= ?', [$y]))
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $cars->load(['photos', 'organization']);

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        $requestUrl = null;
        $publicOrg = Organization::where('is_public', true)->first();
        if ($publicOrg && $publicOrg->slug) {
            $requestUrl = route('public.car-request.index', ['slug' => $publicOrg->slug]);
        }

        return Inertia::render('Public/MarketplaceIndex', [
            'cars' => $cars,
            'verdicts' => $verdicts,
            'lights' => $lights,
            'requestUrl' => $requestUrl,
            'filters' => array_intersect_key($filters, array_flip([
                'search', 'verdict', 'traffic_light', 'min_price', 'max_price', 'year_min', 'year_max',
            ])),
            'filterBounds' => $filterBounds,
        ]);
    }

    /**
     * Show a single car from the marketplace.
     *
     * GET /marketplace/{car}
     */
    public function show(Request $request, Car $car): Response
    {
        // Verify this car should be publicly visible
        if (! $car->organization || ! $car->organization->is_public ||
            ! $car->is_marketplace ||
            ! in_array($car->status, ['Delivered']) ||
            ! in_array($car->verdict, ['Buy', 'Buy if price drops'])) {
            abort(404);
        }

        // Marketplace item 7: contador de vistas con deduplicación por sesión.
        // Una sesión (cookie mc-viewed-{car_id}) cuenta 1 vez; misma sesión no infla.
        $cookieName = 'mc-viewed-'.$car->id;
        if (! $request->cookie($cookieName)) {
            $car->increment('marketplace_views');
            cookie()->queue(cookie($cookieName, '1', 60 * 24)); // 24h
        }

        $car->load(['photos', 'organization']);

        // Pre-compute derived data for the enriched valuation UI
        $car->researchGaps;
        $car->comparablesStats;
        $car->calculateTotalCost();

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $car,
            'shareUrl' => $request->fullUrl(),
            'derived' => [
                'total_cost' => $car->calculateTotalCost(),
                'iedmt' => $car->calculateIEDMT(),
                'research_gaps' => $car->researchGaps,
                'comparables_stats' => $car->comparablesStats,
            ],
        ]);
    }
}
