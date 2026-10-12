<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->organization_id) {
            // Usuario sin organización → redirigir a onboarding
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
