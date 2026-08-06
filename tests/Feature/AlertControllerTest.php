<?php

namespace Tests\Feature;

use App\Models\Alert;
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

    public function test_pending_endpoint_returns_json(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Alert::factory()->count(3)->create(['organization_id' => $org->id, 'resolved' => false]);
        Alert::factory()->count(2)->create(['organization_id' => $org->id, 'resolved' => true]);

        $this->actingAs($user);

        $response = $this->getJson(route('alerts.pending'));
        $response->assertOk();
        $response->assertJsonStructure(['count', 'latest_id', 'recent']);
        $this->assertEquals(3, $response->json('count'));
    }

    public function test_pending_endpoint_excludes_snoozed(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false, 'snoozed_until' => now()->addHour()]);

        $this->actingAs($user);

        $response = $this->getJson(route('alerts.pending'));
        $response->assertOk();
        $this->assertEquals(1, $response->json('count'));
    }

    public function test_snooze_alert(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);

        $this->actingAs($user);

        $response = $this->post(route('alerts.snooze', $alert->id), ['hours' => 3]);
        $response->assertRedirect();

        $fresh = $alert->fresh();
        $this->assertNotNull($fresh->snoozed_until);
        $this->assertTrue($fresh->snoozed_until->isFuture());
        $this->assertTrue($fresh->snoozed_until->diffInHours(now()) < 4);
    }

    public function test_snooze_validates_hours_range(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->post(route('alerts.snooze', $alert->id), ['hours' => 0]);
        $response->assertSessionHasErrors('hours');

        $response = $this->post(route('alerts.snooze', $alert->id), ['hours' => 1000]);
        $response->assertSessionHasErrors('hours');
    }

    public function test_unsnooze_alert(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'resolved' => false,
            'snoozed_until' => now()->addHour(),
        ]);

        $this->actingAs($user);

        $response = $this->delete(route('alerts.unsnooze', $alert->id));
        $response->assertRedirect();
        $this->assertNull($alert->fresh()->snoozed_until);
    }

    public function test_index_filter_by_type(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Alert::factory()->create(['organization_id' => $org->id, 'alert_type' => 'car_request']);
        Alert::factory()->create(['organization_id' => $org->id, 'alert_type' => 'car_stale']);
        Alert::factory()->create(['organization_id' => $org->id, 'alert_type' => 'verification_failed']);

        $this->actingAs($user);

        $response = $this->get(route('alerts.index', ['filter' => 'all', 'type' => 'car_request']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Alerts/Index')
            ->where('filters.type', 'car_request'));
    }

    public function test_mark_all_read_skips_snoozed(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);
        Alert::factory()->create([
            'organization_id' => $org->id,
            'resolved' => false,
            'snoozed_until' => now()->addHour(),
        ]);

        $this->actingAs($user);

        $response = $this->post(route('alerts.mark-all-read'));
        $response->assertRedirect();

        // Solo la no-snoozed debe estar resuelta; la snoozed sigue activa
        $this->assertEquals(1, Alert::where('organization_id', $org->id)->where('resolved', true)->count());
        $this->assertEquals(1, Alert::where('organization_id', $org->id)->where('resolved', false)->count());
    }
}
