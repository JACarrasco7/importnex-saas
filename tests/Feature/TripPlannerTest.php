<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_planner_groups_cars_by_city(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        // 2 cars from same city → batch savings
        Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Purchased',
            'city' => 'Hamburg',
            'lat' => 53.55, 'lng' => 9.99,
            'transport' => 1200,
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Purchased',
            'city' => 'Hamburg',
            'lat' => 53.55, 'lng' => 9.99,
            'transport' => 1200,
        ]);

        $response = $this->get(route('trips.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Trips/Index')
            ->has('trips', 1)
            ->where('trips.0.count', 2)
            ->where('trips.0.city', 'Hamburg')
        );
    }

    public function test_trip_planner_excludes_other_orgs(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org1->id, 'role' => 'owner']);
        $this->actingAs($user);

        Car::factory()->create([
            'organization_id' => $org1->id, 'status' => 'Purchased',
            'city' => 'Hamburg', 'lat' => 53.5, 'lng' => 9.9,
        ]);
        Car::factory()->create([
            'organization_id' => $org2->id, 'status' => 'Purchased',
            'city' => 'Munich', 'lat' => 48.1, 'lng' => 11.5,
        ]);

        $response = $this->get(route('trips.index'));
        $response->assertInertia(fn ($page) => $page->where('totalCars', 1)
            ->has('trips', 1));
    }
}
