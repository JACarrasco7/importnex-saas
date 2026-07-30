<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kanban board for cars organized by status.
 * Allows quick drag-and-drop style status changes.
 */
class CarKanbanController extends Controller
{
    /**
     * Columns (statuses) shown on the board.
     */
    private const COLUMNS = [
        'Located' => 'Located',
        'Valuing' => 'Valuing',
        'Offered' => 'Offered',
        'Reserved' => 'Reserved',
        'Purchased' => 'Purchased',
        'In_transit' => 'In transit',
        'Processing' => 'Processing',
        'Delivered' => 'Delivered',
    ];

    public function index(Request $request): Response
    {
        $orgId = $request->user()->organization_id;

        $cars = Car::where('organization_id', $orgId)
            ->whereIn('status', self::COLUMNS)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get(['id', 'brand', 'model', 'year', 'status', 'traffic_light', 'purchase_price', 'city']);

        $columns = [];
        foreach (self::COLUMNS as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'cars' => $cars->where('status', $key)->values(),
            ];
        }

        return Inertia::render('Cars/Kanban', [
            'columns' => $columns,
            'totalCars' => $cars->count(),
            'totalValue' => $cars->sum('purchase_price'),
        ]);
    }

    /**
     * Move a car to a different status (AJAX endpoint).
     */
    public function move(Request $request, Car $car): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(self::COLUMNS))],
        ]);

        $car->update(['status' => $request->input('status')]);

        return back()->with('success', "Car moved to {$request->input('status')}");
    }
}
