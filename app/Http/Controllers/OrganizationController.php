<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\Ai\AiProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function edit(Organization $organization, AiProviderRegistry $registry): Response
    {
        $this->authorizeAccess($organization);

        return Inertia::render('Organization/Edit', [
            'organization' => $organization,
            'aiProviders' => $registry->options(),
        ]);
    }

    public function update(Request $request, Organization $organization, AiProviderRegistry $registry): RedirectResponse
    {
        $this->authorizeAccess($organization);

        // Only owners can edit organization settings
        if (! auth()->user()->isOwner()) {
            abort(403, 'Only owners can edit the organization.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ai_provider' => ['nullable', Rule::in(array_merge([''], array_column($registry->options(), 'key')))],
            'ai_model' => ['nullable', 'string', 'max:128'],
            'ai_api_key' => ['nullable', 'string', 'max:512'],
        ]);

        $orgData = ['name' => $request->name];
        if ($request->filled('ai_provider')) {
            $orgData['ai_provider'] = $request->ai_provider;
            $orgData['ai_model'] = $request->ai_model ?: null;
            // Only overwrite the key if user typed something new
            if ($request->filled('ai_api_key')) {
                $orgData['ai_api_key'] = $request->ai_api_key;
            }
        } else {
            // Provider disabled — clear it but keep model for reference
            $orgData['ai_provider'] = null;
        }

        $organization->update($orgData);

        return redirect()->route('organization.show', $organization)
            ->with('success', 'Settings saved.');
    }

    protected function authorizeAccess(Organization $organization): void
    {
        if ($organization->id !== auth()->user()->organization_id) {
            abort(403, 'You do not have access to this organization.');
        }
    }
}
