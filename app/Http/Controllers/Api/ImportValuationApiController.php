<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ValuationImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint sin sesión para que el chat suba un informe de valoración
 * directamente al servidor, sin pasar por el formulario web.
 *
 * Autenticación: token compartido en la cabecera X-Import-Token, comparado
 * contra config('services.importnex_chat.token') (env IMPORTNEX_CHAT_IMPORT_TOKEN).
 * No es un usuario ni una sesión — es un secreto de un solo propósito para
 * esta integración, generado con `openssl rand -hex 32` y pegado en el .env
 * del servidor. No des este token de alta como password de ningún usuario.
 *
 * Uso desde el chat (curl):
 *   curl -X POST https://dev.aktive.cloud/importnexcore/api/import-valuation \
 *     -H "X-Import-Token: <token>" \
 *     -H "Content-Type: application/json" \
 *     --data @informe.json
 */
class ImportValuationApiController extends Controller
{
    public function store(Request $request, ValuationImporter $importer): JsonResponse
    {
        $expected = config('services.importnex_chat.token');

        if (blank($expected)) {
            Log::warning('ImportValuationApiController: IMPORTNEX_CHAT_IMPORT_TOKEN no configurado en el servidor.');
            return response()->json(['error' => 'Import bridge not configured on this server.'], 503);
        }

        $given = $request->header('X-Import-Token');
        if (! $given || ! hash_equals($expected, $given)) {
            return response()->json(['error' => 'Invalid or missing X-Import-Token.'], 401);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true);
        }
        if (empty($payload) || ! is_array($payload)) {
            $rawBody = $request->getContent() ?: file_get_contents('php://input');
            $payload = json_decode($rawBody, true);
        }
        if (empty($payload) || ! is_array($payload)) {
            return response()->json(['error' => 'Empty or invalid JSON body.'], 422);
        }

        try {
            $payload = $importer->validate($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $orgName = $request->query('org') ?: 'JJ Import Motors';
        $org = Organization::where('name', $orgName)->first();
        if (! $org) {
            return response()->json(['error' => "Organization not found: {$orgName}"], 404);
        }

        try {
            $car = $importer->resolveCar($payload, $org);
            $wasNew = ! $car->exists;
            $importer->apply($car, $payload);
        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::store failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'status'  => $wasNew ? 'created' : 'updated',
            'car_id'  => $car->id,
            'car_url' => url("/cars/{$car->id}"),
        ], $wasNew ? 201 : 200);
    }
}
