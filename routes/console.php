<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Generate automatic alerts daily at 9 AM
Schedule::command('alerts:generate')->dailyAt('09:00');

// Weekly alert digest — Monday at 9 AM (in each org's TZ approx)
Schedule::command('alerts:send-weekly-digest')->weeklyOn(1, '09:00')->withoutOverlapping();

// Mapa de mercado (skill estudio-mercado): reporte diario de modelos caducados
Schedule::command('market:freshness')->dailyAt('06:00')->withoutOverlapping();

// Mapa de mercado: alertas de outliers y oportunidades (#5/#7)
Schedule::command('market:alerts')->dailyAt('07:00')->withoutOverlapping();

// Mapa de mercado: backup diario del JSON a carpeta con fecha
Schedule::call(function () {
    $file = storage_path('app/importnex/market/backup-'.now()->toDateString().'.json');
    Artisan::call('market:export', ['--file' => $file]);
})->dailyAt('06:30');
