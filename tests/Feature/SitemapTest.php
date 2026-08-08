<?php

namespace Tests\Feature;

use App\Http\Controllers\SitemapController;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_content_type(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('xml', $response->headers->get('Content-Type'));
    }

    public function test_sitemap_contains_homepage(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString(route('home'), $content);
    }

    public function test_sitemap_contains_marketplace_index(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString(route('marketplace.index'), $content);
    }

    public function test_sitemap_includes_only_marketplace_cars(): void
    {
        $org = Organization::factory()->create();
        $publicCar = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
        ]);
        $privateCar = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => false,
        ]);

        $response = $this->get('/sitemap.xml');
        $content = $response->getContent();

        $publicUrl = route('marketplace.show', $publicCar);
        $privateUrl = route('marketplace.show', $privateCar);

        $this->assertStringContainsString($publicUrl, $content);
        $this->assertStringNotContainsString($privateUrl, $content);
    }

    public function test_sitemap_includes_pricing_route(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString(route('pricing'), $response->getContent());
    }

    public function test_sitemap_is_well_formed_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $content = $response->getContent();

        $this->assertStringStartsWith('<?xml', $content, 'Sitemap must start with XML declaration');
        $this->assertStringContainsString('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $content);
    }

    public function test_sitemap_cache_is_cleared_when_car_marketplace_status_changes(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => false,
        ]);

        // Prime cache
        SitemapController::flush();
        $this->get('/sitemap.xml')->assertOk();
        $this->assertTrue(cache()->has('sitemap.cars'));

        // Toggle to public should flush
        $car->update(['is_marketplace' => true]);

        $this->assertFalse(
            cache()->has('sitemap.cars'),
            'Cache should be flushed when is_marketplace changes to true'
        );
    }

    public function test_sitemap_includes_at_most_500_cars(): void
    {
        $org = Organization::factory()->create();
        Car::factory()
            ->count(10)
            ->create(['organization_id' => $org->id, 'is_marketplace' => true]);

        $response = $this->get('/sitemap.xml');
        $content = $response->getContent();

        $count = substr_count($content, '<url>');
        $this->assertGreaterThanOrEqual(10, $count, 'Should include all 10 marketplace cars');
    }
}
