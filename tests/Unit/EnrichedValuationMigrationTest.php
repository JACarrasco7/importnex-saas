<?php

use App\Models\Car;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrichedValuationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_valuation_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('cars', 'research'));
        $this->assertTrue(Schema::hasColumn('cars', 'pros'));
        $this->assertTrue(Schema::hasColumn('cars', 'cons'));
        $this->assertTrue(Schema::hasColumn('cars', 'verdict'));
        $this->assertTrue(Schema::hasColumn('cars', 'verdict_confidence'));
        $this->assertTrue(Schema::hasColumn('cars', 'verdict_reasoning'));
        $this->assertTrue(Schema::hasColumn('cars', 'verdict_changes'));
        $this->assertTrue(Schema::hasColumn('cars', 'verdict_at'));
        $this->assertTrue(Schema::hasColumn('cars', 'market_avg'));
        $this->assertTrue(Schema::hasColumn('cars', 'market_min'));
        $this->assertTrue(Schema::hasColumn('cars', 'market_max'));
        $this->assertTrue(Schema::hasColumn('cars', 'estimated_saving'));
        $this->assertTrue(Schema::hasColumn('cars', 'research_source'));
        $this->assertTrue(Schema::hasColumn('cars', 'schema_version'));
    }

    public function test_research_field_stores_json(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => [
                'common_issues' => [
                    'finding' => 'Timing chain tensioner failure at 80k km',
                    'source_url' => 'https://example.com/issues/audi-a3',
                    'value' => 'negative',
                    'date' => '2026-08-08',
                ],
            ],
        ]);

        $this->assertIsArray($car->research);
        $this->assertArrayHasKey('common_issues', $car->research);
    }

    public function test_pros_and_cons_store_weighted_lists(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'pros' => [
                ['text' => 'Full service history', 'weight' => 'high'],
                ['text' => 'Low mileage', 'weight' => 'medium'],
            ],
            'cons' => [
                ['text' => 'Timing chain issue', 'weight' => 'high'],
                ['text' => 'Scratch on door', 'weight' => 'low'],
            ],
        ]);

        $this->assertCount(2, $car->pros);
        $this->assertEquals('high', $car->pros[0]['weight']);
        $this->assertCount(2, $car->cons);
    }

    public function test_verdict_enum_values(): void
    {
        $validVerdicts = ['Buy', 'Buy if price drops', 'Doubtful', 'Discard'];

        foreach ($validVerdicts as $verdict) {
            $car = Car::factory()->create(['verdict' => $verdict]);
            $this->assertEquals($verdict, $car->verdict);
        }
    }

    public function test_verdict_confidence_enum_values(): void
    {
        $validConfidences = ['high', 'medium', 'low'];

        foreach ($validConfidences as $confidence) {
            $car = Car::factory()->create(['verdict_confidence' => $confidence]);
            $this->assertEquals($confidence, $car->verdict_confidence);
        }
    }

    public function test_market_data_stores_decimals(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'market_avg' => 15000.00,
            'market_min' => 12000.00,
            'market_max' => 18000.00,
            'estimated_saving' => 3000.00,
        ]);

        $this->assertEquals(15000.00, $car->market_avg);
        $this->assertEquals(12000.00, $car->market_min);
        $this->assertEquals(18000.00, $car->market_max);
        $this->assertEquals(3000.00, $car->estimated_saving);
    }

    public function test_research_source_enum(): void
    {
        $validSources = ['chat', 'app', 'manual'];

        foreach ($validSources as $source) {
            $car = Car::factory()->create(['research_source' => $source]);
            $this->assertEquals($source, $car->research_source);
        }
    }

    public function test_schema_version_default_is_1(): void
    {
        $car = Car::factory()->create();
        $this->assertEquals(1, $car->schema_version);
    }

    public function test_verdict_at_is_datetime(): void
    {
        $now = now();
        $car = Car::factory()->create(['verdict_at' => $now]);

        $this->assertInstanceOf(Carbon::class, $car->verdict_at);
        $this->assertEquals($now->toDateTimeString(), $car->verdict_at->toDateTimeString());
    }
}
