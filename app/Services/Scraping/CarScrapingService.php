<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates car listing scraping from supported portals.
 *
 * Flow:
 *   1. Validate URL against allowlist (Germany + Spain + generic)
 *   2. Check 24h cache (avoid duplicate cost)
 *   3. Fetch HTML with cascading strategies: Jina → Puppeteer → Plain HTTP
 *   4. Try extractors in order: Mistral → MiniMax → GLM
 *   5. Extract photo URLs from the listing
 *   6. Cache the winning result
 *   7. Return normalized data + provider used + photo URLs
 */
class CarScrapingService
{
    /** @var string[] Supported portals (Germany primary, Spain secondary, generic) */
    private const ALLOWED_HOSTS = [
        // Germany
        'mobile.de',
        'www.mobile.de',
        'suchen.mobile.de',
        'autoscout24.de',
        'www.autoscout24.de',
        // Spain
        'autoscout24.es',
        'www.autoscout24.es',
        'coches.com',
        'www.coches.com',
        'milanuncios.com',
        'www.milanuncios.com',
        'wallapop.com',
        'www.wallapop.com',
        'coches.net',
        'www.coches.net',
        // Generic European
        'autovit.ro',
        'www.autovit.ro',
        'olx.ro',
        'www.olx.ro',
    ];

    /** Cache TTL in seconds (24 hours). */
    private const CACHE_TTL = 86400;

    /** Custom HTTP factory injected by tests. */
    private ?\Closure $httpHandler = null;

    public function __construct(
        private readonly MistralExtractor $primary,
        private readonly MiniMaxExtractor $fallback1,
        private readonly GlmExtractor $fallback2,
    ) {
    }

    /**
     * Allow tests to inject a fake HTTP handler that bypasses the network.
     *
     * The closure receives the URL string and must return either a string
     * (HTML body — assumes HTTP 200) or null/empty to fall back to real network.
     */
    public function setHttpHandler(\Closure $handler): void
    {
        $this->httpHandler = $handler;
    }

    /**
     * @return array{
     *   success: bool,
     *   data?: array,
     *   provider?: string,
     *   cached?: bool,
     *   attempts?: array,
     *   error?: string
     * }
     */
    public function scrape(string $url): array
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

        $startedAt = microtime(true);
        $host = parse_url($url, PHP_URL_HOST);
        Log::info('Car scraping started', ['url' => $url, 'host' => $host]);

        $html = $this->fetchHtml($url);
        if ($html === null) {
            Log::warning('Car scraping fetch failed', ['url' => $url, 'host' => $host]);
            return ['success' => false, 'error' => 'No se pudo descargar la página (posible bloqueo anti-bot).'];
        }

        $attempts = [];
        foreach ($this->extractors() as $extractor) {
            try {
                $result = $extractor->extract($html, $url);
                $attempts[] = [
                    'provider' => $result['provider'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null,
                ];

                if ($result['success']) {
                    $photos = $this->extractPhotoUrls($html, $url);

                    $payload = [
                        'success' => true,
                        'data' => $result['data'],
                        'photos' => $photos,
                        'provider' => $result['provider'],
                        'attempts' => $attempts,
                    ];
                    cache()->put($cacheKey, $payload, self::CACHE_TTL);

                    Log::info('Car scraping success', [
                        'url' => $url,
                        'host' => $host,
                        'provider' => $result['provider'],
                        'fields_filled' => count(array_filter($result['data'])),
                        'photos_found' => count($photos),
                        'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                    ]);

                    return array_merge($payload, ['cached' => false]);
                }
            } catch (Throwable $e) {
                $attempts[] = [
                    'provider' => $extractor->name(),
                    'success' => false,
                    'error' => 'Exception: ' . $e->getMessage(),
                ];
                Log::warning('Extractor threw', [
                    'provider' => $extractor->name(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('Car scraping all extractors failed', [
            'url' => $url,
            'host' => $host,
            'attempts' => $attempts,
            'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return [
            'success' => false,
            'error' => 'Todos los extractores fallaron.',
            'attempts' => $attempts,
        ];
    }

    /** @return AiExtractorInterface[] */
    private function extractors(): array
    {
        return [$this->primary, $this->fallback1, $this->fallback2];
    }

    /**
     * Extract photo URLs from the listing HTML.
     *
     * Looks for common patterns:
     * - <img src="..."> in gallery/photo containers
     * - OpenGraph / Twitter image meta tags
     * - JSON-LD structured data (image field)
     * - data-src (lazy-loaded images)
     * - Background images in inline styles
     *
     * Returns absolute URLs only, deduplicated, capped at 20.
     *
     * @return string[]
     */
    private function extractPhotoUrls(string $html, string $baseUrl): array
    {
        $urls = [];
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';

        // 1. JSON-LD structured data
        if (preg_match_all('/<script[^>]+application\/ld\+json[^>]*>(.*?)<\/script>/si', $html, $matches)) {
            foreach ($matches[1] as $jsonLd) {
                $data = json_decode($jsonLd, true);
                if (is_array($data)) {
                    $this->collectImagesFromArray($data, $urls);
                }
            }
        }

        // 2. og:image and twitter:image meta tags
        if (preg_match_all('/<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url);
            }
        }

        // 3. <img src> and data-src attributes (focus on gallery containers)
        $imgPattern = '/<img[^>]+(?:src|data-src)=["\'](https?:\/\/[^"\']+\.(?:jpg|jpeg|png|webp)(?:\?[^"\']*)?)["\']/i';
        if (preg_match_all($imgPattern, $html, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url);
            }
        }

        // 4. background-image in inline styles
        if (preg_match_all('/background-image\s*:\s*url\([\"\\\']?(https?:[^\"\\\')]+)[\"\\\']?\)/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url);
            }
        }

        // Resolve relative URLs and filter
        $resolved = [];
        foreach ($urls as $url) {
            // Skip tiny tracking pixels
            if (str_contains($url, '1x1') || str_contains($url, 'pixel') || str_contains($url, 'tracking')) {
                continue;
            }
            // Resolve relative to absolute
            if (str_starts_with($url, '//')) {
                $url = $baseScheme . ':' . $url;
            } elseif (str_starts_with($url, '/')) {
                $url = $baseScheme . '://' . ($baseHost ?? '') . $url;
            } elseif (!str_starts_with($url, 'http')) {
                continue;
            }
            // Only keep images from the same host (avoid third-party trackers)
            $imgHost = parse_url($url, PHP_URL_HOST);
            if (is_string($imgHost) && is_string($baseHost) && str_contains($imgHost, $baseHost)) {
                $resolved[] = $url;
            } elseif (is_string($imgHost) && !str_contains($imgHost, 'facebook') && !str_contains($imgHost, 'google')) {
                $resolved[] = $url;
            }
        }

        // Deduplicate + cap at 20
        $resolved = array_values(array_unique($resolved));
        return array_slice($resolved, 0, 20);
    }

    /**
     * Recursively walk an array looking for "image" keys.
     *
     * @param  array<mixed>  $data
     * @param  string[]  $urls
     */
    private function collectImagesFromArray(array $data, array &$urls): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && (strtolower((string) $key) === 'image' || strtolower((string) $key) === 'thumbnailurl') && str_starts_with($value, 'http')) {
                $urls[] = $value;
            } elseif (is_array($value)) {
                $this->collectImagesFromArray($value, $urls);
            }
        }
    }

    private function isAllowedUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return false;
        }
        return in_array(strtolower($host), self::ALLOWED_HOSTS, true);
    }

    private function fetchHtml(string $url): ?string
    {
        // Test override path: bypass network entirely.
        if ($this->httpHandler !== null) {
            $result = ($this->httpHandler)($url);
            if (is_string($result) && $result !== '') {
                return $result;
            }
            if ($result === false) {
                return null; // simulate failed fetch
            }
        }

        // Strategy 1: Jina Reader (handles JS rendering, anti-bot bypass, returns clean markdown).
        $jina = $this->fetchViaJina($url);
        if ($jina !== null) {
            return $jina;
        }

        // Strategy 2: Puppeteer (local headless browser — requires spatie/browsershot).
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            $puppeteer = $this->fetchViaPuppeteer($url);
            if ($puppeteer !== null) {
                return $puppeteer;
            }
        }

        // Strategy 3: Plain HTTP (works for sites that don't block bots).
        return $this->fetchViaPlainHttp($url);
    }

    /**
     * Jina Reader converts any URL into clean markdown, executing JS and bypassing
     * most anti-bot measures. Free tier: 1M tokens/month.
     *
     * Without an API key, Jina's "Reader" public endpoint returns a lightweight
     * 200-byte summary instead of the full page — we detect that and fall through.
     */
    private function fetchViaJina(string $url): ?string
    {
        $apiKey = config('services.jina.api_key');
        $jinaUrl = config('services.jina.base_url') . '/' . $url;

        // With API key: full power, markdown output
        if (!empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'X-Respond-With' => 'markdown',
                    'X-Target-Selector' => 'main, .listing, #content, body',
                    'X-Return-Format' => 'markdown',
                    'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                ])
                    ->timeout(config('services.jina.timeout', 60))
                    ->get($jinaUrl);

                if ($response->failed()) {
                    Log::warning('Jina fetch failed', [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                    return null;
                }

                $body = trim($response->body());
                if ($body === '' || $this->isJinaSummary($body)) {
                    Log::warning('Jina returned empty/summary', ['url' => $url, 'length' => strlen($body)]);
                    return null;
                }

                Log::info('Jina fetch OK', ['url' => $url, 'length' => strlen($body)]);
                return $body;
            } catch (Throwable $e) {
                Log::warning('Jina fetch exception', ['url' => $url, 'message' => $e->getMessage()]);
                return null;
            }
        }

        // Without API key: try the public endpoint; bail if it returns the lightweight summary.
        try {
            $response = Http::withHeaders([
                'X-Respond-With' => 'markdown',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
            ])
                ->timeout(config('services.jina.timeout', 60))
                ->get($jinaUrl);

            if ($response->failed()) {
                return null;
            }

            $body = trim($response->body());
            // Public endpoint returns <500 bytes of summary when no key is set.
            if (strlen($body) < 1000 || $this->isJinaSummary($body)) {
                return null;
            }

            return $body;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Detect Jina's lightweight summary response (used when no API key is present
     * or when the upstream page is a tracking pixel).
     */
    private function isJinaSummary(string $body): bool
    {
        return str_contains($body, 'A 1x1 image, likely be a tracker probe')
            || str_contains($body, 'Cached snapshot')
            || str_contains($body, 'Warning: This is a cached snapshot');
    }

    /**
     * Local headless Chrome via spatie/browsershot (Puppeteer wrapper).
     * Requires: composer require spatie/browsershot + npm install puppeteer.
     *
     * Anti-bot bypass flags:
     * - --disable-blink-features=AutomationControlled  → removes "Chrome is being controlled..."
     * - --no-sandbox + --disable-setuid-sandbox       → required when running as root
     * - Realistic viewport + locale + UA
     * - Random delay before fetching (defeats instant-fetch fingerprinting)
     */
    private function fetchViaPuppeteer(string $url): ?string
    {
        try {
            $screenshot = \Spatie\Browsershot\Browsershot::url($url)
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36')
                ->windowSize(1920, 1080)
                ->deviceScaleFactor(1)
                ->setExtraOption('args', [
                    '--disable-blink-features=AutomationControlled',
                    '--disable-features=IsolateOrigins,site-per-process',
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--lang=de-DE,de;q=0.9,en;q=0.8',
                ])
                ->setExtraOption('headless', 'new')
                ->waitUntilNetworkIdle()
                ->delay(1500 + random_int(0, 1500))  // 1.5-3s "thinking" delay
                ->bodyHtml();

            return $screenshot;
        } catch (Throwable $e) {
            Log::warning('Puppeteer fetch failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Plain HTTP fetch — last resort, fails on most anti-bot-protected sites.
     */
    private function fetchViaPlainHttp(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                    . 'Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8,es;q=0.7',
            ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                Log::warning('Plain HTTP fetch failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::error('Plain HTTP fetch exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
