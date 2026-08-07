<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $clients = Client::query()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%$s%")
                        ->orWhere('contact_info', 'like', "%$s%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $statuses = ['New', 'Briefing', 'Quote sent', 'Negotiating', 'Order signed', 'In process', 'Delivered'];

        return Inertia::render('Clients/Index', [
            'clients' => Inertia::defer(fn () => $clients),
            'statuses' => $statuses,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        if ($org->limitReached('clients')) {
            return back()->with('error', "You've reached your plan's client limit. Please upgrade your subscription.");
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'looking_for' => 'nullable|string|max:255',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
            'status' => ['required', Rule::in(['New', 'Briefing', 'Quote sent', 'Negotiating', 'Order signed', 'In process', 'Delivered'])],
            'notes' => 'nullable|string',
        ], [
            'budget_max.gte' => 'Budget max must be greater than or equal to budget min.',
        ]);

        Client::create([
            ...$request->only(['name', 'contact_info', 'looking_for', 'budget_min', 'budget_max', 'status', 'notes']),
            'organization_id' => auth()->user()->organization_id,
        ]);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(Client $client): Response
    {
        $client->load(['cars', 'contactLogs', 'contacts']);

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'looking_for' => 'nullable|string|max:255',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
            'status' => ['required', Rule::in(['New', 'Briefing', 'Quote sent', 'Negotiating', 'Order signed', 'In process', 'Delivered'])],
            'notes' => 'nullable|string',
        ]);

        $client->update($request->only(['name', 'contact_info', 'looking_for', 'budget_min', 'budget_max', 'status', 'notes']));

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
