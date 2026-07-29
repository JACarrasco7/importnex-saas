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
     */
    public function verifySync(Request $request, Car $car, CarVerificationService $service): RedirectResponse
    {
        $car->update(['status' => 'Verifying']);

        $result = $service->verify($car);

        if (! $result['success']) {
            $car->update(['status' => 'Located']);
            return back()->with('error', 'Verification failed: ' . ($result['error'] ?? 'unknown'));
        }

        $analysis = $result['analysis'];

        $car->update([
            'status' => 'Pending review',
            'traffic_light' => $analysis['traffic_light'] ?? 'neutral',
            'valuation' => $analysis['valuation'] ?? null,
            'recommendation' => $analysis['recommendation'] ?? null,
            'red_flags' => $analysis['red_flags'] ?? [],
            'tips' => $analysis['tips'] ?? [],
        ]);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'Verification completed.');
    }

    /**
     * Apply the AI suggestions to the car (Save button).
     */
    public function apply(Car $car): RedirectResponse
    {
        $car->update(['status' => 'Valuing']);

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'AI suggestions applied.');
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
