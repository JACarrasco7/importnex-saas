<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
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
            ->has('cars')
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
}
