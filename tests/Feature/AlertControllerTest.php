<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_index_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);

        $this->actingAs($user);

        $response = $this->get(route('alerts.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Alerts/Index')
            ->where('alerts.data.0.message', fn ($msg) => is_string($msg)));
    }

    public function test_mark_resolved(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);

        $this->actingAs($user);

        $response = $this->patch(route('alerts.mark-resolved', $alert->id));
        $response->assertRedirect();
        $this->assertTrue($alert->fresh()->resolved);
    }

    public function test_cannot_resolve_other_org_alert(): void
    {
        $org1 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);
        $org2 = Organization::factory()->create();
        $alert2 = Alert::factory()->create(['organization_id' => $org2->id, 'resolved' => false]);

        $response = $this->actingAs($user1)->patch(route('alerts.mark-resolved', $alert2->id));

        $response->assertStatus(404);
        $this->assertFalse($alert2->fresh()->resolved);
    }

    public function test_delete_alert(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->delete(route('alerts.destroy', $alert->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('alerts', ['id' => $alert->id]);
    }

    public function test_alert_show_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('alerts.show', $alert->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Alerts/Show'));
    }
}
