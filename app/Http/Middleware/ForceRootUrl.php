<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceRootUrl
{
    /**
     * Detect if app is mounted under a sub-path (e.g. /importnexcore)
     * and force Laravel to use it in all generated URLs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $base = config('app.url');
        if ($base) {
            $parsed = parse_url($base);
            if (isset($parsed['path']) && $parsed['path'] !== '/') {
                $path = rtrim($parsed['path'], '/');
                $request->server->set('REQUEST_URI', $path . $request->getRequestUri());
                $request->server->set('SCRIPT_NAME', $path . '/index.php');
            }
            URL::forceRootUrl($base);
            if (($parsed['scheme'] ?? 'http') === 'https') {
                URL::forceScheme('https');
            }
        }
        return $next($request);
    }
}
