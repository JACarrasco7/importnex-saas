<?php

namespace Tests\Unit\Services\Scraping;

use App\Models\Organization;
use App\Services\Scraping\CarScrapingService;
use App\Services\Scraping\GenericAiExtractor;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarScrapingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Build a CarScrapingService with a mocked GenericAiExtractor that returns
     * a configurable result, plus a fake HTTP layer for the page fetch.
     *
     * @param  array<string,mixed>  $extractorResult
     */
    private function makeService(array $extractorResult, ?string $body = '<html/>'): CarScrapingService
    {
        $extractor = Mockery::mock(GenericAiExtractor::class);
        $extractor->shouldReceive('extract')->andReturn($extractorResult);

        $service = new CarScrapingService($extractor);

        if ($body !== null) {
            Http::fake([
                '*' => Http::response($body, 200),
            ]);
        } else {
            Http::fake([
                '*' => Http::response('', 500),
            ]);
        }

        return $service;
    }

    /** Build an in-memory org (no DB save) — just enough for `hasAiConfigured`. */
    private function makeOrg(): Organization
    {
        $org = new Organization([
            'name' => 'Test Org',
            'ai_provider' => 'mistral',
            'ai_api_key' => 'mock-key',
        ]);

        return $org;
    }

    public function test_rejects_unsupported_url(): void
    {
        $service = $this->makeService(['success' => true, 'data' => []]);
        $result = $service->scrape('https://example.com/listing/123', $this->makeOrg());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no soportada', $result['error']);
    }

    public function test_rejects_empty_url(): void
    {
        $service = $this->makeService(['success' => true, 'data' => []]);
        $result = $service->scrape('', $this->makeOrg());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Empty URL', $result['error']);
    }

    public function test_extractor_succeeds(): void
    {
        $service = $this->makeService([
            'success' => true,
            'provider' => 'mistral',
            'data' => [
                'brand' => 'BMW', 'model' => '320d', 'year' => 2020,
                'mileage' => 85000, 'fuel' => 'Diesel',
            ],
        ]);

        $result = $service->scrape('https://mobile.de/listing/12345', $this->makeOrg());

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertEquals('mistral', $result['provider']);
        $this->assertEquals('BMW', $result['data']['brand']);
        $this->assertFalse($result['cached']);
    }

    public function test_returns_error_when_extractor_fails(): void
    {
        $service = $this->makeService([
            'success' => false,
            'provider' => 'mistral',
            'error' => 'rate limit',
        ]);

        $result = $service->scrape('https://mobile.de/test', $this->makeOrg());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rate limit', $result['error']);
    }

    public function test_returns_error_when_fetch_fails(): void
    {
        $service = $this->makeService(
            ['success' => true, 'provider' => 'mistral', 'data' => []],
            null,
        );

        $result = $service->scrape('https://mobile.de/test', $this->makeOrg());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('descargar', $result['error']);
    }

    public function test_uses_cache_on_second_call(): void
    {
        $extractor = Mockery::mock(GenericAiExtractor::class);
        $extractor->shouldReceive('extract')->once()->andReturn([
            'success' => true, 'provider' => 'mistral',
            'data' => ['brand' => 'Cached'],
        ]);

        Http::fake([
            '*' => Http::response('<html/>', 200),
        ]);

        $service = new CarScrapingService($extractor);
        $url = 'https://mobile.de/cache-'.uniqid();
        $org = $this->makeOrg();

        $first = $service->scrape($url, $org);
        $second = $service->scrape($url, $org);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertTrue($second['cached']);
        $this->assertEquals('Cached', $second['data']['brand']);
    }

    public function test_accepts_autoscout24_url(): void
    {
        $service = $this->makeService([
            'success' => true, 'provider' => 'mistral',
            'data' => ['brand' => 'VW', 'model' => 'Golf'],
        ]);

        $result = $service->scrape('https://www.autoscout24.de/angebote/vw-golf', $this->makeOrg());
        $this->assertTrue($result['success']);
        $this->assertEquals('VW', $result['data']['brand']);
    }

    /**
     * @dataProvider spanishPortalProvider
     */
    #[DataProvider('spanishPortalProvider')]
    public function test_accepts_spanish_portals(string $url, string $expectedHost): void
    {
        $service = $this->makeService([
            'success' => true, 'provider' => 'mistral',
            'data' => ['brand' => 'Seat', 'model' => 'Ibiza'],
        ]);

        $result = $service->scrape($url, $this->makeOrg());
        $this->assertTrue($result['success'], "Failed for {$expectedHost}: ".json_encode($result));
        $this->assertEquals('Seat', $result['data']['brand']);
    }

    public static function spanishPortalProvider(): array
    {
        return [
            'autoscout24.es' => ['https://www.autoscout24.es/anuncios/seat-ibiza', 'autoscout24.es'],
            'coches.com' => ['https://www.coches.com/seat-ibiza-12345.htm', 'coches.com'],
            'milanuncios' => ['https://www.milanuncios.com/seat-ibiza', 'milanuncios.com'],
            'wallapop' => ['https://www.wallapop.com/item/seat-ibiza-12345', 'wallapop.com'],
            'coches.net' => ['https://www.coches.net/seat-ibiza', 'coches.net'],
            'autovit.ro' => ['https://www.autovit.ro/seat-ibiza', 'autovit.ro'],
            'olx.ro' => ['https://www.olx.ro/oferta/seat-ibiza-ID123ABC.html', 'olx.ro'],
        ];
    }
}
