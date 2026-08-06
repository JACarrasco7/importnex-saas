<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $alerts = Alert::query()
            ->when($request->input('type'), fn ($q, $t) => $q->where('alert_type', $t))
            ->when($request->input('resolved') !== null, fn ($q, $r) => $q->where('resolved', $r === '1'))
            ->when($request->input('filter') === 'pending', fn ($q) => $q->where('resolved', false))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Asegurar que el accesorio target_url se serializa en cada item del paginator
        $alerts->getCollection()->each->append('target_url');

        $types = ['car_stopped', 'client_no_contact', 'itv_pending', 'document_expired'];

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts,
            'types' => $types,
            'filters' => $request->only(['type', 'resolved', 'filter']),
        ]);
    }

    public function show(Alert $alert): Response
    {
        $alert->append('target_url');

        return Inertia::render('Alerts/Show', [
            'alert' => $alert,
        ]);
    }

    public function markResolved(Alert $alert): RedirectResponse
    {
        $alert->markAsResolved();

        return redirect()->route('alerts.index')
            ->with('success', 'Alert marked as resolved.');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()->route('alerts.index')
            ->with('success', 'Alert deleted successfully.');
    }

    /**
     * Endpoint ligero para polling del badge y toasts in-app.
     * Devuelve count + últimas 5 alertas pendientes. Pensado para
     * llamarse cada 30s desde el cliente sin WebSockets.
     */
    public function pending(Request $request): \Illuminate\Http\JsonResponse
    {
        $org = $request->user()?->organization;
        if (! $org) {
            return response()->json(['count' => 0, 'latest_id' => null, 'recent' => []]);
        }

        $alerts = Alert::query()
            ->where('organization_id', $org->id)
            ->where('resolved', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Asegurar que target_url viaja (util para el toast link)
        $alerts->each->append('target_url');

        return response()->json([
            'count' => $alerts->count(),
            'latest_id' => $alerts->first()?->id,
            'recent' => $alerts->map(fn ($a) => [
                'id' => $a->id,
                'alert_type' => $a->alert_type,
                'reference_type' => $a->reference_type,
                'reference_id' => $a->reference_id,
                'message' => $a->message,
                'target_url' => $a->target_url,
                'created_at' => $a->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Marca todas las alertas pendientes del org como resueltas.
     * Pensado para el botón "Marcar todas como leídas" del inbox.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $org = $request->user()?->organization;
        if (! $org) {
            return back()->with('error', 'No organization.');
        }

        $updated = Alert::query()
            ->where('organization_id', $org->id)
            ->where('resolved', false)
            ->update([
                'resolved' => true,
                'resolved_at' => now(),
            ]);

        return back()->with('success', "{$updated} alert(s) marked as resolved.");
    }
}
