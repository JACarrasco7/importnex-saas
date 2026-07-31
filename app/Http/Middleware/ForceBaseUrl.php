<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceBaseUrl
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $appUrl = config('app.url');
        if ($appUrl && str_contains($appUrl, '/importnexcore')) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        return $next($request);
    }
}
