<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_cars_store_is_blocked_when_starter_limit_reached(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('cars.store'), [
            'brand' => 'BMW',
            'model' => '320d',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 15000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertSessionHasErrors('plan_limit');
    }

    public function test_cars_store_succeeds_under_starter_limit(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Car::factory()->count(5)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('cars.store'), [
            'brand' => 'BMW',
            'model' => '320d',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 15000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertSessionDoesntHaveErrors('plan_limit');
    }

    public function test_cars_store_returns_json_when_limit_reached(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson(route('cars.store'), [
                'brand' => 'BMW',
                'model' => '320d',
                'year' => '07/2020',
                'fuel' => 'Diesel',
                'transmission' => 'Manual',
                'purchase_price' => 15000,
                'status' => 'Located',
                'traffic_light' => 'green',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'plan_limit_reached',
                'resource' => 'cars',
                'plan' => 'starter',
            ]);
    }

    public function test_clients_store_is_blocked_when_starter_limit_reached(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Client::factory()->count(50)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Test Client',
            'status' => 'New',
        ]);

        $response->assertSessionHasErrors('plan_limit');
    }

    public function test_contacts_store_is_blocked_when_starter_limit_reached(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        // Create 25 unique contacts (at the limit)
        for ($i = 0; $i < 25; $i++) {
            Contact::factory()->create([
                'organization_id' => $org->id,
                'email' => "contact{$i}@example.com",
            ]);
        }

        $this->assertTrue($org->limitReached('contacts'));

        $response = $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Test Contact',
        ]);

        $response->assertSessionHasErrors('plan_limit');
    }

    public function test_pro_plan_allows_50_cars(): void
    {
        $org = Organization::factory()->create(['plan' => 'pro']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Car::factory()->count(50)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('cars.store'), [
            'brand' => 'BMW',
            'model' => '320d',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 15000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertSessionDoesntHaveErrors('plan_limit');
    }

    public function test_organization_usageFor_returns_correct_percentage(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        Car::factory()->count(7)->create(['organization_id' => $org->id]);

        $usage = $org->usageFor('cars');

        $this->assertEquals(7, $usage['current']);
        $this->assertEquals(10, $usage['limit']);
        $this->assertEquals(3, $usage['available']);
        $this->assertEquals(70, $usage['percentage']);
        $this->assertFalse($usage['reached']);
    }

    public function test_organization_usageFor_contacts_works(): void
    {
        $org = Organization::factory()->create(['plan' => 'pro']);
        Contact::factory()->count(125)->create(['organization_id' => $org->id]);

        $usage = $org->usageFor('contacts');

        $this->assertEquals(125, $usage['current']);
        $this->assertEquals(250, $usage['limit']);
        $this->assertEquals(50, $usage['percentage']);
    }

    public function test_organization_planUsage_returns_all_resources(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        Car::factory()->count(3)->create(['organization_id' => $org->id]);
        Client::factory()->count(10)->create(['organization_id' => $org->id]);
        Contact::factory()->count(5)->create(['organization_id' => $org->id]);

        $usage = $org->planUsage();

        $this->assertArrayHasKey('cars', $usage);
        $this->assertArrayHasKey('clients', $usage);
        $this->assertArrayHasKey('contacts', $usage);
        $this->assertEquals(3, $usage['cars']['current']);
        $this->assertEquals(10, $usage['clients']['current']);
        $this->assertEquals(5, $usage['contacts']['current']);
    }

    public function test_dashboard_shares_plan_usage_in_inertia(): void
    {
        $org = Organization::factory()->create(['plan' => 'pro']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('planUsage')
            ->has('planUsage.cars')
            ->has('planUsage.clients')
            ->has('planUsage.contacts')
            ->has('currentPlan')
        );
    }
}
