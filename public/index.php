<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Hotfix: force-load .env into putenv() so Laravel encrypt/config providers can read it
if (file_exists(__DIR__.'/../.env')) {
    \Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/..')->safeLoad();
    // Also ensure all keys are in real env via putenv (for getenv() reads)
    foreach (parse_ini_file(__DIR__.'/../.env', false, INI_SCANNER_RAW) ?: [] as $k => $v) {
        @putenv("$k=$v");
    }
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
