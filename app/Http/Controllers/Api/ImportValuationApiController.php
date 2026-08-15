<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Cierre;
use App\Models\InvestigationCache;
use App\Models\ScoutingMercado;
use App\Services\KpiCalculator;
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
 *   curl -X POST https://jjimportmotors.on-forge.com/api/import-valuation \
 *     -H "X-Import-Token: <token>" \
 *     -H "Content-Type: application/json" \
 *     --data @informe.json
 */
class ImportValuationApiController extends Controller
{
    public function store(Request $request, ValuationImporter $importer): JsonResponse
    {
        $org = $request->attributes->get('import_org');

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

        // §10.7 — validar estructura mínima de Flujo A (schema_version + bloques requeridos)
        try {
            $payload = $importer->validate(
                $payload,
                ['_meta', 'vehiculo', 'anuncio', 'veredicto', 'costes', 'mercado']
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        try {
            $car = $importer->resolveCar($payload, $org);
            $wasNew = ! $car->exists;
            $importer->apply($car, $payload);
        } catch (\RuntimeException $e) {
            // Auditoría 3 (#4) — validaciones de negocio = payload inválido (422), no 500
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::store failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Import failed: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => $wasNew ? 'created' : 'updated',
            'car_id' => $car->id,
            'car_url' => url("/cars/{$car->id}"),
        ], $wasNew ? 201 : 200);
    }

    /**
     * Importa un informe de modelo (Flujo B) — investigación sin decisión de venta.
     *
     * Idéntico a store() pero valida que _meta.flujo = "B" y elimina publicidad si viene.
     *
     * Uso desde el chat (curl):
     *   curl -X POST https://jjimportmotors.on-forge.com/api/import-modelo \
     *     -H "X-Import-Token: <token>" \
     *     -H "Content-Type: application/json" \
     *     --data @flujo-b-golf-gti-2026-08-11.json
     */
    public function storeModelo(Request $request, ValuationImporter $importer): JsonResponse
    {
        $org = $request->attributes->get('import_org');

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

        // §10.6 — validar schema_version, flujo y bloques mínimos (Flujo B)
        try {
            $payload = $importer->validate(
                $payload,
                ['_meta', 'vehiculo', 'anuncio', 'veredicto', 'costes', 'mercado'],
                'B'
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Eliminar publicidad si viene (Flujo B no debe tener decisión de venta)
        unset($payload['publicidad']);

        try {
            $car = $importer->resolveCar($payload, $org);
            $wasNew = ! $car->exists;
            $importer->apply($car, $payload);
        } catch (\RuntimeException $e) {
            // Auditoría 3 (#4) — validaciones de negocio = payload inválido (422), no 500
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::storeModelo failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Import failed: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => $wasNew ? 'created' : 'updated',
            'flujo' => 'B',
            'car_id' => $car->id,
            'car_url' => url("/cars/{$car->id}"),
        ], $wasNew ? 201 : 200);
    }

    /**
     * Importa un scouting de mercado (Flujo C) con N modelos agregados.
     *
     * Uso desde el chat (curl):
     *   curl -X POST https://jjimportmotors.on-forge.com/api/import-mercado \
     *     -H "X-Import-Token: <token>" \
     *     -H "Content-Type: application/json" \
     *     --data @flujo-c-2026-08-11.json
     */
    public function storeMercado(Request $request, ValuationImporter $importer): JsonResponse
    {
        $org = $request->attributes->get('import_org');

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

        // §10.6 — validar schema_version, flujo y bloques mínimos (Flujo C)
        try {
            $payload = $importer->validate(
                $payload,
                ['_meta', 'modelos'],
                'C'
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (! isset($payload['_meta']['scouting_id'])) {
            return response()->json(['error' => 'Missing _meta.scouting_id.'], 422);
        }

        if (! is_array($payload['modelos'])) {
            return response()->json(['error' => 'Field "modelos" must be an array.'], 422);
        }

        // Validar campos requeridos en cada modelo
        foreach ($payload['modelos'] as $i => $modeloData) {
            foreach (['modelo', 'hueco_pct', 'n_uds_de'] as $field) {
                if (! isset($modeloData[$field])) {
                    return response()->json([
                        'error' => "Modelo #{$i}: missing required field '{$field}'",
                    ], 422);
                }
            }
        }

        try {
            // Crear o actualizar ScoutingMercado — scoped por organización
            // (auditoría 3 #1: scouting_id es unique PER org, no global)
            $scouting = ScoutingMercado::updateOrCreate(
                [
                    'scouting_id' => $payload['_meta']['scouting_id'],
                    'organization_id' => $org->id,
                ],
                [
                    'schema_version' => $payload['_meta']['schema_version'] ?? 1,
                    'flujo' => $payload['_meta']['flujo'],
                    'generado_el' => $payload['_meta']['generado_el'] ?? now(),
                    'origen' => $payload['_meta']['origen'] ?? 'chat-ia',
                    'preferencias_usuario' => $payload['_meta']['preferencias_usuario'] ?? null,
                    'modelos_escaneados' => $payload['modelos_escaneados'] ?? 0,
                    'modelos_con_hueco' => $payload['modelos_con_hueco'] ?? 0,
                    'modelos_sin_hueco' => $payload['modelos_sin_hueco'] ?? 0,
                    'resumen_ejecutivo' => $payload['resumen_ejecutivo'] ?? null,
                    'organization_id' => $org->id,
                ]
            );

            // Eliminar modelos anteriores si es actualización
            $scouting->modelos()->delete();

            // Crear nuevos modelos
            foreach ($payload['modelos'] as $modeloData) {
                $scouting->modelos()->create([
                    'modelo' => $modeloData['modelo'] ?? 'Sin modelo',
                    'segmento' => $modeloData['segmento'] ?? null,
                    'hueco_pct' => $modeloData['hueco_pct'] ?? null,
                    'n_uds_de' => $modeloData['n_uds_de'] ?? 0,
                    'mediana_es' => $modeloData['mediana_es'] ?? null,
                    'mediana_de' => $modeloData['mediana_de'] ?? null,
                    'vendibilidad_estimada' => $modeloData['vendibilidad_estimada'] ?? null,
                    'recomendacion_aprox' => $modeloData['recomendacion_aprox'] ?? null,
                    'mejor_anuncio_url' => $modeloData['mejor_anuncio_url'] ?? null,
                    'fuente_cobertura' => $modeloData['fuente_cobertura'] ?? null,
                ]);
            }

            Log::info('ScoutingMercado importado', [
                'scouting_id' => $scouting->scouting_id,
                'modelos_count' => count($payload['modelos']),
            ]);

            return response()->json([
                'status' => 'created',
                'scouting_id' => $scouting->scouting_id,
                'modelos_count' => $scouting->modelos()->count(),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::storeMercado failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Import failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Guarda o recupera la caché de investigación por modelo.
     *
     * Uso desde el chat (curl):
     *   curl -X POST https://jjimportmotors.on-forge.com/api/investigation-cache \
     *     -H "X-Import-Token: <token>" \
     *     -H "Content-Type: application/json" \
     *     --data '{"marca":"Opel","modelo":"Astra","potencia":280,"combustible":"Gasolina","aspectos":{...}}'
     */
    public function storeInvestigationCache(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

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

        // Validar campos requeridos
        $required = ['marca', 'modelo', 'aspectos'];
        foreach ($required as $field) {
            if (! isset($payload[$field])) {
                return response()->json(['error' => "Missing required field: {$field}"], 422);
            }
        }

        if (! is_array($payload['aspectos'])) {
            return response()->json(['error' => 'Field "aspectos" must be an object'], 422);
        }

        try {
            // Generar clave única del modelo (scoped por organización)
            $clave = InvestigationCache::generarClave(
                $payload['marca'],
                $payload['modelo'],
                $payload['potencia'] ?? null,
                $payload['combustible'] ?? null
            );

            // Buscar o crear registro (scoped por organización)
            $cache = InvestigationCache::updateOrCreate(
                ['clave_modelo' => $clave, 'organization_id' => $org->id],
                [
                    'marca' => $payload['marca'],
                    'modelo' => $payload['modelo'],
                    'potencia' => $payload['potencia'] ?? null,
                    'combustible' => $payload['combustible'] ?? null,
                    'aspectos' => $payload['aspectos'],
                ]
            );

            Log::info('InvestigationCache actualizado', [
                'clave_modelo' => $clave,
                'organization_id' => $org->id,
                'aspectos_count' => count($payload['aspectos']),
            ]);

            return response()->json([
                'status' => 'saved',
                'clave_modelo' => $clave,
                'organization_id' => $org->id,
                'aspectos_validos' => $cache->aspectosValidos(),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::storeInvestigationCache failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Cache operation failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Recupera la caché de investigación por modelo.
     *
     * Uso desde el chat (curl):
     *   curl -X GET "https://jjimportmotors.on-forge.com/api/investigation-cache?marca=Opel&modelo=Astra&potencia=280" \
     *     -H "X-Import-Token: <token>"
     */
    public function getInvestigationCache(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

        // Validar campos requeridos con mensaje detallado (§10.2)
        $marca = $request->query('marca');
        $modelo = $request->query('modelo');
        $missing = array_filter([
            ! $marca ? 'marca' : null,
            ! $modelo ? 'modelo' : null,
        ]);

        if (! empty($missing)) {
            return response()->json([
                'error' => 'Missing required query params: '.implode(', ', $missing),
                'hint' => 'Example: GET /api/investigation-cache?marca=Opel&modelo=Astra&potencia=280&combustible=Gasolina',
            ], 422);
        }

        try {
            // Generar clave única del modelo
            $clave = InvestigationCache::generarClave(
                $marca,
                $modelo,
                $request->query('potencia') ? (int) $request->query('potencia') : null,
                $request->query('combustible')
            );

            // Buscar registro (scoped por organización)
            $cache = InvestigationCache::where('clave_modelo', $clave)
                ->where('organization_id', $org->id)
                ->first();

            if (! $cache) {
                return response()->json([
                    'status' => 'not_found',
                    'clave_modelo' => $clave,
                    'organization_id' => $org->id,
                    'message' => 'No cache found for this model in this organization',
                ], 404);
            }

            return response()->json([
                'status' => 'found',
                'clave_modelo' => $clave,
                'organization_id' => $org->id,
                'aspectos_validos' => $cache->aspectosValidos(),
                'aspectos_caducados' => $cache->aspectosCaducados(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::getInvestigationCache failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Cache operation failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Crea o actualiza un registro de cierre de venta.
     *
     * §3.5 — Endpoint para tracking de KPIs de negocio.
     *
     * Uso desde el chat (curl):
     *   curl -X POST https://jjimportmotors.on-forge.com/api/cierres \
     *     -H "X-Import-Token: <token>" \
     *     -H "Content-Type: application/json" \
     *     --data '{"coche_id":"opel-astra-opc-2012","veredicto":"Comprar","precio_objetivo":11800,"fecha_venta":"2026-08-15","precio_final":11500,"cliente":"Juan Pérez","plataforma":"Wallapop","estado":"vendido"}'
     */
    public function storeCierre(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

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

        // Validar campos requeridos
        $required = ['coche_id', 'veredicto', 'fecha_investigacion'];
        foreach ($required as $field) {
            if (! isset($payload[$field])) {
                return response()->json(['error' => "Missing required field: {$field}"], 422);
            }
        }

        // Auditoría 3 (#3) — validar formato de fechas (evitar 500)
        foreach (['fecha_investigacion', 'fecha_venta'] as $fechaField) {
            if (isset($payload[$fechaField]) && $payload[$fechaField] !== null) {
                $fecha = $payload[$fechaField];
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $fecha) || strtotime($fecha) === false) {
                    return response()->json([
                        'error' => "Invalid '{$fechaField}' format. Expected YYYY-MM-DD.",
                        'hint' => 'Example: 2026-08-15',
                    ], 422);
                }
            }
        }

        // Auditoría 3 (#13) — validar veredicto contra valores del contrato
        $veredictoNorm = strtolower(trim($payload['veredicto']));
        $veredictosValidos = ['comprar', 'dudoso', 'descartar'];
        if (! str_starts_with($veredictoNorm, 'comprar') && ! in_array($veredictoNorm, $veredictosValidos)) {
            return response()->json([
                'error' => "Invalid veredicto: '{$payload['veredicto']}'. Must be: Comprar, Comprar si baja..., Dudoso, Descartar",
            ], 422);
        }

        // Validar estado si viene
        if (isset($payload['estado']) && ! in_array($payload['estado'], ['vendido', 'no_vendido', 'pendiente'])) {
            return response()->json(['error' => 'Invalid "estado". Must be: vendido, no_vendido, pendiente'], 422);
        }

        try {
            // Resolver car_id si el coche existe en BD.
            // El chat puede mandar car_id explícito; si no, intentamos resolver
            // por coche_id buscando el vin/url del Car (el slug NO existe en cars).
            $carId = $payload['car_id'] ?? null;
            $brand = $payload['brand'] ?? null;
            $model = $payload['model'] ?? null;

            if (! $carId && $brand) {
                $car = Car::where('organization_id', $org->id)
                    ->where('brand', $brand)
                    ->where('model', $model ?? '')
                    ->first();
                if ($car) {
                    $carId = $car->id;
                }
            }

            // §auditoría — idempotente: mismo coche + misma fecha de investigación no duplica.
            // Un reenvío (retry de curl, doble clic) actualiza el registro existente.
            // Se usa whereDate porque el cast 'date' guarda 'YYYY-MM-DD 00:00:00' y un
            // where con 'YYYY-MM-DD' a secas no coincide.
            $cierre = Cierre::where('organization_id', $org->id)
                ->where('coche_id', $payload['coche_id'])
                ->whereDate('fecha_investigacion', $payload['fecha_investigacion'])
                ->first();

            if (! $cierre) {
                $cierre = new Cierre([
                    'organization_id' => $org->id,
                    'coche_id' => $payload['coche_id'],
                ]);
            }

            $cierre->fill([
                'car_id' => $carId,
                'brand' => $brand,
                'model' => $model,
                'fecha_investigacion' => $payload['fecha_investigacion'],
                'veredicto' => $payload['veredicto'],
                'precio_objetivo' => $payload['precio_objetivo'] ?? null,
                'fecha_venta' => $payload['fecha_venta'] ?? null,
                'precio_final' => $payload['precio_final'] ?? null,
                'cliente' => $payload['cliente'] ?? null,
                'plataforma' => $payload['plataforma'] ?? null,
                'dias_hasta_venta' => $payload['dias_hasta_venta'] ?? null,
                'comentario' => $payload['comentario'] ?? null,
                'estado' => $payload['estado'] ?? 'pendiente',
            ]);

            // Calcular días automáticamente si no vinieron
            $cierre->calcularDiasHastaVenta();
            $cierre->save();

            Log::info('Cierre registrado', [
                'cierre_id' => $cierre->id,
                'coche_id' => $cierre->coche_id,
                'estado' => $cierre->estado,
            ]);

            return response()->json([
                'status' => $cierre->wasRecentlyCreated ? 'created' : 'updated',
                'cierre_id' => $cierre->id,
                'dias_hasta_venta' => $cierre->dias_hasta_venta,
                'desviacion_pct' => $cierre->desviacionPorcentaje(),
            ], $cierre->wasRecentlyCreated ? 201 : 200);

        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::storeCierre failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Cierre creation failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Endpoint /api/kpis — Devuelve KPIs agregados con histórico mensual.
     *
     * §3.8 — Complementa /api/cierres (listado raw) con series temporales
     * listas para gráficos externos (Looker, Notion, Telegram bot, etc.).
     *
     * Uso desde el chat (curl):
     *   curl -X GET "https://jjimportmotors.on-forge.com/api/kpis?periodo=2026-08&months=6" \
     *     -H "X-Import-Token: <token>"
     */
    public function kpis(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

        $months = max(1, min(24, (int) $request->query('months', 6)));
        $periodo = $request->query('periodo', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return response()->json(['error' => 'Invalid periodo format. Expected YYYY-MM.', 'hint' => 'Example: 2026-08'], 422);
        }

        try {
            $historico = [];
            foreach (KpiCalculator::historico($org->id, $months) as $mes) {
                $historico[] = [
                    'periodo' => $mes['periodo'],
                    'total_cierres' => $mes['volumen'],
                    'precision_veredictos_pct' => $mes['precision_veredictos'],
                    'tiempo_medio_venta_dias' => $mes['tiempo_hasta_venta'],
                    'tasa_falsos_positivos_pct' => $mes['tasa_falsos_positivos'],
                ];
            }

            $kpi = KpiCalculator::calcular(
                Cierre::where('organization_id', $org->id)->periodo($periodo)->get()
            );

            return response()->json([
                'status' => 'ok',
                'organization_id' => $org->id,
                'periodo_actual' => $periodo,
                'kpis_periodo' => [
                    'precision_veredictos_pct' => $kpi['precision_veredictos'],
                    'tiempo_medio_venta_dias' => $kpi['tiempo_hasta_venta'],
                    'desviacion_media_pct' => $kpi['desviacion_precio'],
                    'tasa_falsos_positivos_pct' => $kpi['tasa_falsos_positivos'],
                    'volumen' => $kpi['_counts']['total'],
                ],
                'historico' => $historico,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::kpis failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'KPIs aggregation failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Lista cierres filtrados por periodo (YYYY-MM) o estado.
     *
     * §3.5 — Endpoint para consultas de KPIs.
     *
     * Uso desde el chat (curl):
     *   curl -X GET "https://jjimportmotors.on-forge.com/api/cierres?periodo=2026-08&estado=vendido" \
     *     -H "X-Import-Token: <token>"
     */
    public function indexCierres(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

        try {
            $query = Cierre::where('organization_id', $org->id);

            // Filtros opcionales
            if ($periodo = $request->query('periodo')) {
                if (! preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                    return response()->json([
                        'error' => 'Invalid periodo format. Expected YYYY-MM.',
                        'hint' => 'Example: 2026-08',
                    ], 422);
                }
                $query->periodo($periodo);
            }

            if ($estado = $request->query('estado')) {
                if (! in_array($estado, ['vendido', 'no_vendido', 'pendiente'])) {
                    return response()->json(['error' => 'Invalid estado. Must be: vendido, no_vendido, pendiente'], 422);
                }
                $query->where('estado', $estado);
            }

            if ($veredictoPositivo = $request->boolean('veredicto_positivo')) {
                $query->veredictoPositivo();
            }

            $cierres = $query->orderByDesc('fecha_investigacion')
                ->limit($request->query('limit', 100))
                ->get();

            // Agregar estadísticas resumidas
            $vendidos = $cierres->where('estado', 'vendido');
            $veredictosPositivos = $cierres->filter(fn ($c) => str_starts_with(strtolower($c->veredicto), 'comprar'));
            $vendidosConVeredictoPositivo = $vendidos->filter(fn ($c) => str_starts_with(strtolower($c->veredicto), 'comprar'));

            $tiempoMedio = $vendidos->avg('dias_hasta_venta');
            $desviaciones = $vendidos->map->desviacionPorcentaje()->filter(fn ($d) => $d !== null);
            $desviacionMedia = $desviaciones->avg();

            $precisionVeredictos = $veredictosPositivos->count() > 0
                ? round(($vendidosConVeredictoPositivo->count() / $veredictosPositivos->count()) * 100, 1)
                : null;

            return response()->json([
                'status' => 'ok',
                'organization_id' => $org->id,
                'total' => $cierres->count(),
                'kpis' => [
                    'precision_veredictos_pct' => $precisionVeredictos,
                    'tiempo_medio_venta_dias' => $tiempoMedio !== null ? round($tiempoMedio, 1) : null,
                    'desviacion_media_pct' => $desviacionMedia !== null ? round($desviacionMedia, 2) : null,
                    'tasa_falsos_positivos_pct' => $veredictosPositivos->count() > 0
                        ? round((($veredictosPositivos->count() - $vendidosConVeredictoPositivo->count()) / $veredictosPositivos->count()) * 100, 1)
                        : null,
                ],
                'cierres' => $cierres->map(fn ($c) => [
                    'id' => $c->id,
                    'coche_id' => $c->coche_id,
                    'car_id' => $c->car_id,
                    'fecha_investigacion' => $c->fecha_investigacion?->format('Y-m-d'),
                    'veredicto' => $c->veredicto,
                    'precio_objetivo' => $c->precio_objetivo,
                    'fecha_venta' => $c->fecha_venta?->format('Y-m-d'),
                    'precio_final' => $c->precio_final,
                    'cliente' => $c->cliente,
                    'plataforma' => $c->plataforma,
                    'dias_hasta_venta' => $c->dias_hasta_venta,
                    'estado' => $c->estado,
                    'desviacion_pct' => $c->desviacionPorcentaje(),
                ]),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::indexCierres failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Cierres listing failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Lista scoutings de mercado (Flujo C) de la organización, con sus modelos.
     *
     * Auditoría 3 (#7) — El flujo C era write-only (solo storeMercado). Ahora
     * se pueden consultar los escaneos importados para el histórico de oportunidades.
     *
     * Uso desde el chat (curl):
     *   curl -X GET "https://jjimportmotors.on-forge.com/api/scouting?limit=20" \
     *     -H "X-Import-Token: <token>"
     */
    public function indexScouting(Request $request): JsonResponse
    {
        $org = $request->attributes->get('import_org');

        try {
            $scoutings = ScoutingMercado::with('modelos')
                ->where('organization_id', $org->id)
                ->orderByDesc('generado_el')
                ->limit(max(1, min(100, (int) $request->query('limit', 20))))
                ->get();

            return response()->json([
                'status' => 'ok',
                'organization_id' => $org->id,
                'total' => $scoutings->count(),
                'scoutings' => $scoutings->map(fn ($s) => [
                    'id' => $s->id,
                    'scouting_id' => $s->scouting_id,
                    'flujo' => $s->flujo,
                    'generado_el' => $s->generado_el?->format('Y-m-d H:i'),
                    'origen' => $s->origen,
                    'modelos_escaneados' => $s->modelos_escaneados,
                    'modelos_con_hueco' => $s->modelos_con_hueco,
                    'modelos_sin_hueco' => $s->modelos_sin_hueco,
                    'resumen_ejecutivo' => $s->resumen_ejecutivo,
                    'modelos' => $s->modelos->map(fn ($m) => [
                        'modelo' => $m->modelo,
                        'segmento' => $m->segmento,
                        'hueco_pct' => $m->hueco_pct,
                        'n_uds_de' => $m->n_uds_de,
                        'mediana_es' => $m->mediana_es,
                        'mediana_de' => $m->mediana_de,
                        'vendibilidad_estimada' => $m->vendibilidad_estimada,
                        'recomendacion_aprox' => $m->recomendacion_aprox,
                        'mejor_anuncio_url' => $m->mejor_anuncio_url,
                    ]),
                ]),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('ImportValuationApiController::indexScouting failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Scouting listing failed: '.$e->getMessage()], 500);
        }
    }
}
