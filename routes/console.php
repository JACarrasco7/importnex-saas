<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// (auditoria ronda 4, sep-2026): eliminado `inspire` hourly — solo mete
// ruido en storage/logs/laravel.log cada hora. Forge recoge stdout.

// Generate automatic alerts daily at 9 AM
Schedule::command('alerts:generate')->dailyAt('09:00');

// Weekly alert digest — Monday at 9 AM (in each org's TZ approx)
Schedule::command('alerts:send-weekly-digest')->weeklyOn(1, '09:00')->withoutOverlapping();

// Mapa de mercado (skill estudio-mercado): reporte diario de modelos caducados
Schedule::command('market:freshness')->dailyAt('06:00')->withoutOverlapping();

// Mapa de mercado: alertas de outliers y oportunidades (#5/#7)
Schedule::command('market:alerts')->dailyAt('07:00')->withoutOverlapping();

// Mapa de mercado: backup diario del JSON a carpeta con fecha.
// (auditoria ronda 4, sep-2026): sin ->withoutOverlapping() antes.
// Si un export tarda >24h, la siguiente pasada se solapa.
// Tambien: el directorio backup crece sin limite (365 archivos/anio).
Schedule::call(function () {
    $file = storage_path('app/importnex/market/backup-'.now()->toDateString().'.json');
    Artisan::call('market:export', ['--file' => $file]);

    // Purga backups >30 dias para evitar acumulacion indefinida.
    $retentionDays = 30;
    foreach (glob(storage_path('app/importnex/market/backup-*.json')) as $old) {
        if (filemtime($old) < now()->subDays($retentionDays)->getTimestamp()) {
            @unlink($old);
        }
    }
})
    ->name('market:backup-and-prune')
    ->dailyAt('06:30')
    ->withoutOverlapping();

// Billing: degradar a `starter` las orgs con payment_failed_at vencido.
// Hook invoice.payment_failed solo marca el timestamp (ver
// StripeWebhookController::handleInvoicePaymentFailed). Este job lo cierra.
Schedule::command('subscription:downgrade-expired-grace')
    ->hourly()
    ->withoutOverlapping();

// (auditoria ronda 4, sep-2026): purga jobs fallidos cada dia. Sin esto
// la tabla `failed_jobs` crece sin limite con miles de webhooks de
// Stripe fallando o timeouts de VerifyCarWithAI.
Schedule::command('queue:prune-failed --hours=72')->daily();
