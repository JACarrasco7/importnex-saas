<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación del puente chat→servidor (X-Import-Token).
 *
 * Auditoría 3 (#11) — Centraliza el bloque expected/given/hash_equals
 * que se repetía en 6 métodos de ImportValuationApiController.
 *
 * Añade al request:
 *  - $request->attributes->set('import_org', Organization)
 *
 * Fallos:
 *  - 503 si el token no está configurado en el servidor
 *  - 401 si el token es inválido
 */
class ImportToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.importnex_chat.token');

        if (blank($expected)) {
            Log::warning('ImportToken: IMPORTNEX_CHAT_IMPORT_TOKEN no configurado.');

            return response()->json(['error' => 'Import bridge not configured on this server.'], 503);
        }

        $given = $request->header('X-Import-Token');
        if (! $given || ! hash_equals($expected, $given)) {
            return response()->json(['error' => 'Invalid or missing X-Import-Token.'], 401);
        }

        // Resolver organización (query ?org= o default)
        $orgName = $request->query('org') ?: 'JJ Import Motors';
        $org = Organization::where('name', $orgName)->first();
        if (! $org) {
            return response()->json(['error' => "Organization not found: {$orgName}"], 404);
        }

        $request->attributes->set('import_org', $org);

        return $next($request);
    }
}
