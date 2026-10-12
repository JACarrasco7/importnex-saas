<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Http;

/**
 * Comprueba si el anuncio original de un coche (mobile.de, autoscout24,
 * coches.net, etc.) sigue disponible en su portal de origen.
 *
 * Usado por la tarea programada `marketplace:check-listings` para
 * desactivar automaticamente los coches cuyo anuncio ya no esta
 * disponible (vendido, retirado, 404, etc.).
 *
 * Logica (en orden):
 *  1. Si la URL esta vacia o no es http(s), se considera "no comprobable"
 *     (NO se desactiva, devuelve available=true con reason=skipped).
 *  2. HTTP GET con timeout corto (8s connect, 12s total) y un User-Agent
 *     de navegador real para minimizar 403 anti-bot.
 *  3. Si la respuesta es 2xx, se escanea el cuerpo buscando palabras
 *     clave de "vendido / no disponible" en ES, EN y DE. Si se
 *     encuentran, se considera no disponible con reason=url_sold_keyword.
 *     Si no, se considera disponible.
 *  4. Si la respuesta es 4xx (excepto 404/410 que SI desactivan), no
 *     se desactiva: el portal puede estar bloqueandonos. reason=url_4xx.
 *  5. Si la respuesta es 5xx, no se desactiva (transitorio): reason=url_5xx.
 *  6. Timeout / error de red: NO se desactiva (transitorio): reason=url_timeout.
 *
 * Importante: ante la duda (timeout, 5xx, 401/403), se prefiere NO
 * desactivar para no perder una publicacion por un fallo transitorio
 * del portal o de la red. El admin puede revisar los coches con
 * `marketplace_unavailable_reason` no vacio desde el panel.
 */
class ListingAvailabilityChecker
{
    public const REASON_SKIPPED = 'skipped';

    public const REASON_AVAILABLE = 'available';

    public const REASON_404 = 'url_404';

    public const REASON_4XX = 'url_4xx';

    public const REASON_5XX = 'url_5xx';

    public const REASON_TIMEOUT = 'url_timeout';

    public const REASON_SOLD_KEYWORD = 'url_sold_keyword';

    public const REASON_OTHER = 'url_other';

    /**
     * Palabras clave (lowercased) que indican que el anuncio ya no esta
     * disponible. Cubrimos ES, EN y DE porque los portales soportados son
     * de los tres idiomas (mobile.de = DE, autoscout24 = ES/DE, etc.).
     */
    private const SOLD_KEYWORDS = [
        'vendido', 'vendida', 'no disponible', 'anuncio retirado',
        'este anuncio ya no esta disponible', 'ha sido vendido',
        'sold', 'no longer available', 'this listing is no longer',
        'ad has been removed', 'has been sold', 'vehicle is sold',
        'verkauft', 'nicht mehr verfugbar', 'nicht verfügbar',
        'inserat wurde entfernt', 'wurde verkauft', 'ist verkauft',
    ];

    /**
     * @return array{available: bool, reason: string, http_status: ?int, message: ?string}
     */
    public function check(?string $url): array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return ['available' => true, 'reason' => self::REASON_SKIPPED, 'http_status' => null, 'message' => 'Sin URL original'];
        }

        if (! preg_match('#^https?://#i', $url)) {
            return ['available' => true, 'reason' => self::REASON_SKIPPED, 'http_status' => null, 'message' => 'URL no es http(s)'];
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8,de;q=0.7',
            ])->connectTimeout(8)->timeout(12)->get($url);
        } catch (\Throwable $e) {
            return [
                'available' => true,
                'reason' => self::REASON_TIMEOUT,
                'http_status' => null,
                'message' => 'Timeout o error de red: '.substr($e->getMessage(), 0, 120),
            ];
        }

        $status = $response->status();

        if ($status >= 200 && $status < 300) {
            $body = strtolower((string) $response->body());
            foreach (self::SOLD_KEYWORDS as $kw) {
                if (str_contains($body, $kw)) {
                    return [
                        'available' => false,
                        'reason' => self::REASON_SOLD_KEYWORD,
                        'http_status' => $status,
                        'message' => "Anuncio retirado: detectado '{$kw}' en el HTML",
                    ];
                }
            }

            return ['available' => true, 'reason' => self::REASON_AVAILABLE, 'http_status' => $status, 'message' => null];
        }

        if ($status === 404 || $status === 410) {
            return [
                'available' => false,
                'reason' => self::REASON_404,
                'http_status' => $status,
                'message' => "Anuncio no encontrado (HTTP {$status})",
            ];
        }

        if ($status >= 400 && $status < 500) {
            return [
                'available' => true,
                'reason' => self::REASON_OTHER,
                'http_status' => $status,
                'message' => "HTTP {$status} (posible bloqueo anti-bot, no se desactiva)",
            ];
        }

        if ($status >= 500) {
            return [
                'available' => true,
                'reason' => self::REASON_5XX,
                'http_status' => $status,
                'message' => "HTTP {$status} (error del portal, no se desactiva)",
            ];
        }

        return [
            'available' => true,
            'reason' => self::REASON_OTHER,
            'http_status' => $status,
            'message' => "HTTP {$status} no concluyente",
        ];
    }
}
