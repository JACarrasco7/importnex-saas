<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarExpense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Financial dashboard with aggregated KPIs and time-series for charts.
 */
class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $orgId = $request->user()->organization_id;

        // Aggregate per-car totals
        $cars = Car::where('organization_id', $orgId)
            ->whereNotNull('purchase_price')
            ->with(['expenses'])
            ->get();

        $totalInvestment = $cars->sum('purchase_price');
        $totalTransport = $cars->sum('transport');
        $totalTaxes = $cars->sum(fn ($c) => ($c->itv_fee ?? 0) + ($c->coc_fee ?? 0) + ($c->dgt_fees ?? 0));
        $totalFees = $cars->sum('professional_fees');

        // Aggregate actual expenses
        $actualExpenses = CarExpense::whereIn('car_id', $cars->pluck('id'))
            ->selectRaw('concept, SUM(actual) as total')
            ->groupBy('concept')
            ->get();

        $estimatedExpenses = CarExpense::whereIn('car_id', $cars->pluck('id'))
            ->selectRaw('concept, SUM(estimated) as total')
            ->groupBy('concept')
            ->get();

        // Per-status breakdown
        $byStatus = $cars->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('purchase_price'),
            ];
        });

        // Monthly series (last 6 months of cars created)
        $monthly = Car::where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(purchase_price) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Finance/Index', [
            'kpis' => [
                'totalInvestment' => $totalInvestment,
                'totalTransport' => $totalTransport,
                'totalTaxes' => $totalTaxes,
                'totalFees' => $totalFees,
                'grandTotal' => $totalInvestment + $totalTransport + $totalTaxes + $totalFees,
                'carsCount' => $cars->count(),
                'averagePurchase' => $cars->count() > 0 ? $totalInvestment / $cars->count() : 0,
            ],
            'actualExpenses' => $actualExpenses,
            'estimatedExpenses' => $estimatedExpenses,
            'byStatus' => $byStatus,
            'monthly' => $monthly,
        ]);
    }
}
