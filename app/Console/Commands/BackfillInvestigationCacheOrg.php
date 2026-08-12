<?php

namespace App\Console\Commands;

use App\Models\InvestigationCache;
use App\Models\Organization;
use Illuminate\Console\Command;

/**
 * Backfill: rellena organization_id en investigation_cache para registros heredados.
 *
 * Uso:
 *   php artisan skill:backfill-investigation-cache         # dry-run (no escribe)
 *   php artisan skill:backfill-investigation-cache --apply # ejecuta UPDATE
 *   php artisan skill:backfill-investigation-cache --force # sin confirmación
 *   php artisan skill:backfill-investigation-cache --org="JJ Import Motors"
 *
 * §10.3 — Crítico multi-tenant. NO USAR --apply en producción sin backup previo.
 */
class BackfillInvestigationCacheOrg extends Command
{
    protected $signature = 'skill:backfill-investigation-cache
        {--apply : Ejecutar el UPDATE (sin esto, solo muestra lo que se haría)}
        {--force : No pedir confirmación interactiva}
        {--org= : Nombre exacto de la organización destino}';

    protected $description = 'Backfill organization_id en investigation_cache (dry-run por defecto)';

    public function handle(): int
    {
        $orgName = $this->option('org') ?: 'JJ Import Motors';
        $org = Organization::where('name', $orgName)->first();

        if (! $org) {
            $this->error("Organización no encontrada: {$orgName}");

            return self::FAILURE;
        }

        $this->info("Organización destino: {$org->name} (ID={$org->id})");

        $query = InvestigationCache::whereNull('organization_id');
        $total = $query->count();

        if ($total === 0) {
            $this->info('✅ No hay registros con organization_id NULL. Nada que hacer.');

            return self::SUCCESS;
        }

        $this->warn("Se actualizarán {$total} registros con organization_id={$org->id}");

        $sample = $query->limit(5)->get(['id', 'clave_modelo', 'created_at']);
        $this->table(['id', 'clave_modelo', 'created_at'], $sample);

        if (! $this->option('apply')) {
            $this->info('🔒 DRY-RUN. Para ejecutar el UPDATE usa --apply');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('¿Continuar con el UPDATE? (haz backup antes en producción)', false)) {
            $this->warn('Cancelado por el usuario.');

            return self::SUCCESS;
        }

        $updated = InvestigationCache::whereNull('organization_id')->update(['organization_id' => $org->id]);
        $this->info("✅ {$updated} registros actualizados.");

        $restantes = InvestigationCache::whereNull('organization_id')->count();
        if ($restantes > 0) {
            $this->warn("Quedan {$restantes} registros sin organization_id (revisa huérfanos).");
        }

        return self::SUCCESS;
    }
}
