<?php

namespace App\Observers;

use App\Models\Alert;
use App\Models\User;
use App\Services\AlertEmailDispatcher;
use App\Services\AlertWebhookDispatcher;
use App\Services\PushNotificationDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertObserver
{
    /**
     * Handle the Alert "created" event.
     *
     * Reglas:
     * - N8: si el tipo está silenciado en preferences, no se hace nada.
     * - N7: si hay webhook configurado y habilitado para el tipo, se envía.
     * - N6: si el usuario tiene suscripción push activa, se envía push.
     *
     * Cada canal es independiente: un fallo en Slack no afecta a push ni a in-app.
     */
    public function created(Alert $alert): void
    {
        try {
            $org = $alert->organization;
            if (! $org) {
                return;
            }

            // N8: si el tipo está silenciado en preferencias, abortar TODO.
            if (! $org->isAlertTypeEnabled($alert->alert_type)) {
                return;
            }

            // N7: webhook Slack/Discord/Teams.
            if ($org->webhookEnabledFor($alert->alert_type)) {
                try {
                    AlertWebhookDispatcher::dispatch($alert);
                } catch (\Throwable $e) {
                    Log::warning('Webhook dispatch failed', ['alert_id' => $alert->id, 'error' => $e->getMessage()]);
                }
            }

            // N6: push notifications (Web Push API).
            try {
                PushNotificationDispatcher::dispatch($alert);
            } catch (\Throwable $e) {
                Log::warning('Push dispatch failed', ['alert_id' => $alert->id, 'error' => $e->getMessage()]);
            }

            // Email notifications.
            try {
                AlertEmailDispatcher::dispatch($alert);
            } catch (\Throwable $e) {
                Log::warning('Email dispatch failed', ['alert_id' => $alert->id, 'error' => $e->getMessage()]);
            }
        } catch (\Throwable $e) {
            Log::warning('AlertObserver failed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Auditoria 2026-09-06 (B7): invalidar cache del middleware
        // HandleInertiaRequests para que el badge de alertas pendientes se
        // actualice al instante en el siguiente GET autenticado.
        $this->forgetInertiaShareCache($alert->organization_id);
    }

    public function deleted(Alert $alert): void
    {
        $this->forgetInertiaShareCache($alert->organization_id);
    }

    private function forgetInertiaShareCache(int $orgId): void
    {
        // El middleware tiene la cache por user_id (múltiples usuarios pueden
        // pertenecer a la misma org). Invalidamos el de TODOS los usuarios
        // de la org iterando la lista — es un set pequeño por org.
        $userIds = User::where('organization_id', $orgId)->pluck('id');
        foreach ($userIds as $uid) {
            Cache::forget('inertia.share.user.'.$uid);
        }
    }
}
