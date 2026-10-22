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
            // Usuario sin organización → crearla (el onboarding la necesita).
            return redirect()->route('organization.create');
        }

        return $next($request);
    }
}
