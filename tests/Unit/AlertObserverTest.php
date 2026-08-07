<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_dispatches_all_channels(): void
    {
        Http::fake();
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://webhook.site/test',
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'alert_type' => 'car_stale',
        ]);

        // Verificar que se despachó webhook con payload correcto
        Http::assertSent(function ($request) use ($alert) {
            $payload = $request->data();

            return $request->url() === 'https://webhook.site/test'
                && ($payload['alert_type'] ?? null) === $alert->alert_type;
        });
        Mail::assertSentCount(1);
    }

    public function test_observer_skips_if_alert_type_disabled(): void
    {
        Http::fake();
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://webhook.site/test',
            'notification_preferences' => ['car_stale' => false],
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'alert_type' => 'car_stale',
        ]);

        // No se debe despachar nada porque el tipo está silenciado
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_observer_webhook_skips_if_no_url(): void
    {
        Http::fake();
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_webhook_url' => null,
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
        ]);

        // Webhook no se envía (sin URL), pero sí email
        Http::assertNothingSent();
        Mail::assertSentCount(1);
    }

    public function test_observer_email_skips_if_no_recipients(): void
    {
        Http::fake();
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://webhook.site/test',
        ]);

        // Crear alerta sin usuarios con email
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
        ]);

        // Webhook se envía, pero email no (sin destinatarios)
        Http::assertSent(fn ($request) => true);
        Mail::assertNothingSent();
    }

    public function test_observer_continues_on_webhook_failure(): void
    {
        Http::fake(function () {
            throw new \Exception('Webhook failed');
        });
        Mail::fake();

        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://webhook.site/test',
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);

        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
        ]);

        // Webhook falla, pero email sigue enviándose
        Mail::assertSentCount(1);
    }
}
