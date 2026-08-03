<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale') ?? $request->input('locale') ?? $request->cookie('locale') ?? 'en';

        if (in_array($locale, ['en', 'es'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
