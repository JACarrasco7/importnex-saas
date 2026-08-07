<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Despacha una push notification a las suscripciones del usuario afectado.
 *
 * Implementación actual: registra el payload en el log. Esto es el hook de
 * integración con `minishlink/web-push` (u otra librería VAPID-compatible)
 * que se enchufa cuando se apruebe la dependencia.
 *
 * Pasos para activarlo de verdad:
 *   1. composer require minishlink/web-push
 *   2. php artisan vendor:publish --tag=laravel-vapor-web-push-config
 *   3. Generar claves VAPID: php artisan web-push:vapid
 *   4. Añadir VAPID_PUBLIC_KEY y VAPID_PRIVATE_KEY a .env
 *   5. Sustituir el cuerpo de sendToSubscription() por WebPush::sendNotification().
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

        // Buscar suscripciones de los usuarios del org
        $subscriptions = PushSubscription::query()
            ->whereIn('user_id', $org->users()->pluck('id'))
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = self::buildPayload($alert);

        foreach ($subscriptions as $sub) {
            self::sendToSubscription($sub, $payload, $alert);
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

    private static function sendToSubscription(PushSubscription $sub, array $payload, Alert $alert): void
    {
        // Hook actual: log. Reemplazar cuando se apruebe la lib de web-push.
        Log::info('[push:dry-run] Would send push notification', [
            'subscription_id' => $sub->id,
            'endpoint_preview' => substr($sub->endpoint, 0, 80),
            'payload' => $payload,
        ]);

        $sub->update(['last_seen_at' => now()]);
    }
}