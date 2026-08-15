<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarRequest;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarMatchRequestTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        return [$user, $org];
    }

    public function test_show_includes_matching_requests_by_brand_and_model(): void
    {
        [$user, $org] = $this->actingUser();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);

        // Compatible: misma marca, sin modelo concreto.
        CarRequest::create([
            'organization_id' => $org->id,
            'name' => 'María',
            'brand' => 'Opel',
            'status' => 'pending',
        ]);
        // Compatible: marca y modelo coinciden.
        CarRequest::create([
            'organization_id' => $org->id,
            'name' => 'Juan',
            'brand' => 'opel',
            'model' => 'Astra J',
            'status' => 'contacted',
        ]);
        // Incompatible: otra marca.
        CarRequest::create([
            'organization_id' => $org->id,
            'name' => 'Ana',
            'brand' => 'VW',
            'status' => 'pending',
        ]);
        // Incompatible: mismo modelo pero estado cerrado.
        CarRequest::create([
            'organization_id' => $org->id,
            'name' => 'Pepe',
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('cars.show', $car->id));

        $response->assertOk();
        $matching = $response->viewData('page')['props']['derived']['matching_requests'];
        $this->assertCount(2, $matching);
        $this->assertSame(
            ['Juan', 'María'],
            collect($matching)->pluck('name')->sort()->values()->all()
        );
    }

    public function test_match_request_links_client_and_moves_request_to_in_progress(): void
    {
        [$user, $org] = $this->actingUser();
        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Cliente Match',
            'status' => 'new',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);
        $request = CarRequest::create([
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'name' => 'Cliente Match',
            'brand' => 'Opel',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post(route('cars.match-request', ['car' => $car->id, 'carRequest' => $request->id]));

        $response->assertRedirect(route('cars.show', $car->id));
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'client_id' => $client->id]);
        $this->assertDatabaseHas('car_requests', ['id' => $request->id, 'status' => 'in_progress']);
        $this->assertStringContainsString("Vinculado a vehículo #{$car->id}", $request->fresh()->notes);
    }

    public function test_show_excludes_matching_requests_from_other_organizations(): void
    {
        [$user, $org] = $this->actingUser();
        $otherOrg = Organization::factory()->create();

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);

        // Solicitud de OTRA organización con misma marca/modelo: NO debe aparecer.
        CarRequest::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Hacker',
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('cars.show', $car->id));

        $response->assertOk();
        $matching = $response->viewData('page')['props']['derived']['matching_requests'];
        $this->assertCount(0, $matching);
    }

    public function test_match_request_from_other_organization_is_forbidden(): void
    {
        [$user, $org] = $this->actingUser();
        $otherOrg = Organization::factory()->create();

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);
        $request = CarRequest::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Hacker',
            'brand' => 'Opel',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post(route('cars.match-request', ['car' => $car->id, 'carRequest' => $request->id]));

        $response->assertForbidden();
        $this->assertNull($car->fresh()->client_id);
        $this->assertSame('pending', $request->fresh()->status);
    }
}
