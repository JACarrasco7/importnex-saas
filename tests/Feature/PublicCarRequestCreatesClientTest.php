<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCarRequestCreatesClientTest extends TestCase
{
    use RefreshDatabase;

    private function publicOrg(): Organization
    {
        return Organization::factory()->create(['is_public' => true, 'slug' => 'jj-test']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'María García',
            'email' => 'maria@example.com',
            'phone' => '600 123 456',
            'brand' => 'Opel',
            'model' => 'Astra',
            'year_min' => 2015,
            'year_max' => 2020,
            'budget_min' => 10000,
            'budget_max' => 15000,
            'mileage_max' => 100000,
            'power_min' => 100,
            'power_max' => 200,
            'engine_type' => 'TSI',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
            'body_type' => 'Compacto',
            'doors' => 5,
            'seats' => 5,
            'color' => 'Negro',
            'requirements' => 'Busco un coche fiable y con buen equipamiento.',
        ], $overrides);
    }

    public function test_public_request_creates_client_when_none_exists(): void
    {
        $org = $this->publicOrg();

        $response = $this->post(route('public.car-request.store', ['slug' => $org->slug]), $this->validPayload());

        $response->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'name' => 'María García',
            'status' => 'New',
        ]);

        $client = Client::where('organization_id', $org->id)->first();
        $this->assertNotNull($client);
        $this->assertStringContainsString('maria@example.com', $client->contact_info);
        $this->assertStringContainsString('600 123 456', $client->contact_info);

        $this->assertDatabaseHas('car_requests', [
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'name' => 'María García',
            'status' => 'pending',
        ]);
    }

    public function test_public_request_reuses_existing_client_by_email(): void
    {
        $org = $this->publicOrg();
        $existing = Client::create([
            'organization_id' => $org->id,
            'name' => 'María García',
            'contact_info' => json_encode(['email' => 'maria@example.com', 'phone' => '600 123 456']),
            'status' => 'New',
        ]);

        $response = $this->post(route('public.car-request.store', ['slug' => $org->slug]), $this->validPayload());

        $response->assertRedirect();

        // No crea un cliente duplicado: reutiliza el existente
        $this->assertSame(1, Client::where('organization_id', $org->id)->count());
        $this->assertDatabaseHas('car_requests', [
            'organization_id' => $org->id,
            'client_id' => $existing->id,
            'status' => 'pending',
        ]);
    }
}
