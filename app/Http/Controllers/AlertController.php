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

        $query = Alert::query()
            ->when($typeFilter, fn ($q, $t) => $q->where('alert_type', $t))
            ->when($filter === 'pending', fn ($q) => $q->active())
            ->when($filter === 'snoozed', fn ($q) => $q->snoozed())
            ->when($filter === 'resolved', fn ($q) => $q->resolved())
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $query->getCollection()->each->append('target_url');

        // Tipos presentes en BD (sin N+1): agrupamos por alert_type y contamos
        $typesAvailable = Alert::query()
            ->selectRaw('alert_type, COUNT(*) as count')
            ->groupBy('alert_type')
            ->orderByDesc('count')
            ->pluck('count', 'alert_type')
            ->toArray();

        // Contadores por filtro (badge por sección)
        $counts = [
            'pending' => Alert::query()->active()->count(),
            'snoozed' => Alert::query()->snoozed()->count(),
            'resolved' => Alert::query()->resolved()->count(),
        ];

        return Inertia::render('Alerts/Index', [
            'alerts' => $query,
            'types' => array_keys($typesAvailable),
            'typesAvailable' => $typesAvailable,
            'counts' => $counts,
            'filters' => $request->only(['type', 'resolved', 'filter']),
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
