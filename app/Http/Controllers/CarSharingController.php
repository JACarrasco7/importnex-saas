<?php

namespace App\Http\Controllers;

use App\Mail\TrackingSharedMail;
use App\Models\Car;
use App\Services\ContractService;
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

        // El coche debe estar en un estado del proceso ya iniciado. No usar
        // is_public_trackable aquí: exigiría token previo y bloquearía el primer share.
        if (! in_array($car->status, Car::TRACKABLE_STATUSES, true)) {
            return back()->with('error', __('tracking.shared.not_trackable_status'))
                ->withInput();
        }

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

    /** Crea (o reutiliza) un ContractAcceptance y devuelve la URL pública. */
    public function createContract(Request $request, Car $car): RedirectResponse
    {
        if (! $car->client_id) {
            return back()->with('error', __('tracking.contract.need_client'));
        }

        $contract = app(ContractService::class)->ensureForCar($car);

        return back()->with('success', __('tracking.contract.created', [
            'url' => $contract->public_url,
        ]));
    }
}
