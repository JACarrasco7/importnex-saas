<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);
        $this->actingAs($this->user);
    }

    public function test_can_create_car(): void
    {
        $response = $this->post('/cars', [
            'brand' => 'BMW',
            'model' => '3 Series',
            'year' => '07/2020',
            'fuel' => 'Gasoline',
            'transmission' => 'Manual',
            'purchase_price' => 25000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertRedirect('/cars');
        $this->assertDatabaseHas('cars', [
            'brand' => 'BMW',
            'model' => '3 Series',
            'organization_id' => $this->user->organization_id,
        ]);
    }

    public function test_can_view_cars_list(): void
    {
        Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'brand' => 'BMW',
        ]);

        $response = $this->get('/cars');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('cars')
            )
        );
    }

    public function test_can_update_car(): void
    {
        $car = Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
        ]);

        $response = $this->patch("/cars/{$car->id}", [
            'brand' => 'BMW',
            'model' => '3 Series',
            'year' => '07/2020',
            'fuel' => 'Gasoline',
            'transmission' => 'Manual',
            'purchase_price' => 26000,
            'status' => 'Purchased',
            'traffic_light' => 'green',
        ]);

        $response->assertRedirect('/cars');
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'purchase_price' => 26000,
            'status' => 'Purchased',
        ]);
    }

    public function test_can_delete_car(): void
    {
        $car = Car::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->delete("/cars/{$car->id}");

        $response->assertRedirect('/cars');
        $this->assertSoftDeleted('cars', ['id' => $car->id]);
    }

    public function test_car_is_isolated_by_organization(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherCar = Car::factory()->create(['organization_id' => $otherOrg->id]);

        $response = $this->get("/cars/{$otherCar->id}");

        $response->assertStatus(404);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->post('/cars', []);

        $response->assertSessionHasErrors(['brand', 'model', 'year', 'fuel', 'transmission', 'purchase_price', 'status', 'traffic_light']);
    }

    public function test_can_update_enriched_valuation_fields(): void
    {
        $car = Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
        ]);

        $response = $this->patch("/cars/{$car->id}", [
            'brand' => 'BMW',
            'model' => '3 Series',
            'year' => '07/2020',
            'fuel' => 'Gasoline',
            'transmission' => 'Manual',
            'purchase_price' => 25000,
            'status' => 'Valuing',
            'traffic_light' => 'amber',
            'verdict' => 'Buy',
            'verdict_confidence' => 'high',
            'verdict_reasoning' => 'Good deal.',
            'market_avg' => 23000,
            'market_min' => 21000,
            'market_max' => 25000,
            'estimated_saving' => 1500,
            'pros' => [['text' => 'Low mileage', 'weight' => 'high']],
            'cons' => [['text' => 'Turbo wear', 'weight' => 'medium']],
        ]);

        $response->assertRedirect('/cars');
        $car->refresh();
        $this->assertSame('Buy', $car->verdict);
        $this->assertSame('high', $car->verdict_confidence);
        $this->assertSame(23000.0, (float) $car->market_avg);
        $this->assertCount(1, $car->pros);
        $this->assertCount(1, $car->cons);
    }

    public function test_index_paginates_with_15_per_page(): void
    {
        // 20 coches > 15 por página → esperamos last_page=2
        Car::factory()->count(20)->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->get('/cars');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('cars')
                ->where('cars.total', 20)
                ->where('cars.per_page', 15)
                ->where('cars.last_page', 2)
                ->where('cars.from', 1)
                ->where('cars.to', 15)
                ->has('cars.links', fn ($links) => $links->etc())
            )
        );
    }

    public function test_index_page_2_returns_remaining_cars(): void
    {
        Car::factory()->count(20)->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->get('/cars?page=2');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->where('cars.current_page', 2)
                ->where('cars.from', 16)
                ->where('cars.to', 20)
                ->has('cars.data', 5)
            )
        );
    }

    public function test_index_filters_by_status_server_side(): void
    {
        Car::factory()->count(10)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
        ]);
        Car::factory()->count(5)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Valuing',
        ]);

        $response = $this->get('/cars?status=Located');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->where('cars.total', 10)
                ->has('cars.data', 10)
            )
        );
    }

    public function test_index_returns_status_counts_for_tabs(): void
    {
        Car::factory()->count(7)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
        ]);
        Car::factory()->count(3)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Discarded',
        ]);

        $response = $this->get('/cars');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->where('statusCounts.Located', 7)
            ->where('statusCounts.Discarded', 3)
        );
    }

    public function test_index_status_counts_are_independent_of_status_filter(): void
    {
        // Aunque filtremos por status=Located, los counts de las pestañas
        // deben seguir reflejando el total global por estado.
        Car::factory()->count(8)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
        ]);
        Car::factory()->count(4)->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Valuing',
        ]);

        $response = $this->get('/cars?status=Located');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->where('statusCounts.Located', 8)
            ->where('statusCounts.Valuing', 4)
        );
    }

    public function test_index_returns_light_counts_and_inventory_value(): void
    {
        Car::factory()->count(3)->create([
            'organization_id' => $this->user->organization_id,
            'traffic_light' => 'green',
        ]);
        Car::factory()->count(2)->create([
            'organization_id' => $this->user->organization_id,
            'traffic_light' => 'amber',
        ]);
        Car::factory()->count(1)->create([
            'organization_id' => $this->user->organization_id,
            'traffic_light' => 'red',
        ]);

        $response = $this->get('/cars');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->where('lightCounts.green', 3)
            ->where('lightCounts.amber', 2)
            ->where('lightCounts.red', 1)
            ->has('totalInventoryValue')
            ->where('sort', 'recent')
            ->has('sortOptions', 6)
        );
    }

    public function test_index_sorts_by_price_asc(): void
    {
        Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'brand' => 'Expensive',
            'purchase_price' => 50000,
        ]);
        Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'brand' => 'Cheap',
            'purchase_price' => 5000,
        ]);
        Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'brand' => 'Mid',
            'purchase_price' => 20000,
        ]);

        $response = $this->get('/cars?sort=price_asc');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->where('cars.data.0.brand', 'Cheap')
                ->where('cars.data.1.brand', 'Mid')
                ->where('cars.data.2.brand', 'Expensive')
            )
            ->where('sort', 'price_asc')
        );
    }

    public function test_index_invalid_sort_falls_back_to_recent(): void
    {
        Car::factory()->count(3)->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->get('/cars?sort=not-a-real-sort');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->where('sort', 'recent')
        );
    }

    public function test_index_sort_combines_with_status_filter(): void
    {
        $cheap = Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
            'purchase_price' => 5000,
        ]);
        $expensive = Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Located',
            'purchase_price' => 30000,
        ]);
        Car::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'Valuing',
            'purchase_price' => 10000, // debe excluirse por filtro de status
        ]);

        $response = $this->get('/cars?status=Located&sort=price_asc');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cars/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->where('cars.total', 2)
                ->where('cars.data.0.id', $cheap->id)
                ->where('cars.data.1.id', $expensive->id)
            )
            ->where('sort', 'price_asc')
        );
    }
}
