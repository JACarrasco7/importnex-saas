<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContactLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the per-client contact history (calls, emails, WhatsApp messages).
 */
class ClientContactLogController extends Controller
{
    public function index(Client $client): Response
    {
        $logs = $client->contactLogs()
            ->orderBy('contact_date', 'desc')
            ->paginate(20);

        return Inertia::render('Clients/ContactLogs', [
            'client' => $client,
            'logs' => $logs,
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'contact_date' => 'required|date',
            'channel' => 'required|in:phone,email,whatsapp,in_person,sms,other',
            'summary' => 'required|string|max:2000',
        ]);

        $client->contactLogs()->create([
            'contact_date' => $request->input('contact_date'),
            'channel' => $request->input('channel'),
            'summary' => $request->input('summary'),
            'organization_id' => $client->organization_id,
        ]);

        return back()->with('success', 'Contact logged.');
    }

    public function destroy(Client $client, ClientContactLog $log): RedirectResponse
    {
        if ($log->client_id !== $client->id) {
            abort(403);
        }

        $log->delete();

        return back()->with('success', 'Log deleted.');
    }
}
