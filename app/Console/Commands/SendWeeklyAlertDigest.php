<?php

namespace App\Console\Commands;

use App\Mail\WeeklyAlertDigest;
use App\Models\Alert;
use App\Models\Organization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('alerts:send-weekly-digest {--dry-run : Preview sin enviar}')]
#[Description('Envía el resumen semanal de alertas por email a cada organización activa')]
class SendWeeklyAlertDigest extends Command
{
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $weekAgo = now()->subWeek();

        $organizations = Organization::query()
            ->whereHas('users')
            ->get();

        $this->info("Found {$organizations->count()} organizations.");

        $sent = 0;
        $skipped = 0;

        foreach ($organizations as $org) {
            // Solo owners (los que reciben mails transaccionales del producto)
            $owner = $org->users()->where('role', 'owner')->first()
                ?? $org->users()->first();
            if (! $owner || ! $owner->email) {
                $skipped++;

                continue;
            }

            $query = Alert::query()->where('organization_id', $org->id);

            $stats = [
                'new_week' => (clone $query)->where('created_at', '>=', $weekAgo)->where('resolved', false)->count(),
                'resolved_week' => (clone $query)->where('resolved_at', '>=', $weekAgo)->count(),
                'pending' => (clone $query)->where('resolved', false)->count(),
                'snoozed' => (clone $query)->where('resolved', false)->where('snoozed_until', '>', now())->count(),
            ];

            // Si no hay nada relevante, no enviar (ahorra mails y mejora engagement)
            if ($stats['new_week'] === 0 && $stats['resolved_week'] === 0 && $stats['pending'] === 0) {
                $skipped++;
                $this->line("  · {$org->name}: sin actividad, skip");

                continue;
            }

            $recentAlerts = (clone $query)
                ->where('created_at', '>=', $weekAgo)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->each->append('target_url');

            $locale = $org->locale ?? 'es';

            $this->line(sprintf(
                '  · %s: new=%d resolved=%d pending=%d -> %s',
                $org->name,
                $stats['new_week'],
                $stats['resolved_week'],
                $stats['pending'],
                $owner->email,
            ));

            if ($dryRun) {
                continue;
            }

            try {
                Mail::to($owner->email)->send(new WeeklyAlertDigest($org, $stats, $recentAlerts->toArray(), $locale));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  ! Failed for {$org->name}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Done. Sent: {$sent}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
