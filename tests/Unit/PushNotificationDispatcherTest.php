<?php

use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use App\Services\PushNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_skips_if_no_one_signal_configured(): void
    {
        $org = Organization::factory()->create([
            'onesignal_app_id' => null,
            'onesignal_api_key' => null,
        ]);

        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'alert_type' => 'verification_failed',
        ]);

        Http::fake();

        PushNotificationDispatcher::dispatch($alert);

        Http::assertNothingSent();
    }

    public function test_dispatch_skips_if_alert_type_disabled(): void
    {
        $org = Organization::factory()->create([
            'onesignal_app_id' => 'test-app-id',
            'onesignal_api_key' => 'test-api-key',
            'notification_preferences' => ['verification_failed' => false],
        ]);

        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'alert_type' => 'verification_failed',
        ]);

        Http::fake();

        PushNotificationDispatcher::dispatch($alert);

        Http::assertNothingSent();
    }

    public function test_dispatch_sends_notification(): void
    {
        $org = Organization::factory()->create([
            'onesignal_app_id' => 'test-app-id',
            'onesignal_api_key' => 'test-api-key',
        ]);

        $user = User::factory()->create(['organization_id' => $org->id]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'alert_type' => 'verification_failed',
            'message' => 'Test notification message',
            'target_url' => 'https://example.com/alert/1',
        ]);

        Http::fake([
            'https://api.onesignal.com/notifications' => Http::response(['id' => 'test-notification-id'], 200),
        ]);

        PushNotificationDispatcher::dispatch($alert);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.onesignal.com/notifications'
                && $request['app_id'] === 'test-app-id'
                && $request['included_segments'] === ['Active Users']
                && isset($request['headings']['es'])
                && isset($request['contents']['es']);
        });
    }

    public function test_notification_title_matches_alert_type(): void
    {
        $titles = [
            'verification_failed' => '⚠️ Verificación fallida',
            'verification_completed' => '✅ Verificación completada',
            'car_request' => '📩 Nueva solicitud',
            'car_stale' => '🕒 Vehículo inactivo',
        ];

        foreach ($titles as $type => $expectedTitle) {
            $org = Organization::factory()->create([
                'onesignal_app_id' => 'test-app-id',
                'onesignal_api_key' => 'test-api-key',
            ]);

            $user = User::factory()->create(['organization_id' => $org->id]);
            $alert = Alert::factory()->create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'alert_type' => $type,
            ]);

            Http::fake([
                'https://api.onesignal.com/notifications' => Http::response(['id' => 'test-id'], 200),
            ]);

            PushNotificationDispatcher::dispatch($alert);

            Http::assertSent(function ($request) use ($expectedTitle) {
                return $request['headings']['es'] === $expectedTitle;
            });

            // Reset para el siguiente test
            Http::reset();
        }
    }
}