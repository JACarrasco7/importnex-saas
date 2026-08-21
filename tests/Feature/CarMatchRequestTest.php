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

    public function test_match_request_marks_car_reserved_when_in_previous_phase(): void
    {
        [$user, $org] = $this->actingUser();
        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Cliente Reserva',
            'status' => 'new',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'Located',
        ]);
        $request = CarRequest::create([
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'name' => 'Cliente Reserva',
            'brand' => 'Opel',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post(route('cars.match-request', ['car' => $car->id, 'carRequest' => $request->id]));

        $response->assertRedirect(route('cars.show', $car->id));
        $this->assertSame('Reserved', $car->fresh()->status);
    }

    public function test_match_request_does_not_downgrade_advanced_status(): void
    {
        [$user, $org] = $this->actingUser();
        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Cliente Compra',
            'status' => 'new',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'Purchased',
        ]);
        $request = CarRequest::create([
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'name' => 'Cliente Compra',
            'brand' => 'Opel',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post(route('cars.match-request', ['car' => $car->id, 'carRequest' => $request->id]));

        $response->assertRedirect(route('cars.show', $car->id));
        $this->assertSame('Purchased', $car->fresh()->status);
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

    public function test_match_request_creates_client_when_request_has_none(): void
    {
        [$user, $org] = $this->actingUser();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);
        // Solicitud SIN cliente (datos viejos / manuales)
        $request = CarRequest::create([
            'organization_id' => $org->id,
            'name' => 'Cliente Huérfano',
            'email' => 'huerfano@example.com',
            'phone' => '600123456',
            'brand' => 'Opel',
            'model' => 'Astra',
            'budget_min' => 10000,
            'budget_max' => 15000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post(route('cars.match-request', ['car' => $car->id, 'carRequest' => $request->id]));

        $response->assertRedirect(route('cars.show', $car->id));

        $request->refresh();
        $this->assertNotNull($request->client_id, 'La solicitud debe tener cliente tras vincular.');
        $this->assertDatabaseHas('clients', [
            'id' => $request->client_id,
            'name' => 'Cliente Huérfano',
            'organization_id' => $org->id,
        ]);
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'client_id' => $request->client_id]);
        $this->assertSame('in_progress', $request->status);
    }

    public function test_create_request_creates_client_and_links_car(): void
    {
        [$user, $org] = $this->actingUser();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'Located',
        ]);

        $response = $this->actingAs($user)->post(route('cars.create-request', $car->id), [
            'name' => 'Cliente Boca a Boca',
            'email' => 'boca@example.com',
            'phone' => '611222333',
            'requirements' => 'Busca un Astra J bien equipado.',
        ]);

        $response->assertRedirect(route('cars.show', $car->id));

        // La SOLICITUD se crea (acto primario) y con ella el cliente
        $this->assertDatabaseHas('car_requests', [
            'organization_id' => $org->id,
            'name' => 'Cliente Boca a Boca',
            'brand' => 'Opel',
            'model' => 'Astra',
            'status' => 'in_progress',
        ]);

        $request = CarRequest::where('organization_id', $org->id)->first();
        $this->assertNotNull($request->client_id, 'La solicitud debe crear y enlazar un cliente.');
        $this->assertDatabaseHas('clients', [
            'id' => $request->client_id,
            'name' => 'Cliente Boca a Boca',
            'organization_id' => $org->id,
        ]);
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'client_id' => $request->client_id]);
        $this->assertSame('Reserved', $car->fresh()->status);
    }

    public function test_create_request_reuses_existing_client_by_email(): void
    {
        [$user, $org] = $this->actingUser();
        $existing = Client::create([
            'organization_id' => $org->id,
            'name' => 'Cliente Existente',
            'contact_info' => json_encode(['email' => 'boca@example.com', 'phone' => '611222333']),
            'status' => 'New',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);

        $response = $this->actingAs($user)->post(route('cars.create-request', $car->id), [
            'name' => 'Cliente Boca a Boca',
            'email' => 'boca@example.com',
            'phone' => '611222333',
        ]);

        $response->assertRedirect(route('cars.show', $car->id));

        // No duplica el cliente: reutiliza el existente por email
        $this->assertSame(1, Client::where('organization_id', $org->id)->count());
        $this->assertDatabaseHas('car_requests', [
            'organization_id' => $org->id,
            'client_id' => $existing->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'client_id' => $existing->id]);
    }

    public function test_create_request_requires_name(): void
    {
        [$user, $org] = $this->actingUser();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);

        $response = $this->actingAs($user)->from(route('cars.show', $car->id))
            ->post(route('cars.create-request', $car->id), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertNull($car->fresh()->client_id);
        $this->assertSame(0, CarRequest::where('organization_id', $org->id)->count());
    }
}
