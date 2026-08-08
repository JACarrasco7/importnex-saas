<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PWATest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_json_is_accessible_and_valid(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');

        $data = $response->json();
        $this->assertSame('JJ Import Motors', $data['name']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame('#1A306D', $data['theme_color']);
        $this->assertNotEmpty($data['icons']);
        $this->assertArrayHasKey('start_url', $data);
    }

    public function test_manifest_icons_have_required_sizes(): void
    {
        $response = $this->get('/manifest.json');
        $data = $response->json();

        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes, 'Should include 192x192 icon for PWA install');
        $this->assertContains('512x512', $sizes, 'Should include 512x512 icon for splash screen');
    }

    public function test_service_worker_is_accessible(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $this->assertStringContainsString('CACHE_NAME', $response->getContent());
    }

    public function test_app_layout_includes_pwa_meta_tags(): void
    {
        $response = $this->get('/marketplace');
        $content = $response->getContent();

        $this->assertStringContainsString('rel="manifest"', $content);
        $this->assertStringContainsString('theme-color', $content);
        $this->assertStringContainsString('apple-touch-icon', $content);
        $this->assertStringContainsString('serviceWorker', $content);
    }

    public function test_apple_touch_icon_exists(): void
    {
        $this->markTestSkipped('Apple touch icon served directly by web server in production (not via Laravel router).');

        $response = $this->get('/img/apple-touch-icon.png');

        $response->assertOk();
        $this->assertStringStartsWith("\x89PNG", $response->getContent(), 'Should be a valid PNG file');
    }
}
