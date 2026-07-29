<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarShowEnrichedTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_enriched_valuation_data(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
            'research' => [
                'common_issues' => ['finding' => 'Turbo wear', 'source' => 'https://forum.example', 'rating' => 'unfavorable', 'date' => '2026-07-29'],
                'recalls'       => ['finding' => 'No recalls', 'source' => 'https://dgt.example', 'rating' => 'favorable', 'date' => '2026-07-29'],
            ],
            'pros' => [
                ['text' => 'Low mileage', 'weight' => 'high'],
            ],
            'cons' => [
                ['text' => 'Turbo', 'weight' => 'high'],
            ],
            'verdict' => 'Buy if price drops',
            'verdict_confidence' => 'medium',
            'verdict_reasoning' => 'Sample reasoning.',
            'verdict_changes' => 'If 500€ lower.',
            'verdict_at' => now(),
            'market_avg' => 16000,
            'market_min' => 15000,
            'market_max' => 17500,
            'estimated_saving' => 700,
            'comparables_list' => [
                ['title' => 'Sample A', 'price' => 16200, 'km' => 80000, 'url' => 'https://a.example', 'country' => 'España'],
            ],
            'purchase_price' => 12900,
            'transport' => 1200,
            'itv_fee' => 95,
            'dgt_fees' => 20.61,
        ]);

        $response = $this->actingAs($user)->get("/cars/{$car->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Show')
            ->has('car')
            ->has('derived.total_cost')
            ->has('derived.iedmt')
            ->has('derived.research_gaps')
            ->has('derived.comparables_stats')
            ->has('derived.milestones_progress')
            ->has('derived.inspections_progress')
            ->has('derived.inspections_by_section')
            ->has('derived.documents_by_group')
        );

        $page = $this->getInertiaProps($response);
        $this->assertGreaterThan(0, $page['derived']['total_cost']);
        $this->assertSame(1, $page['derived']['comparables_stats']['count']);
        $this->assertSame(6, $page['derived']['milestones_progress']['total']);
        $this->assertSame(80, $page['derived']['inspections_progress']['total']);
    }

    public function test_show_research_gaps_reports_missing_aspects(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        // Only fills one aspect; 8 should be missing
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'research' => [
                'recalls' => ['finding' => 'No recalls', 'source' => 'https://x', 'rating' => 'favorable'],
            ],
        ]);

        $response = $this->actingAs($user)->get("/cars/{$car->id}");
        $response->assertStatus(200);

        $page = $this->getInertiaProps($response);
        $this->assertCount(8, $page['derived']['research_gaps']);
    }

    public function test_show_groups_inspections_by_section(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get("/cars/{$car->id}");
        $response->assertStatus(200);

        $page = $this->getInertiaProps($response);
        $this->assertCount(8, $page['derived']['inspections_by_section']);
    }

    public function test_show_groups_documents_by_phase(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get("/cars/{$car->id}");
        $response->assertStatus(200);

        $page = $this->getInertiaProps($response);
        $this->assertCount(3, $page['derived']['documents_by_group']);
    }

    private function getInertiaProps($response): array
    {
        $response->assertViewHas('page');
        return json_decode(json_encode($response->viewData('page')), true)['props'];
    }
}
