<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Map view of all cars with location data, grouped by city for clustering.
 */
class CarMapController extends Controller
{
    public function index(Request $request): Response
    {
        $orgId = $request->user()->organization_id;

        $cars = Car::where('organization_id', $orgId)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get(['id', 'brand', 'model', 'year', 'status', 'traffic_light', 'purchase_price', 'lat', 'lng', 'city']);

        // Group by city for clustering
        $clusters = [];
        foreach ($cars as $car) {
            $city = $car->city ?: 'Unknown';
            if (! isset($clusters[$city])) {
                $clusters[$city] = [
                    'city' => $city,
                    'lat' => $car->lat,
                    'lng' => $car->lng,
                    'count' => 0,
                    'total_value' => 0,
                    'cars' => [],
                ];
            }
            $clusters[$city]['count']++;
            $clusters[$city]['total_value'] += $car->purchase_price;
            $clusters[$city]['cars'][] = [
                'id' => $car->id,
                'brand' => $car->brand,
                'model' => $car->model,
                'year' => $car->year,
                'status' => $car->status,
                'traffic_light' => $car->traffic_light,
                'purchase_price' => $car->purchase_price,
            ];
        }

        return Inertia::render('Cars/Map', [
            'clusters' => array_values($clusters),
            'totalCars' => $cars->count(),
            'totalCities' => count($clusters),
        ]);
    }
}
