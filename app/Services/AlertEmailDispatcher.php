<?php

namespace App\Services;

use App\Mail\AlertNotification;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Despacha una alerta por email a los usuarios de la organización.
 *
 * - Solo envía a usuarios con email y activos.
 * - Usa el locale de la organización (es/en).
 * - No bloquea la aplicación si el email falla.
 * - Un fallo en email no afecta a push ni webhook.
 */
class AlertEmailDispatcher
{
    public static function dispatch(Alert $alert): void
    {
        $org = $alert->organization;
        if (! $org) {
            return;
        }

        // Obtener usuarios activos con email de esta organización
        $recipients = $org->users()
            ->whereNotNull('email')
            ->where(function ($q) {
                $q->where('role', 'owner')
                    ->orWhere('email_verified_at', '!=', null);
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $locale = $org->locale ?? 'es';

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new AlertNotification($alert, $org, $user, $locale));
            } catch (\Throwable $e) {
                Log::warning('Alert email failed', [
                    'alert_id' => $alert->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
