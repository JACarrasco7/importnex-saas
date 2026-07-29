<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trip Planner — group cars by origin city to plan transport runs.
 * Useful for batching multiple cars from the same dealer/city into one transport.
 */
class TripPlannerController extends Controller
{
    public function index(Request $request): Response
    {
        $orgId = $request->user()->organization_id;

        $cars = Car::where('organization_id', $orgId)
            ->whereIn('status', ['Purchased', 'In_transit'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('city')
            ->get(['id', 'brand', 'model', 'year', 'status', 'city', 'lat', 'lng', 'transport', 'purchase_price']);

        // Group by origin city
        $trips = [];
        foreach ($cars as $car) {
            $city = $car->city ?: 'Unknown';
            if (! isset($trips[$city])) {
                $trips[$city] = [
                    'city' => $city,
                    'lat' => $car->lat,
                    'lng' => $car->lng,
                    'cars' => [],
                    'count' => 0,
                    'total_transport' => 0,
                    'total_value' => 0,
                ];
            }
            $trips[$city]['cars'][] = $car;
            $trips[$city]['count']++;
            $trips[$city]['total_transport'] += $car->transport ?? 0;
            $trips[$city]['total_value'] += $car->purchase_price;
        }

        // Sort by total transport cost desc
        usort($trips, fn ($a, $b) => $b['total_transport'] <=> $a['total_transport']);

        // Suggest: batch trips with same city = potential 30% transport savings
        foreach ($trips as &$trip) {
            $trip['potential_savings'] = $trip['count'] > 1
                ? round($trip['total_transport'] * 0.30, 2)
                : 0;
            $trip['recommendation'] = $trip['count'] > 1
                ? "Batch {$trip['count']} cars from {$trip['city']} to save ~€{$trip['potential_savings']} on transport."
                : "Single car — no batch savings available.";
        }

        return Inertia::render('Trips/Index', [
            'trips' => $trips,
            'totalCars' => $cars->count(),
            'totalTransport' => $cars->sum('transport'),
        ]);
    }
}
