<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarKanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        Car::factory()->count(3)->create(['organization_id' => $org->id, 'status' => 'Located']);

        $response = $this->get(route('cars.kanban'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Kanban'));
    }

    public function test_move_car_to_new_status(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $car = Car::factory()->create(['organization_id' => $org->id, 'status' => 'Located']);

        $response = $this->post(route('cars.kanban.move', $car->id), ['status' => 'Purchased']);
        $response->assertRedirect();

        $this->assertEquals('Purchased', $car->fresh()->status);
    }
}
