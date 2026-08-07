<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Despacha notificaciones push vía OneSignal.
 *
 * OneSignal maneja push web, push móvil, email y SMS desde una sola API.
 * Las suscripciones se gestionan en el frontend vía OneSignal SDK (Web SDK).
 *
 * Configuración en .env:
 *   ONESIGNAL_APP_ID=
 *   ONESIGNAL_REST_API_KEY=
 *   ONESIGNAL_API_URL=https://api.onesignal.com
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

        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            Log::warning('[onesignal] Push notification skipped — credentials not configured', [
                'alert_id' => $alert->id,
                'alert_type' => $alert->alert_type,
            ]);

            return;
        }

        $payload = self::buildPayload($alert);

        $response = Http::withHeaders([
            'Authorization' => "Basic {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post(config('services.onesignal.api_url', 'https://api.onesignal.com').'/notifications', [
            'app_id' => $appId,
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
