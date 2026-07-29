<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarKanbanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_move_own_org_car(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id, 'status' => 'Located']);
        $this->actingAs($user);

        $response = $this->post(route('cars.kanban.move', $car->id), ['status' => 'Purchased']);
        $response->assertRedirect();
        $this->assertEquals('Purchased', $car->fresh()->status);
    }

    public function test_user_cannot_move_other_org_car(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);
        $org2 = Organization::factory()->create();
        $car2 = Car::factory()->create(['organization_id' => $org2->id, 'status' => 'Located']);

        $response = $this->actingAs($user1)->post(route('cars.kanban.move', $car2->id), ['status' => 'Purchased']);

        $response->assertStatus(404);
        $this->assertNotEquals('Purchased', $car2->fresh()->status);
    }

    public function test_move_with_invalid_status_fails_validation(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->post(route('cars.kanban.move', $car->id), ['status' => 'InvalidStatus']);

        $response->assertSessionHasErrors('status');
    }

    public function test_move_without_status_fails_validation(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->post(route('cars.kanban.move', $car->id), []);

        $response->assertSessionHasErrors('status');
    }
}
