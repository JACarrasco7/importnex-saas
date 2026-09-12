<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Parsea el body de un request como JSON array, con multiples fallbacks.
 *
 * Por que existe: Laravel recibe POST/PUT/PATCH con Content-Type application/json
 * de tres formas distintas segun el cliente (curl, Guzzle, axios, fetch). El
 * controller tenia 4 lineas de fallback repetidas en 4 endpoints - esto centraliza
 * el comportamiento y los mensajes de error.
 */
trait ParsesImportPayload
{
    /**
     * Lee el body del request como array. Devuelve [array, null] si OK o
     * [null, JsonResponse(422)] si no se pudo parsear.
     *
     * @return array{0: array, 1: JsonResponse|null}
     */
    protected function parseImportPayload(Request $request): array
    {
        $payload = $request->json()->all();
        if (! is_array($payload) || empty($payload)) {
            $payload = json_decode($request->getContent() ?: '', true);
        }
        if (! is_array($payload) || empty($payload)) {
            $rawBody = $request->getContent() ?: file_get_contents('php://input');
            $payload = $rawBody ? json_decode($rawBody, true) : null;
        }

        if (! is_array($payload) || empty($payload)) {
            return [null, response()->json(['error' => 'Empty or invalid JSON body.'], 422)];
        }

        return [$payload, null];
    }
}
