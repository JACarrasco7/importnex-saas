<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_kpis(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        Car::factory()->count(3)->create(['organization_id' => $org->id]);
        Client::factory()->count(2)->create(['organization_id' => $org->id]);
        Contact::factory()->create(['organization_id' => $org->id]);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard')
            ->where('stats.cars_total', 3)
            ->where('stats.clients_total', 2)
            ->where('stats.contacts_total', 1)
            ->where('stats.alerts_pending', 1)
        );
    }

    public function test_dashboard_traffic_lights_count(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Car::factory()->create(['organization_id' => $org->id, 'traffic_light' => 'green']);
        Car::factory()->create(['organization_id' => $org->id, 'traffic_light' => 'red']);
        Car::factory()->create(['organization_id' => $org->id, 'traffic_light' => 'red']);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('trafficLights.green', 1)
            ->where('trafficLights.red', 2)
            ->where('trafficLights.amber', 0)
            ->where('trafficLights.neutral', 0)
        );
    }

    public function test_dashboard_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org1->id]);

        Car::factory()->create(['organization_id' => $org1->id]);
        Car::factory()->create(['organization_id' => $org2->id]);
        Car::factory()->create(['organization_id' => $org2->id]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('stats.cars_total', 1)
        );
    }

    public function test_dashboard_recent_cars_limited(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('recentCars', fn ($cars) => count($cars) <= 5)
        );
    }
}
