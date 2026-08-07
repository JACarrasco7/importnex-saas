<?php

namespace App\Observers;

use App\Models\Alert;
use App\Services\AlertWebhookDispatcher;
use Illuminate\Support\Facades\Log;

class AlertObserver
{
    /**
     * Handle the Alert "created" event.
     *
     * Reglas:
     * - Si la org tiene un webhook configurado Y el alert_type está habilitado,
     *   se encola el envío (no bloquea la request).
     * - Si la org silenció este alert_type en notification_preferences, no se hace nada.
     *
     * El envío pasa por cola para que un Slack/Discord caído no afecte al usuario.
     */
    public function created(Alert $alert): void
    {
        try {
            $org = $alert->organization;
            if (! $org) {
                return;
            }

            // N8: si el tipo está silenciado en preferencias, abortar.
            if (! $org->isAlertTypeEnabled($alert->alert_type)) {
                return;
            }

            // N7: si el webhook está habilitado para este tipo, despachar.
            if ($org->webhookEnabledFor($alert->alert_type)) {
                AlertWebhookDispatcher::dispatch($alert);
            }
        } catch (\Throwable $e) {
            Log::warning('AlertObserver failed to dispatch webhook', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
