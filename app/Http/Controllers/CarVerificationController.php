<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyCarWithAI;
use App\Models\Car;
use App\Services\CarVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarVerificationController extends Controller
{
    /**
     * Show verification form/result for a car.
     */
    public function show(Car $car): Response
    {
        return Inertia::render('Cars/Verify', [
            'car' => $car,
        ]);
    }

    /**
     * Dispatch the AI verification job.
     */
    public function verify(Request $request, Car $car): RedirectResponse
    {
        $car->update(['status' => 'Verifying']);

        VerifyCarWithAI::dispatch($car);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'Verification queued. We will notify you when done.');
    }

    /**
     * Synchronous verification (when queue is not available).
     *
     * NO toca ningún campo existente del coche: solo guarda el análisis de la
     * IA en `ai_analysis_json` + `ai_verified_at`. El usuario revisa y aplica
     * campo por campo desde el modal (apply). Esto garantiza que "verificar"
     * nunca cambie nada de lo que ya está sin consentimiento explícito.
     */
    public function verifySync(Request $request, Car $car, CarVerificationService $service): RedirectResponse
    {
        $result = $service->verify($car);

        if (! $result['success']) {
            return back()->with('error', 'Verification failed: '.($result['error'] ?? 'unknown'));
        }

        $analysis = $result['analysis'];
        $analysisFull = $result['analysis_full'] ?? $analysis;

        // Solo persistimos la investigación. El status y todos los campos se
        // quedan como están; el usuario decide qué aplicar en el modal.
        $car->update([
            'ai_analysis_json' => $analysisFull,
            'ai_verified_at' => now(),
        ]);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'Verification completed. Review the suggestions and apply what you want.');
    }

    /**
     * Apply selected AI suggestions to the car.
     *
     * The user picks per-field which AI suggestions to apply (via a modal on
     * the Verify page). Only the chosen fields are written; everything else
     * keeps its current value.
     */
    public function apply(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'fields' => 'required|array',
            'fields.*' => 'string|in:description,purchase_price,manual_tax_base,tips,red_flags,verdict,verdict_confidence,verdict_reasoning,market_avg,market_min,market_max,estimated_saving,pros,cons,valuation',
        ]);

        $analysis = $car->ai_analysis_json;
        if (! is_array($analysis) || empty($analysis)) {
            return back()->with('error', 'No AI analysis available for this car. Run the verification first.');
        }

        $fillable = [];
        $touched = [];

        foreach ($data['fields'] as $field) {
            $proposed = $analysis[$field] ?? null;
            if ($proposed === null || $proposed === '' || $proposed === []) {
                continue;
            }
            $fillable[$field] = $proposed;
            $touched[] = $field;
        }

        if (empty($fillable)) {
            return back()->with('error', 'No fields selected or suggestions were empty.');
        }

        // Move the car out of Pending review and reset the analysis snapshot so
        // the modal stops offering the same suggestions after they're consumed.
        $fillable['status'] = 'Valuing';
        $fillable['ai_analysis_json'] = null;

        $car->update($fillable);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'AI suggestions applied to: '.implode(', ', $touched).'.');
    }

    /**
     * Discard the AI suggestions.
     */
    public function discard(Car $car): RedirectResponse
    {
        $car->update([
            'status' => 'Located',
            'valuation' => null,
            'recommendation' => null,
            'red_flags' => [],
            'tips' => [],
            'traffic_light' => 'neutral',
        ]);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'AI suggestions discarded.');
    }
}
