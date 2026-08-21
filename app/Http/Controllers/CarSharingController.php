<?php

namespace App\Http\Controllers;

use App\Mail\TrackingSharedMail;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Acciones para compartir/revocar el seguimiento público de un coche.
 *
 * - share(): genera el token si no existe, persiste `expected_delivery_date`
 *   opcional, marca `tracking_shared_at`, envía email al cliente con la URL.
 * - revoke(): marca `tracking_revoked_at` (no borra token para auditoría).
 * - regenerate(): rota el token (si se filtró, invalida enlace antiguo).
 *
 * Multi-tenant: aplica `organization` middleware a las rutas.
 */
class CarSharingController extends Controller
{
    public function share(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
        ]);

        $url = $car->shareTracking($data['email'] ?? null);

        if (! empty($data['expected_delivery_date'])) {
            $car->forceFill(['expected_delivery_date' => $data['expected_delivery_date']])->save();
        }

        if (! empty($data['email'])) {
            Mail::to($data['email'])->send(new TrackingSharedMail($car, $url));
        }

        return back()->with('success', __('tracking.shared.success', ['url' => $url]));
    }

    public function revoke(Car $car): RedirectResponse
    {
        $car->revokeTracking();

        return back()->with('success', __('tracking.shared.revoked'));
    }

    public function regenerate(Car $car): RedirectResponse
    {
        $car->regenerateTrackingToken();

        return back()->with('success', __('tracking.shared.regenerated'));
    }
}
