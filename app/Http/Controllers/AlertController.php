<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->input('filter', 'pending');
        $typeFilter = $request->input('type');
        $org = $request->user()?->organization;

        // N8: Filtrar por preferencias del org (null prefs = todo activo)
        $allTypes = ['car_request', 'car_stale', 'client_no_contact', 'verification_failed', 'verification_completed'];
        $disabledTypes = [];
        if ($org) {
            $disabledTypes = array_values(array_filter($allTypes, fn ($t) => ! $org->isAlertTypeEnabled($t)));
        }

        $query = Alert::query()
            ->when($typeFilter, fn ($q, $t) => $q->where('alert_type', $t))
            ->when($filter === 'pending', fn ($q) => $q->active())
            ->when($filter === 'snoozed', fn ($q) => $q->snoozed())
            ->when($filter === 'resolved', fn ($q) => $q->resolved())
            ->when($filter === 'pending' && $disabledTypes, fn ($q) => $q->whereNotIn('alert_type', $disabledTypes))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $query->getCollection()->each->append('target_url');

        $typesAvailable = Alert::query()
            ->selectRaw('alert_type, COUNT(*) as count')
            ->groupBy('alert_type')
            ->orderByDesc('count')
            ->pluck('count', 'alert_type')
            ->toArray();

        // Filtrar chips de tipo a los no silenciados
        if ($disabledTypes) {
            $typesAvailable = array_diff_key($typesAvailable, array_flip($disabledTypes));
        }

        $counts = [
            'pending' => Alert::query()->active()->when($disabledTypes, fn ($q) => $q->whereNotIn('alert_type', $disabledTypes))->count(),
            'snoozed' => Alert::query()->snoozed()->count(),
            'resolved' => Alert::query()->resolved()->count(),
        ];

        return Inertia::render('Alerts/Index', [
            'alerts' => $query,
            'types' => array_keys($typesAvailable),
            'typesAvailable' => $typesAvailable,
            'counts' => $counts,
            'filters' => $request->only(['type', 'resolved', 'filter']),
            'allAlertTypes' => $allTypes,
            'disabledAlertTypes' => $disabledTypes,
        ]);
    }

    public function snooze(Request $request, Alert $alert): RedirectResponse
    {
        $request->validate([
            'hours' => ['required', 'integer', 'min:1', 'max:168'], // max 7 días
        ]);

        $alert->snooze((int) $request->input('hours'));

        return back()->with('success', "Alert snoozed for {$request->input('hours')}h.");
    }

    public function unsnooze(Alert $alert): RedirectResponse
    {
        $alert->unsnooze();

        return back()->with('success', 'Alert reactivated.');
    }

    /**
     * Toggle de preferencia de notificación por tipo (N8).
     * Pensado para un switch inline en /alerts ("silenciar este tipo").
     */
    public function togglePreference(Request $request, string $alertType): RedirectResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $org = $request->user()?->organization;
        if (! $org) {
            abort(403, 'No organization.');
        }

        $prefs = $org->notification_preferences ?? [];
        $prefs[$alertType] = (bool) $request->input('enabled');

        $org->update(['notification_preferences' => $prefs]);

        return back()->with('success', $request->boolean('enabled')
            ? "Notifications enabled for {$alertType}."
            : "Notifications muted for {$alertType}.");
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
    public function pending(Request $request): JsonResponse
    {
        $org = $request->user()?->organization;
        if (! $org) {
            return response()->json(['count' => 0, 'latest_id' => null, 'recent' => []]);
        }

        $alerts = Alert::query()
            ->where('organization_id', $org->id)
            ->active()
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
            ->active()
            ->update([
                'resolved' => true,
                'resolved_at' => now(),
            ]);

        return back()->with('success', "{$updated} alert(s) marked as resolved.");
    }
}
