<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Http\Request;
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
        // Define what makes a car "publicly available"
        // From organizations that are public, with delivered status and positive verdict
        $cars = Car::query()
            ->whereHas('organization', function ($query) {
                $query->where('is_public', true);
            })
            ->where('is_marketplace', true) // Solo coches marcados para publicar
            ->whereIn('status', ['Delivered']) // Only show delivered cars
            ->whereIn('verdict', ['Buy', 'Buy if price drops']) // Only show positive verdicts
            ->when($request->input('search'), function($q, $s) {
                $q->where(function($sub) use ($s) {
                    $sub->where('brand', 'like', "%$s%")
                        ->orWhere('model', 'like', "%$s%")
                        ->orWhere('vin', 'like', "%$s%");
                });
            })
            ->when($request->input('verdict'), fn($q, $v) => $q->where('verdict', $v))
            ->when($request->input('traffic_light'), fn($q, $tl) => $q->where('traffic_light', $tl))
            ->when($request->input('min_price'), fn($q, $p) => $q->where('purchase_price', '>=', $p))
            ->when($request->input('max_price'), fn($q, $p) => $q->where('purchase_price', '<=', $p))
            ->when($request->input('year_min'), fn($q, $y) => $q->whereRaw("SUBSTRING(year, -4) >= ?", [$y]))
            ->when($request->input('year_max'), fn($q, $y) => $q->whereRaw("SUBSTRING(year, -4) <= ?", [$y]))
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Load photos and organization for each car
        $cars->load(['photos', 'organization']);

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        return Inertia::render('Public/MarketplaceIndex', [
            'cars' => $cars,
            'verdicts' => $verdicts,
            'lights' => $lights,
            'filters' => $request->only(['search', 'verdict', 'traffic_light', 'min_price', 'max_price', 'year_min', 'year_max']),
        ]);
    }

    /**
     * Show a single car from the marketplace.
     *
     * GET /marketplace/{car}
     */
    public function show(Car $car): Response
    {
        // Verify this car should be publicly visible
        if (!$car->organization || !$car->organization->is_public ||
            !$car->is_marketplace ||
            !in_array($car->status, ['Delivered']) ||
            !in_array($car->verdict, ['Buy', 'Buy if price drops'])) {
            abort(404);
        }

        $car->load(['photos', 'organization']);

        // Pre-compute derived data for the enriched valuation UI
        $car->researchGaps;       // touch accessor
        $car->comparablesStats;    // touch accessor
        $car->calculateTotalCost(); // touch method

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $car,
            'derived' => [
                'total_cost'           => $car->calculateTotalCost(),
                'iedmt'                => $car->calculateIEDMT(),
                'research_gaps'        => $car->researchGaps,
                'comparables_stats'    => $car->comparablesStats,
            ],
        ]);
    }
}
