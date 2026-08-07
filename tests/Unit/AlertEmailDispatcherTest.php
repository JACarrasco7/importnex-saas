<?php

namespace Tests\Unit;

use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use App\Services\AlertEmailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertEmailDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_skips_unverified_users(): void
    {
        $org = Organization::factory()->create(['locale' => 'es']);
        User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        Mail::fake();

        AlertEmailDispatcher::dispatch($alert);

        Mail::assertNothingSent();
    }

    public function test_dispatch_sends_to_verified_users(): void
    {
        $org = Organization::factory()->create(['locale' => 'es']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'alert_type' => 'car_stale',
            'message' => 'Test alert message',
            'reference_id' => 'REF-123',
        ]);

        Mail::fake();

        AlertEmailDispatcher::dispatch($alert);

        Mail::assertSent(AlertNotification::class, function ($mail) use ($user, $alert) {
            return $mail->hasTo($user->email)
                && $mail->alert->id === $alert->id
                && $mail->locale === 'es';
        });
    }

    public function test_uses_organization_locale(): void
    {
        $org = Organization::factory()->create(['locale' => 'en']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        Mail::fake();

        AlertEmailDispatcher::dispatch($alert);

        Mail::assertSent(AlertNotification::class, function ($mail) {
            return $mail->locale === 'en';
        });
    }

    public function test_sends_to_owners_without_verification(): void
    {
        $org = Organization::factory()->create(['locale' => 'es']);
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => null,
            'role' => 'owner',
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        Mail::fake();

        AlertEmailDispatcher::dispatch($alert);

        Mail::assertSent(AlertNotification::class, function ($mail) use ($owner) {
            return $mail->hasTo($owner->email);
        });
    }
}
