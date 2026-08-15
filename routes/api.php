<?php

use App\Http\Controllers\Api\ImportValuationApiController;
use Illuminate\Support\Facades\Route;

// Puente chat -> servidor: sube un informe de valoracion directamente,
// sin pasar por sesion web. Autenticado con token compartido, no con
// usuario/password. El middleware import-token valida X-Import-Token y
// resuelve la organización (?org= o default). Auditoría 3 #11 (DRY).
Route::middleware('import-token')->group(function () {

    // Importa informe (Flujo A) — POST pesado, tier de escritura (auditoría 3 #14)
    Route::post('/import-valuation', [ImportValuationApiController::class, 'store'])
        ->middleware('throttle:api-write');

    // Importa un informe de modelo (Flujo B) — investigación sin decisión de venta.
    Route::post('/import-modelo', [ImportValuationApiController::class, 'storeModelo'])
        ->middleware('throttle:api-write');

    // Importa un scouting de mercado (Flujo C) con N modelos agregados.
    Route::post('/import-mercado', [ImportValuationApiController::class, 'storeMercado'])
        ->middleware('throttle:api-write');

    // Caché de investigación por modelo (guardar/recuperar aspectos reutilizables).
    Route::post('/investigation-cache', [ImportValuationApiController::class, 'storeInvestigationCache'])
        ->middleware('throttle:api-write');
    Route::get('/investigation-cache', [ImportValuationApiController::class, 'getInvestigationCache'])
        ->middleware('throttle:api-read');

    // §3.5 — Registro de cierres (tracking de KPIs de negocio).
    // POST para crear/actualizar, GET para listar + estadísticas.
    Route::post('/cierres', [ImportValuationApiController::class, 'storeCierre'])
        ->middleware('throttle:api-write');
    Route::get('/cierres', [ImportValuationApiController::class, 'indexCierres'])
        ->middleware('throttle:api-read');

    // §3.8 — KPIs agregados con histórico mensual (N meses, default 6).
    // Complementa /api/cierres con series temporales listas para gráficos externos.
    Route::get('/kpis', [ImportValuationApiController::class, 'kpis'])
        ->middleware('throttle:api-read');

    // Auditoría 3 (#7) — Lectura de scoutings (Flujo C) por organización.
    // Devuelve los escaneos de mercado importados, con sus modelos.
    Route::get('/scouting', [ImportValuationApiController::class, 'indexScouting'])
        ->middleware('throttle:api-read');
});
