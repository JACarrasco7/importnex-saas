<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarChecklistToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_marks_completed_when_no_explicit_value(): void
    {
        [$org, $user, $car] = $this->makeCar();

        $item = $car->checklists()->milestones()->where('item_key', 'deposit_paid')->first();
        $this->assertFalse($item->completed);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/checklists/{$item->id}/toggle");
        $response->assertRedirect();

        $item->refresh();
        $this->assertTrue($item->completed);
        $this->assertNotNull($item->completed_at);
    }

    public function test_toggle_unmarks_when_already_completed(): void
    {
        [$org, $user, $car] = $this->makeCar();

        $item = $car->checklists()->milestones()->where('item_key', 'deposit_paid')->first();
        $item->update(['completed' => true, 'completed_at' => now()]);

        $this->actingAs($user)->post("/cars/{$car->id}/checklists/{$item->id}/toggle");

        $item->refresh();
        $this->assertFalse($item->completed);
        $this->assertNull($item->completed_at);
    }

    public function test_explicit_completed_false_unmarks(): void
    {
        [$org, $user, $car] = $this->makeCar();

        $item = $car->checklists()->milestones()->where('item_key', 'deposit_paid')->first();
        $item->update(['completed' => true, 'completed_at' => now()]);

        $this->actingAs($user)->post("/cars/{$car->id}/checklists/{$item->id}/toggle", [
            'completed' => false,
        ]);

        $item->refresh();
        $this->assertFalse($item->completed);
    }

    public function test_user_cannot_toggle_other_org_checklist(): void
    {
        [$org, $user, $car] = $this->makeCar();

        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $item = $car->checklists()->milestones()->where('item_key', 'deposit_paid')->first();

        $response = $this->actingAs($otherUser)->post("/cars/{$car->id}/checklists/{$item->id}/toggle");

        $response->assertStatus(404);
        $item->refresh();
        $this->assertFalse($item->completed);
    }

    public function test_404_when_checklist_does_not_belong_to_car(): void
    {
        [$org, $user, $car] = $this->makeCar();

        $otherCar = Car::factory()->create(['organization_id' => $org->id]);
        $strayItem = CarChecklist::create([
            'organization_id' => $org->id,
            'car_id'         => $otherCar->id,
            'item_key'       => 'custom_item',
            'kind'           => 'inspection',
            'completed'      => false,
        ]);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/checklists/{$strayItem->id}/toggle");
        $response->assertStatus(404);
    }

    /**
     * @return array{0:Organization,1:User,2:Car}
     */
    private function makeCar(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        return [$org, $user, $car];
    }
}
