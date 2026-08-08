<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketplaceFilterCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_filter_options_are_cached(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
            'brand' => 'Audi',
            'fuel' => 'Diesel',
            'color' => 'Black',
        ]);

        // Clear any existing cache
        Cache::forget('marketplace.filter_options');

        // First request: cache miss, populates cache
        $this->get('/marketplace')->assertOk();
        $this->assertTrue(Cache::has('marketplace.filter_options'), 'Cache should be populated after first request');

        // Verify cache contains expected structure
        $cached = Cache::get('marketplace.filter_options');
        $this->assertArrayHasKey('brands', $cached);
        $this->assertArrayHasKey('fuels', $cached);
        $this->assertArrayHasKey('transmissions', $cached);
        $this->assertArrayHasKey('doors', $cached);
        $this->assertArrayHasKey('colors', $cached);
        $this->assertContains('Audi', $cached['brands']);
        $this->assertContains('Diesel', $cached['fuels']);
    }

    public function test_marketplace_cache_returns_same_filter_options(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
            'brand' => 'BMW',
        ]);

        Cache::forget('marketplace.filter_options');

        // Trigger first request to populate cache
        $this->get('/marketplace')->assertOk();
        $cached = Cache::get('marketplace.filter_options');

        // Trigger second request (should use cache, data should be identical)
        $this->get('/marketplace')->assertOk();
        $cachedAfter = Cache::get('marketplace.filter_options');

        $this->assertSame($cached, $cachedAfter, 'Cache should return identical results');
        $this->assertContains('BMW', $cached['brands']);
    }

    public function test_cache_flushes_when_car_is_created_with_is_marketplace(): void
    {
        Cache::put('marketplace.filter_options', ['brands' => [], 'fuels' => [], 'transmissions' => [], 'doors' => [], 'colors' => []], 60);

        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        $this->assertFalse(
            Cache::has('marketplace.filter_options'),
            'Cache should be flushed when new marketplace car is created'
        );
    }

    public function test_cache_flushes_when_is_marketplace_toggled(): void
    {
        Cache::put('marketplace.filter_options', ['stale' => true], 60);

        $org = Organization::factory()->create(['is_public' => true]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => false,
        ]);

        Cache::put('marketplace.filter_options', ['fresh' => true], 60);

        $car->update(['is_marketplace' => true]);

        $this->assertFalse(
            Cache::has('marketplace.filter_options'),
            'Cache should flush when is_marketplace changes'
        );
    }

    public function test_filter_options_have_expected_structure(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
            'brand' => 'Mercedes',
            'fuel' => 'Gasoline',
        ]);

        Cache::forget('marketplace.filter_options');
        $this->get('/marketplace')->assertOk();
        $options = Cache::get('marketplace.filter_options');

        $this->assertIsArray($options);
        $this->assertArrayHasKey('brands', $options);
        $this->assertArrayHasKey('fuels', $options);
        $this->assertArrayHasKey('transmissions', $options);
        $this->assertArrayHasKey('doors', $options);
        $this->assertArrayHasKey('colors', $options);
        $this->assertContains('Mercedes', $options['brands']);
        $this->assertContains('Gasoline', $options['fuels']);
    }
}
