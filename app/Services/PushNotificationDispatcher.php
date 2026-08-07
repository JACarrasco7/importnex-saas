<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Despacha notificaciones push vía OneSignal.
 *
 * OneSignal maneja push web, push móvil, email y SMS desde una sola API.
 * Las credenciales se configuran POR ORGANIZACIÓN (onesignal_app_id + onesignal_api_key).
 *
 * Configuración por organización:
 *   Organization::onesignal_app_id
 *   Organization::onesignal_api_key (encrypted)
 */
class PushNotificationDispatcher
{
    public static function dispatch(Alert $alert): void
    {
        $org = $alert->organization;
        if (! $org) {
            return;
        }

        // N8: respetar preferencias
        if (! $org->isAlertTypeEnabled($alert->alert_type)) {
            return;
        }

        // OneSignal configurado por organización
        if (! $org->hasOneSignalConfigured()) {
            Log::info('[onesignal] Push notification skipped — org has not configured OneSignal', [
                'alert_id' => $alert->id,
                'organization_id' => $org->id,
            ]);

            return;
        }

        $payload = self::buildPayload($alert);

        $response = Http::withHeaders([
            'Authorization' => "Basic {$org->onesignal_api_key}",
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications', [
            'app_id' => $org->onesignal_app_id,
            'included_segments' => ['Active Users'],
            'headings' => ['en' => $payload['title'], 'es' => $payload['title']],
            'contents' => ['en' => $payload['body'], 'es' => $payload['body']],
            'data' => [
                'alert_id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'url' => $payload['url'],
            ],
            'web_url' => $payload['url'],
            'app_url' => $payload['url'],
        ]);

        if ($response->failed()) {
            Log::error('[onesignal] Push notification failed', [
                'alert_id' => $alert->id,
                'organization_id' => $org->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * @return array{title:string,body:string,url:?string,alert_type:string,alert_id:int}
     */
    private static function buildPayload(Alert $alert): array
    {
        $title = match ($alert->alert_type) {
            'verification_failed' => '⚠️ Verificación fallida',
            'verification_completed' => '✅ Verificación completada',
            'car_request' => '📩 Nueva solicitud',
            'car_stale' => '🕒 Vehículo inactivo',
            'client_no_contact' => '👤 Cliente sin contacto',
            default => '🔔 Nueva alerta',
        };

        return [
            'title' => $title,
            'body' => $alert->message,
            'url' => $alert->target_url,
            'alert_type' => $alert->alert_type,
            'alert_id' => $alert->id,
        ];
    }
}
