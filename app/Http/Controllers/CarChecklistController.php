<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarChecklist;
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

        return back();
    }
}
