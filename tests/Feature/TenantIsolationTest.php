<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Car;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cars_are_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();

        Car::factory()->create(['organization_id' => $org1->id, 'brand' => 'BMW']);
        Car::factory()->create(['organization_id' => $org2->id, 'brand' => 'Audi']);

        $this->assertEquals(1, $org1->cars()->count());
        $this->assertEquals(1, $org2->cars()->count());
    }

    public function test_clients_are_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Client::factory()->create(['organization_id' => $org1->id, 'name' => 'Client Org1']);
        Client::factory()->create(['organization_id' => $org2->id, 'name' => 'Client Org2']);

        $this->assertEquals(1, $org1->clients()->count());
        $this->assertEquals(1, $org2->clients()->count());
    }

    public function test_user_cannot_access_other_org_car(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $car2 = Car::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->get("/cars/{$car2->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_update_other_org_car(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $car2 = Car::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->patch("/cars/{$car2->id}", [
            'brand' => 'Updated',
            'model' => 'Test',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 20000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_other_org_car(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $car2 = Car::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->delete("/cars/{$car2->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('cars', ['id' => $car2->id]);
    }

    public function test_contacts_are_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Contact::factory()->create(['organization_id' => $org1->id, 'name' => 'Contact Org1']);
        Contact::factory()->create(['organization_id' => $org2->id, 'name' => 'Contact Org2']);

        $this->assertEquals(1, $org1->contacts()->count());
        $this->assertEquals(1, $org2->contacts()->count());
    }

    public function test_user_cannot_access_other_org_contact(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $contact2 = Contact::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->get("/contacts/{$contact2->id}");
        $response->assertStatus(404);
    }

    public function test_user_cannot_update_other_org_contact(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $contact2 = Contact::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->patch("/contacts/{$contact2->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_other_org_contact(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $contact2 = Contact::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->delete("/contacts/{$contact2->id}");
        $response->assertStatus(404);
        $this->assertDatabaseHas('contacts', ['id' => $contact2->id]);
    }

    public function test_alerts_are_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Alert::factory()->create(['organization_id' => $org1->id, 'message' => 'Org1 alert']);
        Alert::factory()->create(['organization_id' => $org2->id, 'message' => 'Org2 alert']);

        $this->assertEquals(1, $org1->alerts()->count());
        $this->assertEquals(1, $org2->alerts()->count());
    }

    public function test_user_cannot_access_other_org_alert(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $alert2 = Alert::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user1)->get("/alerts/{$alert2->id}");
        $response->assertStatus(404);
    }

    public function test_user_cannot_mark_resolved_other_org_alert(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);

        $org2 = Organization::factory()->create();
        $alert2 = Alert::factory()->create(['organization_id' => $org2->id, 'resolved' => false]);

        $response = $this->actingAs($user1)->patch("/alerts/{$alert2->id}/mark-resolved");
        $response->assertStatus(404);
        $this->assertFalse($alert2->fresh()->resolved);
    }

    public function test_operator_cannot_edit_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator']);
        $this->actingAs($user);

        $response = $this->patch(route('organization.update', $org->id), ['name' => 'Hacked']);
        $response->assertStatus(403);
    }

    public function test_cannot_create_car_beyond_plan_limit(): void
    {
        $org = Organization::factory()->create(['plan' => 'starter']);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        // Starter plan = 10 cars limit. Create 10 + try to create 11th.
        Car::factory()->count(10)->create(['organization_id' => $org->id]);

        $response = $this->post('/cars', [
            'brand' => 'BMW',
            'model' => 'Test',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 10000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(10, Car::where('organization_id', $org->id)->count());
    }
}
