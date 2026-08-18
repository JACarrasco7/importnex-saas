<?php

namespace App\Console\Commands;

use App\Models\MarketModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reporta el estado de frescura del mapa de mercado.
 * Uso: php artisan market:freshness   (cron diario en routes/console.php)
 */
class MarketFreshness extends Command
{
    protected $signature = 'market:freshness';

    protected $description = 'Reporta modelos del mapa de mercado con estudio caducado.';

    public function handle(): int
    {
        $today = now()->toDateString();

        $total = MarketModel::count();
        $caducados = MarketModel::caducados($today)->get();
        $oportunidades = MarketModel::oportunidades()->count();

        foreach ($caducados as $model) {
            $this->warn("  [CADUCADO] {$model->modelo} (refrescar_antes_de: {$model->refrescar_antes_de?->toDateString()})");
        }

        $this->info("Mapa de mercado: {$total} modelos · {$caducados->count()} caducados · {$oportunidades} oportunidades");

        if ($caducados->isNotEmpty()) {
            Log::info('market:freshness', [
                'caducados' => $caducados->count(),
                'modelos' => $caducados->pluck('modelo')->all(),
            ]);
        }

        return self::SUCCESS;
    }
}
