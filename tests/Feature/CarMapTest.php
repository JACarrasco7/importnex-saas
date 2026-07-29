<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_groups_cars_by_city(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        Car::factory()->create([
            'organization_id' => $org->id,
            'city' => 'Hamburg', 'lat' => 53.5, 'lng' => 9.9,
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'city' => 'Munich', 'lat' => 48.1, 'lng' => 11.5,
        ]);

        $response = $this->get(route('cars.map'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Map')
            ->where('totalCars', 2)
            ->where('totalCities', 2)
        );
    }
}
