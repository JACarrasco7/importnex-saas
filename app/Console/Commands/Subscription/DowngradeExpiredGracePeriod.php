<?php

namespace App\Console\Commands\Subscription;

use App\Models\Organization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Degrada a `starter` todas las organizaciones cuyo payment_failed_at lleva
 * más de SUBSCRIPTION_GRACE_DAYS días sin resolverse.
 *
 * El webhook invoice.payment_failed solo marca `payment_failed_at` (NO degrada
 * inmediatamente, ver StripeWebhookController::handleInvoicePaymentFailed).
 * Este comando cierra el ciclo diariamente.
 *
 * Programado en routes/console.php para correr cada hora.
 */
#[Signature('subscription:downgrade-expired-grace {--dry-run : cuenta sin modificar}')]
#[Description('Degrada a starter las orgs con payment_failed_at más antiguo que SUBSCRIPTION_GRACE_DAYS.')]
class DowngradeExpiredGracePeriod extends Command
{
    public function handle(): int
    {
        $days = (int) config('subscription.payment_failed_grace_days', 7);

        $query = Organization::query()
            ->whereNotNull('payment_failed_at')
            ->where('plan', '!=', 'starter')
            ->where('payment_failed_at', '<=', now()->subDays($days));

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info("Sin orgs vencidas (grace={$days}d).");

            return self::SUCCESS;
        }

        $this->info("Encontradas {$count} orgs vencidas (grace={$days}d).");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: no se modifica nada.');
            foreach ($query->orderBy('payment_failed_at')->get(['id', 'name', 'plan', 'payment_failed_at']) as $row) {
                $this->line(sprintf(
                    '  · #%d %s — plan=%s — failed_at=%s',
                    $row->id,
                    (string) $row->name,
                    (string) $row->plan,
                    $row->payment_failed_at ? $row->payment_failed_at->toDateTimeString() : 'n/a',
                ));
            }

            return self::SUCCESS;
        }

        $updated = $query->update([
            'plan' => 'starter',
            'updated_at' => now(),
        ]);

        $this->info("OK {$updated} orgs degradadas a 'starter'.");

        // Opcional: log estructurado para que un Alerter pueda crear alerta
        // visible al admin. (No implementado todavía — ver hallazgo #36 en la
        // auditoría 2026-09-06.)
        Log::info('Orgs downgraded tras grace period', [
            'count' => $updated,
            'grace_days' => $days,
        ]);

        return self::SUCCESS;
    }
}
