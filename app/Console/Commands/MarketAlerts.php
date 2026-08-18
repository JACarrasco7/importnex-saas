<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\MarketModel;
use App\Services\PushNotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Alertas del mapa de mercado:
 *  - #7 Outliers: cambio brusco de hueco (>15 pts) entre la última medición y la anterior.
 *  - #5 Oportunidades: modelos con oportunidad=true recién marcada.
 * Reporta por consola y Log. (La notificación push/email se conecta en el paso siguiente al servicio existente.)
 *
 * Uso: php artisan market:alerts
 */
class MarketAlerts extends Command
{
    protected $signature = 'market:alerts';

    protected $description = 'Detecta outliers de hueco y oportunidades nuevas en el mapa de mercado.';

    public function handle(): int
    {
        $outliers = 0;
        $oportunidades = 0;

        foreach (MarketModel::with('history')->get() as $model) {
            // Outlier: delta de hueco bruto vs la medición anterior > 15 puntos
            $delta = null;
            $prev = $model->history->sortByDesc('medido_el')->first();
            if ($prev && $prev->hueco_pct !== null && $model->hueco_pct !== null) {
                $delta = (float) $model->hueco_pct - (float) $prev->hueco_pct;
                if (abs($delta) > 15) {
                    $outliers++;
                    $this->warn("  [OUTLIER] {$model->modelo}: hueco {$prev->hueco_pct}% → {$model->hueco_pct}% (Δ {$delta} pts) — revisar medición o cambio real de mercado");
                    $this->notify($model, 'market_outlier', "Cambio brusco de hueco en {$model->modelo}: Δ {$delta} pts");
                }
            }

            // Oportunidad recién marcada (sin histórico previo de oportunidad)
            if ($model->oportunidad && $model->veredicto === 'verde') {
                $oportunidades++;
                $this->info("  [CHOLLO] {$model->modelo}: precio_desde_de ".number_format((float) $model->precio_desde_de).' € vs mediana '.number_format((float) $model->mediana_de).' €');
                $this->notify($model, 'market_chollo', "Chollo detectado: {$model->modelo} a ".number_format((float) $model->precio_desde_de).' €');
            }
        }

        $this->info("market:alerts — {$outliers} outliers · {$oportunidades} oportunidades");

        if ($outliers > 0 || $oportunidades > 0) {
            Log::info('market:alerts', ['outliers' => $outliers, 'oportunidades' => $oportunidades]);
        }

        return self::SUCCESS;
    }

    /**
     * #2 — Crea un Alert y despacha push OneSignal (si el org lo tiene configurado).
     * No rompe si el esquema de Alert exige algo más: se captura y se loguea.
     */
    private function notify(MarketModel $model, string $tipo, string $mensaje): void
    {
        try {
            // #1 — Dedupe: si ya existe un alert activo (no resuelto) para la misma
            // referencia y tipo, no lo repetimos. Evita re-alertar el mismo chollo
            // cada día que corre el cron (07:00).
            $yaExiste = Alert::query()
                ->where('alert_type', $tipo)
                ->where('reference_type', 'market_model')
                ->where('reference_id', $model->id)
                ->whereNull('resolved_at')
                ->exists();

            if ($yaExiste) {
                return;
            }

            $alert = Alert::create([
                'organization_id' => $model->organization_id,
                'alert_type' => $tipo,
                'reference_type' => 'market_model',
                'reference_id' => $model->id,
                'message' => $mensaje,
            ]);
            PushNotificationDispatcher::dispatch($alert);
        } catch (\Throwable $e) {
            Log::warning('market:alerts notify skipped', ['tipo' => $tipo, 'error' => $e->getMessage()]);
        }
    }
}
