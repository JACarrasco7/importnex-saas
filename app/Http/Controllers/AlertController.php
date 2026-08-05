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
}
