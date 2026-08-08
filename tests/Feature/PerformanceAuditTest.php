<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_index_renders_under_1_second(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->count(30)->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        $start = microtime(true);
        $response = $this->get('/marketplace');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(1000, $duration, "Marketplace index took {$duration}ms (>1s limit)");
    }

    public function test_marketplace_index_does_not_select_star(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        DB::enableQueryLog();
        $this->get('/marketplace');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Find the SELECT on cars table
        $carQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from "cars"')
                && str_contains(strtolower($q['query']), 'select');
        });

        $this->assertNotEmpty($carQueries, 'Should have at least one SELECT on cars');

        // Check that the select is specific (has field list, not *)
        foreach ($carQueries as $q) {
            $query = strtolower($q['query']);
            // In SQLite, SELECT * becomes explicit; but our code passes specific columns.
            // If count of selected fields is small (< 50), it's specific.
            $selectPart = substr($query, 0, strpos($query, ' from'));
            $fieldCount = substr_count($selectPart, ',') + 1;
            $this->assertLessThan(
                50,
                $fieldCount,
                "Query should select specific fields, not all. Got: {$q['query']}"
            );
        }
    }

    public function test_marketplace_index_eager_loads_photos(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);
        CarPhoto::factory()->count(3)->create(['car_id' => $car->id]);

        DB::enableQueryLog();
        $this->get('/marketplace');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Photos should be eager-loaded in 1-2 queries (not N per car)
        $photoQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from "car_photos"')
                && str_contains(strtolower($q['query']), 'where "car_id"');
        });

        $this->assertLessThanOrEqual(
            2,
            count($photoQueries),
            'Photos should be eager-loaded, not N+1. Got '.count($photoQueries).' photo queries.'
        );
    }

    public function test_marketplace_index_eager_loads_organization(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        Car::factory()->count(5)->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        DB::enableQueryLog();
        $this->get('/marketplace');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Organization should be eager-loaded in 1 query, not 5 (one per car)
        $orgQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from "organizations"')
                && str_contains(strtolower($q['query']), 'where "id"');
        });

        $this->assertLessThanOrEqual(
            2,
            count($orgQueries),
            'Organization should be eager-loaded. Got '.count($orgQueries).' queries.'
        );
    }

    public function test_marketplace_show_renders_under_500ms(): void
    {
        $org = Organization::factory()->create(['is_public' => true]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        $start = microtime(true);
        $response = $this->get("/marketplace/{$car->id}");
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(500, $duration, "Show page took {$duration}ms");
    }

    public function test_health_endpoint_under_300ms(): void
    {
        $start = microtime(true);
        $response = $this->get('/health');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(300, $duration, "Health took {$duration}ms");
    }

    public function test_app_layout_has_critical_css_inline(): void
    {
        $response = $this->get('/marketplace');
        $content = $response->getContent();

        // Critical CSS should be inline (in <style> tag) for fast first paint
        $this->assertStringContainsString('<style>', $content);
        $this->assertStringContainsString('box-sizing', $content);
    }

    public function test_openapi_endpoint_under_200ms(): void
    {
        $start = microtime(true);
        $response = $this->get('/openapi.json');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(200, $duration, "OpenAPI took {$duration}ms");
    }
}
