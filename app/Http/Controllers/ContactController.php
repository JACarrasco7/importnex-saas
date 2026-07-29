<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $contacts = Contact::query()
            ->when($request->input('search'), function($q, $s) {
                $q->where(function($sub) use ($s) {
                    $sub->where('name', 'like', "%$s%")
                        ->orWhere('email', 'like', "%$s%")
                        ->orWhere('phone', 'like', "%$s%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $clients = Client::select('id', 'name')->get();
        return Inertia::render('Contacts/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable',
        ]);

        $tags = $request->input('tags');
        $tagsArray = is_string($tags) && $tags !== ''
            ? array_map('trim', explode(',', $tags))
            : (is_array($tags) ? $tags : []);

        Contact::create([
            ...$request->only(['name', 'phone', 'email', 'city', 'notes']),
            'tags' => $tagsArray,
            'organization_id' => auth()->user()->organization_id,
        ]);

        return redirect()->route('contacts.index')
            ->with('success', 'Contact created successfully.');
    }

    public function show(Contact $contact): Response
    {
        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    public function edit(Contact $contact): Response
    {
        $clients = Client::select('id', 'name')->get();
        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable',
        ]);

        $tags = $request->input('tags');
        $tagsArray = is_string($tags) && $tags !== ''
            ? array_map('trim', explode(',', $tags))
            : (is_array($tags) ? $tags : []);

        $contact->update([
            ...$request->only(['name', 'phone', 'email', 'city', 'notes']),
            'tags' => $tagsArray,
        ]);

        return redirect()->route('contacts.index')
            ->with('success', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();
        return redirect()->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }
}
