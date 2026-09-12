<?php

namespace App\Listeners;

use App\Events\CarImported;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push opcional al Desktop local tras un import.
 *
 * Si services.importnex_chat.webhook_url esta configurado, envia POST JSON
 * con los datos del coche. Fire-and-forget: timeout duro 2s (configurable),
 * nunca lanza excepcion al import real si el webhook falla.
 *
 * En local, scripts/import-notify-receiver.ps1 escucha en el puerto 8765
 * y reescribe encargos.md del skill. Asi el siguiente encargo de Claude
 * Desktop ve que ese coche ya esta importado sin que el chat tenga que
 * llamar a subir-informe.ps1.
 */
class NotifyImportWebhook
{
    public function handle(CarImported $event): void
    {
        $url = (string) config('services.importnex_chat.webhook_url', '');
        if ($url === '') {
            return; // feature desactivada
        }

        $payload = [
            'event' => 'car.imported',
            'ts' => now()->toIso8601String(),
            'flujo' => $event->flujo,
            'schema_version' => $event->schemaVersion,
            'car_id' => $event->car->id,
            'car_url' => $event->carUrl,
            'marca' => $event->car->brand ?? null,
            'modelo' => $event->car->model ?? null,
            'anio' => $event->car->year ?? null,
        ];

        try {
            $secret = (string) config('services.importnex_chat.webhook_secret', '');
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'ImportnexCore-Webhook/1.0',
            ];
            if ($secret !== '') {
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($payload), $secret);
            }

            Http::withHeaders($headers)
                ->timeout((int) config('services.importnex_chat.webhook_timeout', 2))
                ->connectTimeout(1)
                ->withBody(json_encode($payload), 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            // No romper el import por un webhook caido. Solo log.
            Log::warning('NotifyImportWebhook failed', ['error' => $e->getMessage(), 'car_id' => $event->car->id]);
        }
    }
}
