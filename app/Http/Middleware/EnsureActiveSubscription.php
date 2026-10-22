<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SaaS: sin suscripción activa (ni prueba gratuita en vigor) la organización
 * solo puede ir a planes, facturación, su perfil y el onboarding.
 *
 * La organización propietaria de la plataforma (is_owner) nunca se bloquea.
 * Se desactiva con SUBSCRIPTION_ENFORCE=false (instalaciones sin cobro).
 */
class EnsureActiveSubscription
{
    /** Rutas accesibles aunque la suscripción haya caducado. */
    private const ALLOWED = [
        'subscriptions.*', 'billing.*', 'profile.*', 'onboarding.*',
        'organization.show', 'organization.edit', 'organization.update',
        'logout', 'locale.*', 'push.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->user()?->organization;

        if (! config('subscription.enforce', true)
            || ! $organization
            || $organization->isOwner()
            || $organization->hasActiveSubscription()
            || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        $message = __('Tu periodo de prueba ha terminado. Elige un plan para seguir usando la aplicación.');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'subscription_required',
                'upgrade_url' => route('subscriptions.index'),
            ], 402);
        }

        return redirect()->route('subscriptions.index')->with('error', $message);
    }
}
