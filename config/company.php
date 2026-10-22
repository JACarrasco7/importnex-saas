<?php

/*
 * Datos de la empresa que opera la plataforma (fallback). Cada organización
 * tiene los suyos en Ajustes → «Datos de empresa» (ver App\Support\Brand);
 * estos valores solo se usan cuando no hay organización en contexto.
 */

return [

    'nombre' => env('COMPANY_NAME', env('APP_NAME', 'Importnex')),

    'telefono_1' => env('COMPANY_PHONE'),
    'telefono_2' => env('COMPANY_PHONE_2'),
    'email' => env('COMPANY_EMAIL'),
    'web' => env('COMPANY_WEB'),
    'ventas_email' => env('COMPANY_SALES_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Honorarios fijos del servicio por defecto (visible en el folleto).
    'precio_honorarios' => env('COMPANY_FEE_LABEL', '1.500 €'),

];
