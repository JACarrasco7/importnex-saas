<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy base URL
    |--------------------------------------------------------------------------
    |
    | Forzar el prefijo del subpath /importnexcore para que Ziggy genere
    | rutas absolutas con el prefijo correcto cuando la app se sirve
    | bajo un Alias de Apache.
    |
    */

    'base_url' => env('APP_URL', 'https://dev.aktive.cloud/importnexcore'),
    'base_port' => null,
    'base_domain' => null,
    'base_protocol' => 'https',
    'group' => false,
    'url' => env('APP_URL', 'https://dev.aktive.cloud/importnexcore'),
];
