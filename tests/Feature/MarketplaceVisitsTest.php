<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\MarketplaceVisit;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceVisitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_starts_at_zero_and_first_view_returns_one(): void
    {
        $response = $this->get('/marketplace');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->where('visitsCount', 1));
    }

    public function test_each_human_view_increments_counter_by_one(): void
    {
        $this->get('/marketplace');
        $this->get('/marketplace');
        $this->get('/marketplace');

        $row = MarketplaceVisit::current();
        $this->assertSame(3, (int) $row->visits_count);
    }

    public function test_bot_user_agents_do_not_inflate_counter(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get('/marketplace');

        $this->withHeaders(['User-Agent' => 'curl/7.68.0'])
            ->get('/marketplace');

        $this->withHeaders(['User-Agent' => 'python-requests/2.31.0'])
            ->get('/marketplace');

        $row = MarketplaceVisit::current();
        $this->assertSame(0, (int) $row->visits_count);

        // Reset del UA sticky y forzar uno humano.
        $this->flushHeaders();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
            ->get('/marketplace');
        $this->assertSame(1, (int) MarketplaceVisit::current()->visits_count);
    }

    public function test_singleton_row_is_persisted_at_id_one(): void
    {
        $this->get('/marketplace');
        $this->get('/marketplace');

        $this->assertDatabaseHas('marketplace_visits', [
            'id' => 1,
            'visits_count' => 2,
        ]);
        $this->assertSame(1, MarketplaceVisit::query()->count());
    }

    public function test_counter_is_incremented_even_when_catalog_is_empty(): void
    {
        $response = $this->get('/marketplace');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('visitsCount', 1));

        $this->get('/marketplace');
        $this->assertSame(2, (int) MarketplaceVisit::current()->visits_count);
    }

    public function test_first_visited_at_is_set_on_first_hit_and_persisted(): void
    {
        $this->get('/marketplace');

        $row = MarketplaceVisit::current();
        $this->assertNotNull($row->first_visited_at);
        $firstHit = $row->first_visited_at->copy();

        $this->travel(5)->minutes();
        $this->get('/marketplace');

        $row->refresh();
        $this->assertNotNull($row->last_visited_at);
        $this->assertEquals(
            $firstHit->toAtomString(),
            $row->first_visited_at->toAtomString()
        );
    }

    public function test_marketplace_index_existing_listings_still_render(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        Car::factory()->count(3)->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $response = $this->get('/marketplace');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->loadDeferredProps('default', fn ($prop) => $prop->has('cars.data', 3))
            ->where('visitsCount', 1));
    }
}
