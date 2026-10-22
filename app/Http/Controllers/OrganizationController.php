<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\ListsModelsInterface;
use App\Support\Brand;
use App\Support\PublicSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (auth()->user()->organization_id) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Organization/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->organization_id) {
            return redirect()->route('dashboard');
        }

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

        return redirect()->route('onboarding.index');
    }

    public function show(Organization $organization): Response
    {
        $this->authorizeAccess($organization);

        $brand = $organization->brand();

        return Inertia::render('Organization/Show', [
            'organization' => $organization,
            'members' => $organization->users()->orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at']),
            'canManage' => auth()->user()->isOwner(),
            'usersLimit' => $organization->isOwner() ? null : $organization->limitFor('users'),
            'apiTokenActive' => filled($organization->api_token_hash),
            'newApiToken' => session('api_token'),
            'companyMissing' => $brand->missingForContracts(),
            'siteUrl' => PublicSite::urlFor($organization),
            'requestFormUrl' => PublicSite::urlFor($organization) ? $organization->public_url : null,
        ]);
    }

    public function edit(Organization $organization, AiProviderRegistry $registry): Response
    {
        $this->authorizeAccess($organization);

        return Inertia::render('Organization/Edit', [
            'organization' => $organization,
            'aiProviders' => $registry->options(),
            'aiKeyConfigured' => filled($organization->ai_api_key),
            'webBaseUrl' => url('/web'),
            'siteUrl' => PublicSite::urlFor($organization),
            'siteBlocked' => $organization->is_public && ! PublicSite::isAvailable($organization),
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
            // Web pública por empresa (/web/{slug}).
            'is_public' => ['sometimes', 'boolean'],
            'slug' => ['sometimes', 'required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/',
                Rule::unique('organizations', 'slug')->ignore($organization->id), Rule::notIn(PublicSite::RESERVED_SLUGS)],
            'currency' => ['nullable', Rule::in(['EUR', 'USD', 'GBP'])],
            'locale' => ['nullable', Rule::in(['es', 'en'])],
            'ai_provider' => ['nullable', Rule::in(array_merge([''], array_column($registry->options(), 'key')))],
            'ai_model' => ['nullable', 'string', 'max:128'],
            'ai_api_key' => ['nullable', 'string', 'max:512'],
            // OneSignal (push web + móvil + email + SMS)
            'onesignal_app_id' => ['nullable', 'string', 'max:255'],
            'onesignal_api_key' => ['nullable', 'string', 'max:512'],
            // N7: webhook (Slack/Discord/Teams)
            'notification_webhook_url' => ['nullable', 'url', 'max:512'],
            'notification_webhook_types' => ['nullable', 'array'],
            'notification_webhook_types.*' => ['string', 'max:64'],
            // N8: preferencias por tipo (map alert_type => bool)
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['boolean'],
            // Datos de empresa (contrato, portal, emails, fichas públicas).
            'company' => ['nullable', 'array'],
            'company.legal_name' => ['nullable', 'string', 'max:255'],
            'company.tax_id' => ['nullable', 'string', 'max:32'],
            'company.address' => ['nullable', 'string', 'max:255'],
            'company.city' => ['nullable', 'string', 'max:120'],
            'company.phone' => ['nullable', 'string', 'max:32'],
            'company.phone_2' => ['nullable', 'string', 'max:32'],
            'company.whatsapp' => ['nullable', 'string', 'max:32'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.website' => ['nullable', 'string', 'max:255'],
            'company.iban' => ['nullable', 'string', 'max:42'],
            'company.iban_holder' => ['nullable', 'string', 'max:255'],
            'company.iban_bank' => ['nullable', 'string', 'max:120'],
            'company.fee_label' => ['nullable', 'string', 'max:60'],
            'company.tagline' => ['nullable', 'string', 'max:160'],
            'company.fee_base' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'company.fee_premium' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'company.fee_threshold' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ], [
            'slug.regex' => 'Usa solo minúsculas, números y guiones (sin espacios ni acentos).',
            'slug.unique' => 'Esa dirección ya la usa otra empresa.',
            'slug.not_in' => 'Esa dirección está reservada. Elige otra.',
            'slug.min' => 'La dirección debe tener al menos 3 caracteres.',
        ]);

        $orgData = [
            'name' => $request->name,
            ...($request->has('is_public') ? ['is_public' => $request->boolean('is_public')] : []),
            ...($request->filled('slug') ? ['slug' => $request->input('slug')] : []),
            'currency' => $request->input('currency', $organization->currency ?? 'EUR'),
            'locale' => $request->input('locale', $organization->locale ?? 'es'),
        ];

        // OneSignal — save empty string as null
        if ($request->has('onesignal_app_id')) {
            $orgData['onesignal_app_id'] = $request->filled('onesignal_app_id')
                ? $request->input('onesignal_app_id')
                : null;
        }
        if ($request->has('onesignal_api_key')) {
            $orgData['onesignal_api_key'] = $request->filled('onesignal_api_key')
                ? $request->input('onesignal_api_key')
                : null;
        }

        // N7: webhook — save empty string as null to avoid storing ''
        if ($request->has('notification_webhook_url')) {
            $orgData['notification_webhook_url'] = $request->filled('notification_webhook_url')
                ? $request->input('notification_webhook_url')
                : null;
        }
        if ($request->has('notification_webhook_types')) {
            $orgData['notification_webhook_types'] = $request->input('notification_webhook_types') ?: null;
        }
        // N8: preferencias — merge para no perder tipos nuevos
        if ($request->has('notification_preferences')) {
            $existing = $organization->notification_preferences ?? [];
            $orgData['notification_preferences'] = array_merge($existing, $request->input('notification_preferences') ?: []);
        }
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

        if ($request->has('company')) {
            $orgData['company'] = array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_intersect_key((array) $request->input('company'), array_flip(Brand::FIELDS))),
                fn ($v) => filled($v),
            );
        }
        if ($request->hasFile('logo')) {
            $orgData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $organization->update($orgData);

        // La web pública cachea la marca y los filtros de la empresa.
        Cache::forget('marketplace.context.v2');
        PublicMarketplaceController::forgetFilterCaches($organization->id);

        return redirect()->route('organization.show', $organization)
            ->with('success', 'Cambios guardados.');
    }

    /**
     * List models available for the given provider + API key.
     * Uses the typed key, or falls back to the organization's stored key.
     */
    public function aiModels(Request $request, Organization $organization, AiProviderRegistry $registry): JsonResponse
    {
        $this->authorizeAccess($organization);

        $data = $request->validate([
            'provider' => ['required', Rule::in(array_column($registry->options(), 'key'))],
            'api_key' => ['nullable', 'string', 'max:512'],
        ]);

        $apiKey = $data['api_key'] ?: $organization->ai_api_key;
        if (! $apiKey) {
            return response()->json(['success' => false, 'error' => 'Introduce una API key primero.'], 422);
        }

        $provider = $registry->get($data['provider']);
        if (! $provider instanceof ListsModelsInterface) {
            return response()->json(['success' => false, 'error' => 'Este proveedor no soporta listado de modelos.'], 422);
        }

        $result = $provider->listModels($apiKey);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    protected function authorizeAccess(Organization $organization): void
    {
        if ($organization->id !== auth()->user()->organization_id) {
            abort(403, 'You do not have access to this organization.');
        }
    }
}
