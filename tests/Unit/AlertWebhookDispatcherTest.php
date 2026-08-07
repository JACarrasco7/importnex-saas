<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\Organization;
use App\Services\AlertWebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlertWebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_skips_if_no_webhook_url(): void
    {
        $org = Organization::factory()->create([
            'notification_webhook_url' => null,
        ]);
        $alert = Alert::factory()->create(['organization_id' => $org->id]);

        Http::fake();

        AlertWebhookDispatcher::dispatch($alert);

        Http::assertNothingSent();
    }

    public function test_dispatch_sends_to_slack_webhook(): void
    {
        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX',
        ]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'alert_type' => 'car_stale',
            'message' => 'Test alert',
            'reference_id' => 'REF-123',
        ]);

        Http::fake();

        AlertWebhookDispatcher::dispatch($alert);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return isset($payload['text'])
                && str_contains($payload['text'], '🕒')
                && str_contains($payload['text'], 'car stale')
                && str_contains($payload['text'], 'Test alert')
                && $payload['alert_type'] === 'car_stale'
                && $payload['reference_id'] === 'REF-123';
        });
    }

    public function test_dispatch_sends_to_discord_webhook(): void
    {
        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://discord.com/api/webhooks/1234567890/AbCdEfGhIjKlMnOpQrStUvWxYz',
        ]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
            'alert_type' => 'verification_failed',
            'message' => 'Discord test',
        ]);

        Http::fake();

        AlertWebhookDispatcher::dispatch($alert);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return isset($payload['content'])
                && str_contains($payload['content'], '⚠️')
                && str_contains($payload['content'], 'verification failed')
                && $payload['username'] === 'JJ Import Motors Alerts';
        });
    }

    public function test_dispatch_includes_metadata(): void
    {
        $org = Organization::factory()->create([
            'notification_webhook_url' => 'https://webhook.site/xxxxx',
        ]);
        $alert = Alert::factory()->create([
            'organization_id' => $org->id,
        ]);

        Http::fake();

        AlertWebhookDispatcher::dispatch($alert);

        Http::assertSent(function ($request) use ($alert, $org) {
            $payload = $request->data();

            return $payload['organization_id'] === $org->id
                && $payload['alert_type'] === $alert->alert_type
                && $payload['reference_id'] === $alert->reference_id
                && isset($payload['created_at']);
        });
    }
}
