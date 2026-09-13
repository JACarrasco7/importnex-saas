<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auditoria 2026-09-06 (D10): añade headers de seguridad HTTP básicos que
 * faltan en el proyecto. No bloquea nada — solo informa al navegador.
 *
 * - X-Content-Type-Options: nosniff  → evita MIME sniffing.
 * - X-Frame-Options: SAMEORIGIN      → previene clickjacking en iframes.
 * - Referrer-Policy: strict-origin-when-cross-origin → minimiza leaks de URL.
 * - Permissions-Policy: deny cámara/micrófono/geolocalización por defecto.
 *
 * CSP completo es invasivo (rompe flows con Tailwind CDN o Vite dev).
 * Si en el futuro se quiere CSP estricto, añadir aquí un middleware aparte.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // (auditoria ronda 4, sep-2026): HSTS fuerza HTTPS durante 1 anio.
        // Sin esto un atacante en la misma red puede hacer SSL stripping.
        // Forge ya redirige HTTP→HTTPS; este header indica a navegadores
        // que nuncausen HTTP para subdominios tampoco.
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}
