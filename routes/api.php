<?php

use App\Http\Controllers\Api\ImportValuationApiController;
use Illuminate\Support\Facades\Route;

// Puente chat -> servidor: sube un informe de valoracion directamente,
// sin pasar por sesion web. Autenticado con token compartido, no con
// usuario/password (ver ImportValuationApiController para el detalle).
Route::post('/import-valuation', [ImportValuationApiController::class, 'store'])
    ->middleware('throttle:api-read');

// Adjunta un PDF-briefing generado por el chat al expediente de un coche ya
// existente. Mismo token, subida de archivo en vez de JSON.
Route::post('/cars/{car}/briefing-pdf', [ImportValuationApiController::class, 'attachBriefing'])
    ->middleware('throttle:api-write');
