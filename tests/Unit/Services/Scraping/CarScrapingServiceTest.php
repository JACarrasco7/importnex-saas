<?php

namespace Tests\Unit\Services\Scraping;

use App\Services\Scraping\CarScrapingService;
use App\Services\Scraping\GlmExtractor;
use App\Services\Scraping\MiniMaxExtractor;
use App\Services\Scraping\MistralExtractor;
use Tests\TestCase;

class CarScrapingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Build a CarScrapingService with mocked extractors and an optional HTTP handler.
     *
     * @param  array<int,array<string,mixed>>  $fakeResults
     */
    private function makeService(array $fakeResults, ?\Closure $httpHandler = null): CarScrapingService
    {
        $mistral = \Mockery::mock(MistralExtractor::class);
        $minimax = \Mockery::mock(MiniMaxExtractor::class);
        $glm = \Mockery::mock(GlmExtractor::class);

        foreach ($fakeResults as $i => $r) {
            $mock = [$mistral, $minimax, $glm][$i] ?? null;
            if ($mock === null) {
                continue;
            }
            $mock->shouldReceive('name')->andReturn($r['provider']);
            $mock->shouldReceive('extract')->andReturn($r);
        }

        $service = new CarScrapingService($mistral, $minimax, $glm);

        if ($httpHandler !== null) {
            $service->setHttpHandler($httpHandler);
        }

        return $service;
    }

    public function test_rejects_unsupported_url(): void
    {
        $service = $this->makeService([]);
        $result = $service->scrape('https://example.com/listing/123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no soportada', $result['error']);
    }

    public function test_rejects_empty_url(): void
    {
        $service = $this->makeService([]);
        $result = $service->scrape('');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Empty URL', $result['error']);
    }

    public function test_first_extractor_succeeds(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'mobile.de')
            ? '<html>BMW 320d 85000 km Diesel</html>'
            : null;

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => [
                    'brand' => 'BMW', 'model' => '320d', 'year' => 2020,
                    'mileage' => 85000, 'fuel' => 'Diesel',
                ],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape('https://mobile.de/listing/12345');

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertEquals('mistral', $result['provider']);
        $this->assertEquals('BMW', $result['data']['brand']);
        $this->assertEquals(2020, $result['data']['year']);
        $this->assertFalse($result['cached']);
    }

    public function test_falls_back_to_secondary_when_primary_fails(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'mobile.de') ? '<html/>' : null;

        $service = $this->makeService([
            ['provider' => 'mistral', 'success' => false, 'error' => 'rate limit'],
            [
                'provider' => 'minimax',
                'success' => true,
                'data' => ['brand' => 'Audi', 'model' => 'A4'],
            ],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape('https://www.mobile.de/test');

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertEquals('minimax', $result['provider']);
        $this->assertEquals('Audi', $result['data']['brand']);
        $this->assertCount(2, $result['attempts']);
    }

    public function test_returns_error_when_all_extractors_fail(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'mobile.de') ? '<html/>' : null;

        $service = $this->makeService([
            ['provider' => 'mistral', 'success' => false, 'error' => 'fail 1'],
            ['provider' => 'minimax', 'success' => false, 'error' => 'fail 2'],
            ['provider' => 'glm', 'success' => false, 'error' => 'fail 3'],
        ], $httpHandler);

        $result = $service->scrape('https://mobile.de/test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Todos los extractores', $result['error']);
        $this->assertCount(3, $result['attempts']);
    }

    public function test_returns_error_when_fetch_fails(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'mobile.de') ? false : null;

        $service = $this->makeService([
            ['provider' => 'mistral', 'success' => true, 'data' => []],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape('https://mobile.de/test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('descargar', $result['error']);
    }

    public function test_uses_cache_on_second_call(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'mobile.de') ? '<html/>' : null;

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => ['brand' => 'Cached'],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $url = 'https://mobile.de/cache-test-' . uniqid();
        $first = $service->scrape($url);
        $this->assertTrue($first['success']);
        $this->assertFalse($first['cached']);

        $second = $service->scrape($url);
        $this->assertTrue($second['success']);
        $this->assertTrue($second['cached']);
        $this->assertEquals('Cached', $second['data']['brand']);
    }

    public function test_accepts_autoscout24_url(): void
    {
        $httpHandler = fn(string $url) => str_contains($url, 'autoscout24.de') ? '<html/>' : null;

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => ['brand' => 'VW', 'model' => 'Golf'],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape('https://www.autoscout24.de/angebote/vw-golf');

        $this->assertTrue($result['success']);
        $this->assertEquals('VW', $result['data']['brand']);
    }

    /**
     * @dataProvider spanishPortalProvider
     */
    public function test_accepts_spanish_portals(string $url, string $expectedHost): void
    {
        $httpHandler = fn(string $u) => str_contains($u, $expectedHost) ? '<html/>' : null;

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => ['brand' => 'Seat', 'model' => 'Ibiza'],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape($url);
        $this->assertTrue($result['success'], "Failed for {$expectedHost}: " . json_encode($result));
        $this->assertEquals('Seat', $result['data']['brand']);
    }

    public static function spanishPortalProvider(): array
    {
        return [
            'autoscout24.es' => ['https://www.autoscout24.es/anuncios/seat-ibiza', 'autoscout24.es'],
            'coches.com' => ['https://www.coches.com/seat-ibiza-12345.htm', 'coches.com'],
            'milanuncios.com' => ['https://www.milanuncios.com/seat-ibiza', 'milanuncios.com'],
            'wallapop.com' => ['https://www.wallapop.com/item/seat-ibiza-12345', 'wallapop.com'],
            'coches.net' => ['https://www.coches.net/seat-ibiza', 'coches.net'],
            'autovit.ro' => ['https://www.autovit.ro/seat-ibiza', 'autovit.ro'],
            'olx.ro' => ['https://www.olx.ro/oferta/seat-ibiza-ID123ABC.html', 'olx.ro'],
        ];
    }

    public function test_extracts_photo_urls_from_html(): void
    {
        $html = <<<HTML
<html>
<head>
<meta property="og:image" content="https://www.autoscout24.de/images/car-1.jpg" />
<meta property="og:image" content="https://www.autoscout24.de/images/car-2.jpg" />
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Vehicle","image":["https://www.autoscout24.de/images/ld-1.jpg","https://www.autoscout24.de/images/ld-2.jpg"]}
</script>
</head>
<body>
<img src="https://www.autoscout24.de/images/gallery-1.jpg" data-src="https://www.autoscout24.de/images/gallery-2.jpg" />
<img src="https://www.autoscout24.de/images/1x1-pixel.gif" />
<div style="background-image: url('https://www.autoscout24.de/images/bg-1.jpg');"></div>
</body>
</html>
HTML;

        $httpHandler = fn(string $url) => $html;

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => ['brand' => 'BMW'],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $result = $service->scrape('https://www.autoscout24.de/listing/12345');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('photos', $result);
        $this->assertGreaterThan(0, count($result['photos']));

        // 1x1 tracking pixel should be filtered
        foreach ($result['photos'] as $photo) {
            $this->assertStringNotContainsString('1x1-pixel', $photo);
            $this->assertStringStartsWith('http', $photo);
        }

        // Should include og:image, JSON-LD, and gallery images
        $joined = implode(' ', $result['photos']);
        $this->assertStringContainsString('car-1.jpg', $joined);
    }

    public function test_logs_structured_info_on_success(): void
    {
        $captured = [];

        \Illuminate\Support\Facades\Log::swap(new class($captured) extends \Illuminate\Log\Logger {
            public function __construct(array &$captured)
            {
                $this->captured = &$captured;
            }
            public function emergency($message, array $context = []): void { $this->captured[] = ['emergency', $message, $context]; }
            public function alert($message, array $context = []): void     { $this->captured[] = ['alert', $message, $context]; }
            public function critical($message, array $context = []): void   { $this->captured[] = ['critical', $message, $context]; }
            public function error($message, array $context = []): void     { $this->captured[] = ['error', $message, $context]; }
            public function warning($message, array $context = []): void   { $this->captured[] = ['warning', $message, $context]; }
            public function notice($message, array $context = []): void    { $this->captured[] = ['notice', $message, $context]; }
            public function info($message, array $context = []): void      { $this->captured[] = ['info', $message, $context]; }
            public function debug($message, array $context = []): void     { $this->captured[] = ['debug', $message, $context]; }
            public function log($level, $message, array $context = []): void { $this->captured[] = [$level, $message, $context]; }
        });

        $httpHandler = fn(string $url) => '<html/>';

        $service = $this->makeService([
            [
                'provider' => 'mistral',
                'success' => true,
                'data' => ['brand' => 'Audi'],
            ],
            ['provider' => 'minimax', 'success' => true, 'data' => []],
            ['provider' => 'glm', 'success' => true, 'data' => []],
        ], $httpHandler);

        $service->scrape('https://www.autoscout24.de/listing/log-test-' . uniqid());

        $messages = array_map(fn ($entry) => $entry[1] ?? null, $captured);
        $this->assertContains('Car scraping started', $messages);
        $this->assertContains('Car scraping success', $messages);
    }
}
