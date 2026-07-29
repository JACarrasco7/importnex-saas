<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarExpense;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $car = Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 20000, 'transport' => 1000]);
        CarExpense::factory()->create(['car_id' => $car->id, 'concept' => 'ITV', 'estimated' => 150, 'actual' => 180]);

        $response = $this->get(route('finance.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Finance/Index')
            ->where('kpis.carsCount', 1));
    }
}
