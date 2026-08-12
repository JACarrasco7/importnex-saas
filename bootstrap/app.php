<?php

use App\Http\Middleware\EnsureHasOrganization;
use App\Http\Middleware\EnsureOrganization;
use App\Http\Middleware\ForceBaseUrl;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ImportToken;
use App\Http\Middleware\PlanLimitMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ForceBaseUrl::class,
        ]);

        $middleware->alias([
            'organization' => EnsureOrganization::class,
            'has.organization' => EnsureHasOrganization::class,
            'plan.limit' => PlanLimitMiddleware::class,
            'import-token' => ImportToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
