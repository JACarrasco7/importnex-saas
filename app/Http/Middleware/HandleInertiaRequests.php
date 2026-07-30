<?php

namespace App\Http\Middleware;

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
        $locale = 'en';

        if ($user = $request->user()) {
            $pendingAlertsCount = \App\Models\Alert::where('organization_id', $user->organization_id)
                ->where('resolved', false)
                ->count();

            $pendingCarRequestsCount = \App\Models\CarRequest::where('organization_id', $user->organization_id)
                ->where('status', 'pending')
                ->count();

            $organization = $user->organization;
            if ($organization) {
                $planUsage = $organization->planUsage();
                $currentPlan = [
                    'name' => $organization->plan,
                    'has_active_subscription' => $organization->hasActiveSubscription(),
                ];
            }

            $locale = $user->locale ?? 'en';
        } else {
            // For guests, try to get locale from session or cookie
            $locale = $request->session()->get('locale') ??
                      $request->cookie('locale') ??
                      'en';
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
        ];
    }
}
