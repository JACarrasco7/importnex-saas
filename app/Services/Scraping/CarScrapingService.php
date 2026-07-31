<?php

namespace App\Services\Scraping;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Car listing scraper. Single AI extractor (GenericAiExtractor) powered by the
 * organization's chosen provider. No more provider fallback chain.
 *
 * Flow:
 *   1. Validate URL against allowlist (Germany + Spain + Romania)
 *   2. 24h cache check
 *   3. Fetch HTML (plain HTTP fallback; Jina/Puppeteer optional)
 *   4. Hand off to GenericAiExtractor
 *   5. Extract photo URLs
 *   6. Cache result
 *   7. Return normalized data + provider used + photos
 */
class CarScrapingService
{
    private const ALLOWED_HOSTS = [
        // Germany
        'mobile.de', 'www.mobile.de', 'suchen.mobile.de',
        'autoscout24.de', 'www.autoscout24.de',
        // Spain
        'autoscout24.es', 'www.autoscout24.es',
        'coches.com', 'www.coches.com',
        'milanuncios.com', 'www.milanuncios.com',
        'wallapop.com', 'www.wallapop.com',
        'coches.net', 'www.coches.net',
        // Romania
        'autovit.ro', 'www.autovit.ro',
        'olx.ro', 'www.olx.ro',
    ];

    private const CACHE_TTL = 86400;

    public function __construct(private readonly GenericAiExtractor $extractor) {}

    /**
     * @return array{
     *   success: bool, data?: array, provider?: string, cached?: bool,
     *   error?: string
     * }
     */
    public function scrape(string $url, ?Organization $org = null): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['success' => false, 'error' => 'Empty URL'];
        }

        if (!$this->isAllowedUrl($url)) {
            return [
                'success' => false,
                'error' => 'URL no soportada. Portales válidos: mobile.de, autoscout24 (de/es), coches.com, milanuncios, wallapop, coches.net, autovit.ro, olx.ro.',
            ];
        }

        $cacheKey = 'scrape:' . md5($url);
        $cached = cache()->get($cacheKey);
        if (is_array($cached) && ($cached['success'] ?? false) === true) {
            return array_merge($cached, ['cached' => true]);
        }

        $host = parse_url($url, PHP_URL_HOST);
        Log::info('Car scraping started', ['url' => $url, 'host' => $host]);

        $html = $this->fetchHtml($url);
        if ($html === null) {
            return ['success' => false, 'error' => 'No se pudo descargar la página (posible bloqueo anti-bot).'];
        }

        try {
            $result = $this->extractor->extract($html, $url, $org);
        } catch (Throwable $e) {
            Log::warning('Extractor threw', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Extractor error: '.$e->getMessage()];
        }

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'extractor failed', 'provider' => $result['provider'] ?? null];
        }

        $photos = $this->extractPhotoUrls($html, $url);
        $payload = [
            'success' => true,
            'data' => $result['data'],
            'photos' => $photos,
            'provider' => $result['provider'] ?? 'unknown',
        ];
        cache()->put($cacheKey, $payload, self::CACHE_TTL);

        return array_merge($payload, ['cached' => false]);
    }

    private function isAllowedUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') return false;
        return in_array($host, self::ALLOWED_HOSTS, true);
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $resp = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                    'Accept-Language' => 'es-ES,es;q=0.9,de;q=0.8,en;q=0.7',
                ])
                ->get($url);
            if (!$resp->successful()) return null;
            return $resp->body();
        } catch (Throwable $e) {
            Log::warning('fetchHtml failed', ['url' => $url, 'msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function extractPhotoUrls(string $html, string $baseUrl): array
    {
        $urls = [];
        $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';

        // JSON-LD
        if (preg_match_all('/<script[^>]+application\/ld\+json[^>]*>(.*?)<\/script>/si', $html, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/"image"\s*:\s*"(https?:[^"]+)"/i', $block, $imgs)) {
                    foreach ($imgs[1] as $i) $urls[] = $i;
                }
            }
        }

        // <img src>
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $src) {
                if (str_starts_with($src, '//')) $src = $baseScheme . ':' . $src;
                if (str_starts_with($src, '/')) {
                    $h = parse_url($baseUrl, PHP_URL_HOST);
                    $src = $baseScheme . '://' . $h . $src;
                }
                if (str_starts_with($src, 'http')) $urls[] = $src;
            }
        }

        $urls = array_values(array_unique($urls));
        return array_slice($urls, 0, 20);
    }
}
