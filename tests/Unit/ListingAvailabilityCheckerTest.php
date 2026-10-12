<?php

namespace Tests\Unit;

use App\Services\Marketplace\ListingAvailabilityChecker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ListingAvailabilityCheckerTest extends TestCase
{
    private ListingAvailabilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new ListingAvailabilityChecker;
    }

    public function test_empty_url_returns_skipped(): void
    {
        $r = $this->checker->check('');
        $this->assertTrue($r['available']);
        $this->assertSame('skipped', $r['reason']);
    }

    public function test_non_http_url_returns_skipped(): void
    {
        $r = $this->checker->check('ftp://example.com');
        $this->assertTrue($r['available']);
        $this->assertSame('skipped', $r['reason']);
    }

    public function test_200_with_normal_page_is_available(): void
    {
        Http::fake(['*' => Http::response('<html>BMW 320d disponible, contacta.</html>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertTrue($r['available']);
        $this->assertSame('available', $r['reason']);
        $this->assertSame(200, $r['http_status']);
    }

    public function test_404_marks_unavailable(): void
    {
        Http::fake(['*' => Http::response('Not Found', 404)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_404', $r['reason']);
    }

    public function test_410_marks_unavailable(): void
    {
        Http::fake(['*' => Http::response('Gone', 410)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_404', $r['reason']);
    }

    public function test_503_does_not_mark_unavailable(): void
    {
        Http::fake(['*' => Http::response('Internal Server Error', 503)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertTrue($r['available']);
        $this->assertSame('url_5xx', $r['reason']);
    }

    public function test_403_does_not_mark_unavailable(): void
    {
        Http::fake(['*' => Http::response('Forbidden', 403)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertTrue($r['available']);
        $this->assertSame('url_other', $r['reason']);
    }

    public function test_timeout_does_not_mark_unavailable(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Operation timed out');
        });
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertTrue($r['available']);
        $this->assertSame('url_timeout', $r['reason']);
    }

    public function test_sold_keyword_spanish_vendido(): void
    {
        Http::fake(['*' => Http::response('<html>Este coche ya ha sido vendido.</html>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
        $this->assertStringContainsString('vendido', $r['message']);
    }

    public function test_sold_keyword_spanish_no_disponible(): void
    {
        Http::fake(['*' => Http::response('<div>No disponible</div>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
        $this->assertStringContainsString('no disponible', $r['message']);
    }

    public function test_sold_keyword_english_sold(): void
    {
        Http::fake(['*' => Http::response('<p>Vehicle has been sold.</p>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
    }

    public function test_sold_keyword_english_no_longer_available(): void
    {
        Http::fake(['*' => Http::response('This listing is no longer available', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
    }

    public function test_sold_keyword_german_verkauft(): void
    {
        Http::fake(['*' => Http::response('<span>Das Auto wurde verkauft.</span>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
    }

    public function test_sold_keyword_german_nicht_verfuegbar(): void
    {
        Http::fake(['*' => Http::response('<h1>Nicht verfügbar</h1>', 200)]);
        $r = $this->checker->check('https://example.com/ad/1');
        $this->assertFalse($r['available']);
        $this->assertSame('url_sold_keyword', $r['reason']);
    }
}
