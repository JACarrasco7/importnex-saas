<?php

/*
 * Estimaciones de gastos de importación Alemania → España (Huelva).
 *
 * Se usan en PrecioClienteCalculator para mostrar al cliente el cálculo
 * de "precio del anuncio + gastos" SIN desglose de margen (regla C3,
 * auditoría 09-sep-2026).
 *
 * Los valores por defecto son estimaciones conservadoras y pueden
 * sobreescribirse desde .env si el cliente quiere ajustar:
 *   IMP_KM_ALEMANIA_HUELVA, IMP_TRANSPORTE_EUR_KM, IMP_IVA_PCT,
 *   IMP_IEDMT_EUR, IMP_GESTORIA_EUR.
 */

return [
    'transporte_km_alemania_huelva' => env('IMP_KM_ALEMANIA_HUELVA', 2200),
    'transporte_eur_km' => env('IMP_TRANSPORTE_EUR_KM', 0.45),
    'iva_pct' => env('IMP_IVA_PCT', 0.21),
    'iedmt_estimado_eur' => env('IMP_IEDMT_EUR', 250.0),
    'gestoria_eur' => env('IMP_GESTORIA_EUR', 220.0),
];
