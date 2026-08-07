<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);
        $this->actingAs($this->user);
    }

    public function test_can_create_client(): void
    {
        $response = $this->post('/clients', [
            'name' => 'Juan Pérez',
            'contact_info' => '+34 600 123 456',
            'looking_for' => 'BMW 3 Series',
            'budget_min' => 10000,
            'budget_max' => 30000,
            'status' => 'New',
            'notes' => 'Interested in sport version',
        ]);

        $response->assertRedirect('/clients');
        $this->assertDatabaseHas('clients', [
            'name' => 'Juan Pérez',
            'organization_id' => $this->user->organization_id,
        ]);
    }

    public function test_can_view_clients_list(): void
    {
        Client::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->get('/clients');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('clients')
            )
        );
    }

    public function test_can_update_client(): void
    {
        $client = Client::factory()->create([
            'organization_id' => $this->user->organization_id,
            'status' => 'New',
        ]);

        $response = $this->patch("/clients/{$client->id}", [
            'name' => 'Juan Pérez',
            'contact_info' => '+34 600 123 456',
            'looking_for' => 'BMW 3 Series',
            'budget_min' => 10000,
            'budget_max' => 30000,
            'status' => 'Negotiating',
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect('/clients');
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'Negotiating',
        ]);
    }

    public function test_can_delete_client(): void
    {
        $client = Client::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->delete("/clients/{$client->id}");

        $response->assertRedirect('/clients');
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_validates_required_name(): void
    {
        $response = $this->post('/clients', [
            'contact_info' => '+34 600 123 456',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
