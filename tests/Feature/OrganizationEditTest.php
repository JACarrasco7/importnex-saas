<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $response = $this->get(route('organization.edit', $org->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Organization/Edit'));
    }

    public function test_operator_cannot_edit_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator']);
        $this->actingAs($user);

        $response = $this->patch(route('organization.update', $org->id), ['name' => 'Hacked']);
        $response->assertStatus(403);
    }

    public function test_owner_can_update_organization_name(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $response = $this->patch(route('organization.update', $org->id), ['name' => 'New Name']);
        $response->assertRedirect();

        $this->assertEquals('New Name', $org->fresh()->name);
    }
}
