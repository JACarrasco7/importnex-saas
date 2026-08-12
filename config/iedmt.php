<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IEDMT — Impuesto Especial sobre Determinados Medios de Transporte
    |--------------------------------------------------------------------------
    |
    | Orden HAC/1501/2025, vigente 1-ene-2026. Fuente única de verdad: este archivo.
    | Referencia documental: .claude/skills/importacion-vehiculos/costes.md §IEDMT.
    |
    | Coeficientes de antigüedad (Anexo IV). Índice = años completos del vehículo,
    | con clamp al último valor (>12 años → 0.10).
    |
    */
    'coeficientes_antiguedad' => [
        0 => 1.00,   // ≤1 año
        1 => 0.84,   // 1-2 años
        2 => 0.67,   // 2-3 años
        3 => 0.56,   // 3-4 años
        4 => 0.47,   // 4-5 años
        5 => 0.39,   // 5-6 años
        6 => 0.34,   // 6-7 años
        7 => 0.28,   // 7-8 años
        8 => 0.24,   // 8-9 años
        9 => 0.19,   // 9-10 años
        10 => 0.17,  // 10-11 años
        11 => 0.13,  // 11-12 años
        12 => 0.10,  // >12 años
    ],

    /*
    | Tipos impositivos según emisiones de CO₂ (g/km).
    |
    */
    'tipos_co2' => [
        'max_120' => 0.000,    // ≤120 g/km → 0%
        'max_159' => 0.0475,   // 121-159 g/km → 4,75%
        'max_199' => 0.0975,   // 160-199 g/km → 9,75%
        'default' => 0.1475,   // ≥200 g/km → 14,75%
    ],
];
