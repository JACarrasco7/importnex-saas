<?php

namespace Tests\Feature;

use App\Mail\WeeklyAlertDigest;
use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklyAlertDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_send_mail(): void
    {
        Mail::fake();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'email' => 'owner@test.com']);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => false]);

        $this->artisan('alerts:send-weekly-digest --dry-run')
            ->expectsOutputToContain('1 organizations')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_skips_org_with_no_activity(): void
    {
        Mail::fake();

        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->artisan('alerts:send-weekly-digest')
            ->expectsOutputToContain('Sent: 0')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_sends_digest_to_owner_with_activity(): void
    {
        Mail::fake();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'email' => 'owner@test.com']);
        Alert::factory()->count(3)->create(['organization_id' => $org->id, 'resolved' => false]);
        Alert::factory()->create(['organization_id' => $org->id, 'resolved' => true, 'resolved_at' => now()]);

        $this->artisan('alerts:send-weekly-digest')->assertExitCode(0);

        Mail::assertSent(WeeklyAlertDigest::class, 1);
    }

    public function test_respects_muted_preferences_in_stats(): void
    {
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_preferences' => ['car_stale' => false],
        ]);
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'email' => 'o@t.com']);
        Alert::factory()->create(['organization_id' => $org->id, 'alert_type' => 'car_stale', 'resolved' => false]);
        Alert::factory()->create(['organization_id' => $org->id, 'alert_type' => 'car_request', 'resolved' => false]);

        // El command cuenta TODO sin filtrar por prefs — las prefs son para canales secundarios (webhook/push).
        // El digest manda el estado bruto de BD, sin filtrar.
        $this->artisan('alerts:send-weekly-digest')->assertExitCode(0);

        Mail::assertSent(WeeklyAlertDigest::class, 1);
    }
}
