<?php

namespace Tests\Unit;

use App\Support\UrlNormalizer;
use PHPUnit\Framework\TestCase;

class UrlNormalizerTest extends TestCase
{
    /**
     * §2.1 — elimina query string, fragmentos y trailing slash
     */
    public function test_normalizes_full_url(): void
    {
        $url = 'https://www.mobile.de/fahrzeuge/details.html?id=123&utm_source=google#photos';
        $expected = 'https://www.mobile.de/fahrzeuge/details.html';

        $this->assertSame($expected, UrlNormalizer::normalize($url));
    }

    public function test_removes_trailing_slash(): void
    {
        $this->assertSame(
            'https://example.com/path',
            UrlNormalizer::normalize('https://example.com/path/')
        );
    }

    public function test_removes_query_string(): void
    {
        $this->assertSame(
            'https://example.com/path',
            UrlNormalizer::normalize('https://example.com/path?foo=bar&baz=1')
        );
    }

    public function test_removes_fragment(): void
    {
        $this->assertSame(
            'https://example.com/path',
            UrlNormalizer::normalize('https://example.com/path#section')
        );
    }

    public function test_returns_null_for_empty_input(): void
    {
        $this->assertNull(UrlNormalizer::normalize(null));
        $this->assertNull(UrlNormalizer::normalize(''));
        $this->assertNull(UrlNormalizer::normalize('   '));
    }

    public function test_trims_whitespace(): void
    {
        $this->assertSame(
            'https://example.com/path',
            UrlNormalizer::normalize('  https://example.com/path/  ')
        );
    }

    public function test_leaves_clean_url_unchanged(): void
    {
        $url = 'https://www.mobile.de/fahrzeuge/details.html';
        $this->assertSame($url, UrlNormalizer::normalize($url));
    }

    /**
     * §2.1 — método same() compara URLs normalizadas
     */
    public function test_same_compares_normalized_urls(): void
    {
        $url1 = 'https://example.com/path?utm_source=google';
        $url2 = 'https://example.com/path?utm_source=facebook';
        $url3 = 'https://example.com/other';

        $this->assertTrue(UrlNormalizer::same($url1, $url2));
        $this->assertFalse(UrlNormalizer::same($url1, $url3));
    }

    public function test_same_handles_nulls(): void
    {
        $this->assertTrue(UrlNormalizer::same(null, null));
        $this->assertTrue(UrlNormalizer::same('', ''));
        $this->assertFalse(UrlNormalizer::same('https://example.com', null));
    }

    public function test_handles_real_mobile_de_urls(): void
    {
        $url = 'https://suchen.mobile.de/fahrzeuge/search.html?dam=false&isSearchRequest=true&ms=25200;;29;&p=10000:30000&lang=de';
        $expected = 'https://suchen.mobile.de/fahrzeuge/search.html';

        $this->assertSame($expected, UrlNormalizer::normalize($url));
    }
}
