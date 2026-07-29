<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        // Single query for cars KPIs using CASE WHEN aggregations
        $carStats = DB::table('cars')
            ->whereNull('deleted_at')
            ->where('organization_id', $orgId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status IN ('Located', 'Valuing', 'Offered', 'Reserved') THEN 1 ELSE 0 END) as for_sale,
                SUM(CASE WHEN status = 'Purchased' THEN 1 ELSE 0 END) as purchased,
                SUM(CASE WHEN traffic_light = 'green' THEN 1 ELSE 0 END) as green,
                SUM(CASE WHEN traffic_light = 'amber' THEN 1 ELSE 0 END) as amber,
                SUM(CASE WHEN traffic_light = 'red' THEN 1 ELSE 0 END) as red,
                SUM(CASE WHEN traffic_light = 'neutral' THEN 1 ELSE 0 END) as neutral,
                SUM(purchase_price) as total_cost
            ")
            ->first();

        // Single query for clients KPIs
        $clientStats = DB::table('clients')
            ->whereNull('deleted_at')
            ->where('organization_id', $orgId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('New', 'Briefing', 'Quote sent', 'Negotiating', 'Order signed', 'In process') THEN 1 ELSE 0 END) as active
            ")
            ->first();

        $stats = [
            'cars_total' => (int) $carStats->total,
            'cars_active' => (int) $carStats->active,
            'cars_for_sale' => (int) $carStats->for_sale,
            'cars_purchased' => (int) $carStats->purchased,
            'clients_total' => (int) $clientStats->total,
            'clients_active' => (int) $clientStats->active,
            'contacts_total' => Contact::where('organization_id', $orgId)->count(),
            'alerts_pending' => Alert::where('organization_id', $orgId)
                ->where('resolved', false)
                ->count(),
            'total_cars_cost' => (float) $carStats->total_cost,
        ];

        $recentCars = Car::where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'brand', 'model', 'year', 'status', 'traffic_light', 'purchase_price']);

        $trafficLights = [
            'green' => (int) $carStats->green,
            'amber' => (int) $carStats->amber,
            'red' => (int) $carStats->red,
            'neutral' => (int) $carStats->neutral,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentCars' => $recentCars,
            'trafficLights' => $trafficLights,
            'organization' => $user->organization,
        ]);
    }
}
