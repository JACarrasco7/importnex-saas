<?php

namespace Tests\Feature\Audit;

use App\Enums\ImportOrderState;
use App\Models\Car;
use App\Models\ImportOrder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Auditoría de venta: coche y encargo no pueden contar estados distintos. */
class FlowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_cannot_move_a_car_with_an_active_order(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $order = ImportOrder::factory()->inState(ImportOrderState::Inspecting)->create(['organization_id' => $org->id]);
        $car = $order->car;
        $car->forceFill(['status' => 'Reserved'])->saveQuietly();

        $this->actingAs($user)->post(route('cars.kanban.move', $car), ['status' => 'Delivered'])
            ->assertSessionHas('error');

        $this->assertSame('Reserved', $car->fresh()->status);
    }

    public function test_kanban_moves_a_car_without_an_order(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id, 'status' => 'Located']);

        $this->actingAs($user)->post(route('cars.kanban.move', $car), ['status' => 'Valuing'])
            ->assertSessionHas('success');

        $this->assertSame('Valuing', $car->fresh()->status);
    }
}
