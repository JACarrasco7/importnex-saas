<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Organization/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $org = Organization::create([
            'name' => $request->name,
            'plan' => 'starter',
            'trial_ends_at' => now()->addDays(config('subscription.trial_days')),
        ]);

        $user = auth()->user();
        $user->update([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        return redirect()->route('dashboard');
    }

    public function show(Organization $organization): Response
    {
        $this->authorizeAccess($organization);

        return Inertia::render('Organization/Show', [
            'organization' => $organization->load('users'),
        ]);
    }

    public function edit(Organization $organization): Response
    {
        $this->authorizeAccess($organization);

        return Inertia::render('Organization/Edit', [
            'organization' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($organization);

        // Only owners can edit organization settings
        if (! auth()->user()->isOwner()) {
            abort(403, 'Only owners can edit the organization.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization->update($request->only('name'));

        return redirect()->route('organization.show', $organization);
    }

    protected function authorizeAccess(Organization $organization): void
    {
        if ($organization->id !== auth()->user()->organization_id) {
            abort(403, 'You do not have access to this organization.');
        }
    }
}
