<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrichedValuationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tests para los campos enriquecidos del modelo Car (skill importacion-vehiculos).
     * Versión PHPUnit (migrada desde Pest); usa User con organization_id directo (belongsTo).
     */
    public function test_enriched_valuation_fields_exist_in_database(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => [
                'history' => [
                    'finding' => '2 owners',
                    'source_url' => 'https://example.com',
                    'value' => 2,
                    'date' => '2026-08-01',
                ],
            ],
            'pros' => [
                ['point' => 'Good maintenance', 'weight' => 'high'],
                ['point' => 'Low mileage', 'weight' => 'medium'],
            ],
            'cons' => [
                ['point' => 'High price', 'weight' => 'high'],
            ],
            'verdict' => 'Buy',
            'verdict_confidence' => 'high',
            'verdict_reasoning' => 'Well maintained with low mileage',
            'verdict_changes' => 'Price needs to drop by €500',
            'verdict_at' => now(),
            'market_avg' => 25000.00,
            'market_min' => 22000.00,
            'market_max' => 28000.00,
            'estimated_saving' => 3000.00,
            'research_source' => 'app',
            'schema_version' => 1,
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'verdict' => 'Buy',
            'verdict_confidence' => 'high',
            'research_source' => 'app',
            'schema_version' => 1,
        ]);

        $this->assertIsArray($car->research);
        $this->assertArrayHasKey('history', $car->research);
        $this->assertIsArray($car->pros);
        $this->assertIsArray($car->cons);
    }

    public function test_enriched_valuation_fields_have_proper_casts(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => ['test' => 'data'],
            'pros' => [],
            'cons' => [],
            'market_avg' => '25000.00',
            'verdict_at' => '2026-08-01 12:00:00',
        ]);

        $this->assertIsArray($car->research);
        $this->assertIsArray($car->pros);
        $this->assertIsArray($car->cons);
        // SQLite returns DECIMAL as string; check numeric value instead of strict float type
        $this->assertEquals(25000.00, (float) $car->market_avg);
        $this->assertInstanceOf(Carbon::class, $car->verdict_at);
    }

    public function test_verdict_enum_constraints(): void
    {
        $this->assertContains('Buy', Car::VERDICTS);
        $this->assertContains('Buy if price drops', Car::VERDICTS);
        $this->assertContains('Doubtful', Car::VERDICTS);
        $this->assertContains('Discard', Car::VERDICTS);
    }

    public function test_verdict_confidence_enum_constraints(): void
    {
        $this->assertContains('high', Car::VERDICT_CONFIDENCE);
        $this->assertContains('medium', Car::VERDICT_CONFIDENCE);
        $this->assertContains('low', Car::VERDICT_CONFIDENCE);
    }

    public function test_research_aspects_constant(): void
    {
        $this->assertIsArray(Car::RESEARCH_ASPECTS);
        $this->assertCount(9, Car::RESEARCH_ASPECTS);
    }

    public function test_get_research_gaps_attribute_returns_missing_aspects(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => [
                'common_issues' => ['finding' => 'Recurring turbo failure'],
                'recalls' => ['finding' => 'Recall 2020'],
            ],
        ]);

        $gaps = $car->research_gaps;

        $this->assertIsArray($gaps);
        $this->assertCount(7, $gaps);
        $this->assertSame('market_price', $gaps[0]);
    }

    public function test_set_research_aspect_adds_or_updates_aspect(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => [],
        ]);

        $car->setResearchAspect('common_issues', [
            'finding' => 'Turbo failure at 120k km',
            'source_url' => 'https://example.com',
            'value' => 1,
            'date' => '2026-08-01',
        ]);

        $car->save();

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
        ]);

        $car->refresh();
        $this->assertSame('Turbo failure at 120k km', $car->research['common_issues']['finding']);
    }

    public function test_verdict_at_null_when_verdict_not_issued(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'verdict' => null,
        ]);

        $this->assertNull($car->verdict_at);
    }

    public function test_schema_version_defaults_to_1(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
        ]);

        $car->refresh();
        $this->assertEquals(1, $car->schema_version);
    }

    public function test_market_data_calculations(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'market_avg' => 25000.00,
            'market_min' => 22000.00,
            'market_max' => 28000.00,
            'estimated_saving' => 3000.00,
        ]);

        // SQLite returns DECIMAL as string; use assertEquals with numeric cast
        $this->assertEquals(25000.00, (float) $car->market_avg);
        $this->assertEquals(22000.00, (float) $car->market_min);
        $this->assertEquals(28000.00, (float) $car->market_max);
        $this->assertEquals(3000.00, (float) $car->estimated_saving);
    }
}
