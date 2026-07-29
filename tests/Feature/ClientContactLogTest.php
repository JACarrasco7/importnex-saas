<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientContactLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_log_contact(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $client = Client::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->post(route('clients.contact-logs.store', $client->id), [
            'contact_date' => now()->format('Y-m-d'),
            'channel' => 'phone',
            'summary' => 'Discussed budget',
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $client->contactLogs()->count());
    }

    public function test_contact_logs_index_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $client = Client::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->get(route('clients.contact-logs.index', $client->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Clients/ContactLogs'));
    }
}
