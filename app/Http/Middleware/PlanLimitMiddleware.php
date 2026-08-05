<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();
        $organization = $user?->organization;

        if (! $organization) {
            return $next($request);
        }

        if ($organization->isOwner() || ! $organization->limitReached($resource)) {
            return $next($request);
        }

        $resourceName = rtrim($resource, 's');
        $limit = $organization->limitFor($resource);
        $current = $organization->currentCount($resource);
        $planName = config('subscription.plans.'.$organization->plan.'.name', $organization->plan);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => "You have reached the limit of {$limit} {$resourceName}s on your {$planName} plan.",
                'error' => 'plan_limit_reached',
                'resource' => $resource,
                'current' => $current,
                'limit' => $limit,
                'plan' => $organization->plan,
                'upgrade_url' => route('subscriptions.index'),
            ], 403);
        }

        return back()
            ->with('error', "You've reached the limit of {$limit} {$resourceName}s on your {$planName} plan. Upgrade to add more.")
            ->withErrors([
                'plan_limit' => "Limit of {$limit} {$resourceName}s reached on {$planName} plan.",
            ]);
    }
}
