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
 * Acepta el token de API de una organización (cada empresa genera el suyo
 * en Ajustes) o el token global de la plataforma (solo uso interno).
 *
 * Fallos:
 *  - 401 si el token es inválido
 *  - 503 si no es de ninguna organización y no hay token global configurado
 *  - 404 si el token global pide una organización que no existe
 */
class ImportToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $given = (string) $request->header('X-Import-Token');
        if ($given === '') {
            return response()->json(['error' => 'Invalid or missing X-Import-Token.'], 401);
        }

        // 1) Token propio de una organización (Ajustes → API): solo ve y
        //    escribe en SU organización; ?org= se ignora.
        $org = Organization::findByApiToken($given);

        // 2) Token global de la plataforma (IMPORTNEX_CHAT_IMPORT_TOKEN): uso
        //    interno del operador. Elige organización con ?org=<slug|nombre>;
        //    por defecto, la organización propietaria.
        if (! $org) {
            $expected = (string) config('services.importnex_chat.token');
            if ($expected === '') {
                return response()->json(['error' => 'Import bridge not configured on this server.'], 503);
            }
            if (! hash_equals($expected, $given)) {
                return response()->json(['error' => 'Invalid or missing X-Import-Token.'], 401);
            }

            $wanted = (string) $request->query('org', '');
            $org = $wanted !== ''
                ? Organization::query()->where('slug', $wanted)->orWhere('name', $wanted)->first()
                : (Organization::query()->where('is_owner', true)->orderBy('id')->first()
                    ?? Organization::query()->orderBy('id')->first());

            if (! $org) {
                Log::warning('ImportToken: organización no encontrada', ['org' => $wanted]);

                return response()->json(['error' => 'Organization not found.'], 404);
            }
        }

        $request->attributes->set('import_org', $org);

        return $next($request);
    }
}
