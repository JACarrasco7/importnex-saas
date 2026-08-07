<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OneSignal App ID (por defecto global)
    |--------------------------------------------------------------------------
    |
    | Cada organización puede tener su propio onesignal_app_id.
    | Este valor se usa como fallback si una organización no tiene
    | uno configurado.
    */
    'default_app_id' => env('ONESIGNAL_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | OneSignal API Key (por defecto global)
    |--------------------------------------------------------------------------
    */
    'default_api_key' => env('ONESIGNAL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OneSignal REST API Base URL
    |--------------------------------------------------------------------------
    */
    'api_url' => 'https://api.onesignal.com/notifications',
];
