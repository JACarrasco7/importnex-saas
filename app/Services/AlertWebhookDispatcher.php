<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Despacha una alerta a un webhook Slack/Discord/Teams/Genérico.
 *
 * - Slack:   POST JSON { text: "..." } al Incoming Webhook URL.
 * - Discord: POST JSON { content: "..." } (Slack-compatible con content).
 *            Si el usuario pega una URL de Discord, detectamos por host.
 * - Teams:   POST JSON { text: "..." } (Adaptive Card minimo).
 * - Genérico: POST JSON { text, alert_type, reference_id, ... } (webhook.site, n8n, etc.)
 *
 * Usa el cliente HTTP de Laravel con timeout corto y retry-on-error desactivado
 * para que un webhook caido no retrase la app.
 */
class AlertWebhookDispatcher
{
    public static function dispatch(Alert $alert): void
    {
        $org = $alert->organization;
        $url = $org?->notification_webhook_url;
        if (! $url) {
            return;
        }

        $text = self::formatMessage($alert);

        $payload = str_contains($url, 'discord.com')
            ? ['content' => $text, 'username' => 'JJ Import Motors Alerts']
            : ['text' => $text];

        // Adjuntar metadata para integraciones genericas (webhook.site, n8n, Make)
        $payload['alert_type'] = $alert->alert_type;
        $payload['reference_id'] = $alert->reference_id;
        $payload['organization_id'] = $alert->organization_id;
        $payload['created_at'] = $alert->created_at?->toIso8601String();

        try {
            $response = Http::timeout(5)->connectTimeout(3)->post($url, $payload);
            if (! $response->successful() && $response->status() !== 204) {
                Log::warning('Alert webhook returned non-2xx', [
                    'alert_id' => $alert->id,
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 200),
                ]);
            }
        } catch (ConnectionException $e) {
            // No bloqueamos: la alerta ya esta en BD, el canal es secundario
            Log::warning('Alert webhook connection failed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Alert webhook unexpected error', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function formatMessage(Alert $alert): string
    {
        $org = $alert->organization;
        $orgName = $org?->name ?? 'org';

        $emoji = match ($alert->alert_type) {
            'verification_failed' => '⚠️',
            'verification_completed' => '✅',
            'car_request' => '📩',
            'car_stale' => '🕒',
            'client_no_contact' => '👤',
            default => '🔔',
        };

        return sprintf(
            '%s [%s] %s — %s',
            $emoji,
            $orgName,
            str_replace('_', ' ', $alert->alert_type),
            $alert->message,
        );
    }
}
