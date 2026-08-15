<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Models\Organization;
use Illuminate\Console\Command;

/**
 * Diagnóstico rápido del estado de IA (verificación + marketing).
 */
class DiagAi extends Command
{
    protected $signature = 'importnex:diag-ai';

    protected $description = 'Muestra el estado de configuración IA de la organización';

    public function handle(): int
    {
        $org = Organization::find(1);
        $this->line('org ai_provider: '.var_export($org?->ai_provider, true));
        $this->line('org ai_model: '.var_export($org?->ai_model, true));
        $this->line('org ai_api_key set: '.var_export(! empty($org?->ai_api_key), true));
        $this->line('org hasAi: '.var_export($org?->hasAiConfigured(), true));

        $car = Car::withoutGlobalScopes()->where('id', 1)->first();
        if ($car) {
            $this->line('car1 status: '.$car->status);
            $this->line('car1 traffic_light: '.$car->traffic_light);
            $this->line('car1 ai_analysis_json: '.var_export($car->ai_analysis_json, true));
            $this->line('car1 verdict: '.var_export($car->verdict, true));
        }

        $this->line('marketing_contents: '.CarMarketingContent::count());
        $this->line('alerts: '.Alert::count());

        return self::SUCCESS;
    }
}
