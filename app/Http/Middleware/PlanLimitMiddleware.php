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

        $resourceName = ['cars' => 'coches', 'clients' => 'clientes', 'contacts' => 'contactos', 'users' => 'usuarios'][$resource] ?? $resource;
        $limit = $organization->limitFor($resource);
        $current = $organization->currentCount($resource);
        $planName = config('subscription.plans.'.$organization->plan.'.name', $organization->plan);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => "Has alcanzado el límite de {$limit} {$resourceName} de tu plan {$planName}.",
                'error' => 'plan_limit_reached',
                'resource' => $resource,
                'current' => $current,
                'limit' => $limit,
                'plan' => $organization->plan,
                'upgrade_url' => route('subscriptions.index'),
            ], 403);
        }

        return back()
            ->with('error', "Has alcanzado el límite de {$limit} {$resourceName} de tu plan {$planName}. Mejora tu plan para añadir más.")
            ->withErrors([
                'plan_limit' => "Límite de {$limit} {$resourceName} alcanzado en el plan {$planName}.",
            ]);
    }
}
