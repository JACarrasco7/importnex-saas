<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Protege el acceso a Telescope para que solo sea accesible:
 * - En entornos no-production (local, staging)
 * - Por usuarios con role 'Super Admin'
 * - O si el usuario tiene permiso 'telescope.access' (futuro)
 */
class TelescopeAccess
{
    public function handle(Request $request, Closure $next)
    {
        // En producción, denegar acceso siempre
        if (app()->environment('production')) {
            abort(403, 'Telescope is not available in production');
        }

        // El usuario debe estar autenticado
        if (! auth()->check()) {
            abort(401);
        }

        $user = auth()->user();

        // Permitir solo a Super Admin
        if ($user->role !== 'Super Admin') {
            abort(403, 'You do not have permission to access Telescope');
        }

        return $next($request);
    }
}
