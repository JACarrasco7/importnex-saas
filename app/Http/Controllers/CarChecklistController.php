<?php

namespace App\Http\Controllers;

use App\Enums\OrderEventType;
use App\Models\Car;
use App\Models\CarChecklist;
use App\Services\ImportOrders\OrderTimeline;
use App\Support\CarChecklistDefinitions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CarChecklistController extends Controller
{
    /**
     * Toggle the completed state of a checklist item.
     *
     * POST /cars/{car}/checklists/{checklist}/toggle
     * Body: { completed?: bool } — if absent, toggles current state.
     */
    public function toggle(Request $request, Car $car, CarChecklist $checklist): RedirectResponse
    {
        abort_unless($checklist->car_id === $car->id, 404);

        $explicit = $request->boolean('completed');
        $newState = $request->has('completed') ? $explicit : ! $checklist->completed;

        $checklist->completed = $newState;
        $checklist->completed_at = $newState ? now() : null;
        $checklist->save();

        // Hito completado → novedad visible en el portal del cliente.
        if ($newState && $checklist->kind === 'milestone' && ($order = $car->activeImportOrder())) {
            $name = collect(CarChecklistDefinitions::milestones())->firstWhere('key', $checklist->item_key)['name'] ?? $checklist->item_key;
            app(OrderTimeline::class)->record($order, OrderEventType::MilestoneCompleted, 'Hito completado: '.$name, [
                'user' => $request->user(),
                'visible' => true,
                'meta' => ['checklist_id' => $checklist->id, 'item_key' => $checklist->item_key],
            ]);
        }

        return back();
    }
}
