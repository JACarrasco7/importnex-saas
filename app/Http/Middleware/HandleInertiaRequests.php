<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\CarRequest;
use App\Models\UserOnboardingProgress;
use App\Services\Ai\AiProviderRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $pendingAlertsCount = 0;
        $pendingCarRequestsCount = 0;
        $planUsage = null;
        $currentPlan = null;
        $locale = 'es';
        $aiSettings = null;
        $onboardingShare = null;

        if ($user = $request->user()) {
            $pendingAlertsCount = Alert::where('organization_id', $user->organization_id)
                ->where('resolved', false)
                ->count();

            $pendingCarRequestsCount = CarRequest::where('organization_id', $user->organization_id)
                ->where('status', 'pending')
                ->count();

            // Onboarding progress (Sprint 2.1) — null when missing or completed.
            $onboardingProgress = UserOnboardingProgress::where('user_id', $user->id)->first();
            $onboardingShare = $onboardingProgress && ! $onboardingProgress->is_completed
                ? [
                    'step_organization_created' => (bool) $onboardingProgress->step_organization_created,
                    'step_first_vehicle_added' => (bool) $onboardingProgress->step_first_vehicle_added,
                    'step_team_invited' => (bool) $onboardingProgress->step_team_invited,
                    'step_plan_selected' => (bool) $onboardingProgress->step_plan_selected,
                    'current_step' => (int) $onboardingProgress->current_step,
                    'progress' => $onboardingProgress->progress ?? 0,
                    'is_completed' => false,
                    'skipped_at' => $onboardingProgress->skipped_at?->toIso8601String(),
                ]
                : null;

            $organization = $user->organization;
            if ($organization) {
                $planUsage = $organization->planUsage();
                $currentPlan = [
                    'name' => $organization->plan,
                    'is_owner' => $organization->isOwner(),
                    'has_active_subscription' => $organization->hasActiveSubscription(),
                ];

                // Resolve AI provider label without instantiating the class
                $registry = app(AiProviderRegistry::class);
                $providerLabel = $organization->ai_provider
                    ? ($registry->get($organization->ai_provider)?->label() ?? $organization->ai_provider)
                    : null;
                $aiSettings = [
                    'provider' => $organization->ai_provider,
                    'provider_label' => $providerLabel,
                    'model' => $organization->ai_model,
                    'has_key' => $organization->hasAiConfigured(),
                ];
            }

            $locale = $user->locale ?? 'es';
        } else {
            // For guests, try to get locale from session or cookie
            $locale = $request->session()->get('locale') ??
                      $request->cookie('locale') ??
                      'es';
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'csrfToken' => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'pending_alerts_count' => $pendingAlertsCount,
            'pending_car_requests_count' => $pendingCarRequestsCount,
            'planUsage' => $planUsage,
            'currentPlan' => $currentPlan,
            'locale' => $locale,
            'aiSettings' => $aiSettings,
            'onboardingProgress' => $onboardingShare,
            // Configuracion regional (moneda + locale) para formateo en el frontend.
            // Por defecto EUR + es si la organizacion todavia no tiene valor.
            'formatting' => [
                'currency' => $organization?->currency ?? 'EUR',
                'locale' => $organization?->locale ?? $locale,
                'decimals' => 2,
            ],
        ];
    }
}
