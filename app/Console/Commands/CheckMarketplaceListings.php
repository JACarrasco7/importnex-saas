<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Car;
use App\Services\Marketplace\ListingAvailabilityChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tarea programada que revisa el anuncio original (url_link) de cada
 * coche publicado en el marketplace y, si ya no esta disponible:
 *  - Lo desactiva (is_marketplace = false).
 *  - Guarda el motivo en `cars.marketplace_unavailable_reason`.
 *  - Crea una alerta `car_listing_unavailable` para que el admin la vea
 *    en el panel.
 *
 * Si el portal devuelve un error transitorio (5xx, 401, 403, timeout)
 * NO se desactiva (decision 12-oct-2026): preferimos no perder una
 * publicacion por un fallo del portal o de la red. La fila se marca
 * con `marketplace_checked_at` para que el admin sepa que se reviso.
 *
 * Uso:
 *   php artisan marketplace:check-listings               (real)
 *   php artisan marketplace:check-listings --dry-run    (no persiste nada)
 *   php artisan marketplace:check-listings --org=1       (solo org=1)
 *
 * Programado en routes/console.php (diario 04:00).
 */
class CheckMarketplaceListings extends Command
{
    protected $signature = 'marketplace:check-listings
                            {--dry-run : Solo loguea, no desactiva ni crea alertas}
                            {--org= : Limitar a una organization_id concreta}
                            {--limit=200 : Maximo de coches a revisar por ejecucion}';

    protected $description = 'Revisa el anuncio original de cada coche del marketplace y desactiva los que ya no estan disponibles';

    public function handle(ListingAvailabilityChecker $checker): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $orgId = $this->option('org') !== null ? (int) $this->option('org') : null;
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '[DRY-RUN] ' : ''.'Revisando anuncios del marketplace...');

        $query = Car::query()
            ->withoutGlobalScope('organization') // comandos: sin auth -> global scope filtraria nada
            ->where('is_marketplace', true)
            ->whereNotNull('url_link')
            ->where('url_link', '!=', '')
            ->orderBy('id');

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $checked = 0;
        $deactivated = 0;
        $errors = 0;
        $skipped = 0;

        $query->chunkById(50, function ($cars) use ($checker, $dryRun, &$checked, &$deactivated, &$errors, &$skipped) {
            foreach ($cars as $car) {
                $checked++;
                try {
                    $result = $checker->check($car->url_link);
                } catch (Throwable $e) {
                    $errors++;
                    Log::warning('marketplace:check-listings error', [
                        'car_id' => $car->id,
                        'url' => $car->url_link,
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn("  [ERROR] car#{$car->id}: {$e->getMessage()}");

                    continue;
                }

                $this->line(sprintf(
                    '  car#%d %s %s [%s%s]',
                    $car->id,
                    $car->brand,
                    $car->model,
                    $result['reason'],
                    $result['http_status'] ? " HTTP {$result['http_status']}" : ''
                ));

                if ($dryRun) {
                    if (! $result['available']) {
                        $deactivated++;
                    }

                    continue;
                }

                $car->marketplace_checked_at = now();

                if (! $result['available']) {
                    $this->deactivateAndAlert($car, $result);
                    $deactivated++;
                } else {
                    // Si estaba marcado como unavailable de un check anterior y
                    // el anuncio ha vuelto a estar disponible, limpiamos la marca.
                    if ($car->marketplace_unavailable_reason !== null) {
                        $car->marketplace_unavailable_reason = null;
                        $car->save();
                    } else {
                        $car->save();
                    }
                }
            }
        });

        $this->info("Revisados: {$checked} · Desactivados: {$deactivated} · Saltados/errores: {$skipped}/{$errors}");

        Log::info('marketplace:check-listings done', [
            'checked' => $checked,
            'deactivated' => $deactivated,
            'errors' => $errors,
            'dry_run' => $dryRun,
            'org_id' => $orgId,
        ]);

        return self::SUCCESS;
    }

    /**
     * Desactiva el coche del marketplace y crea la alerta para el admin.
     * Idempotente: si ya hay una alerta abierta del mismo tipo, la
     * reutiliza en vez de duplicar.
     */
    private function deactivateAndAlert(Car $car, array $result): void
    {
        $car->is_marketplace = false;
        $car->marketplace_unavailable_reason = $result['reason'];
        $car->save();

        $existing = Alert::query()
            ->where('reference_type', 'car')
            ->where('reference_id', $car->id)
            ->where('alert_type', 'car_listing_unavailable')
            ->where('resolved', false)
            ->first();

        if ($existing) {
            return;
        }

        $humanReason = $this->humanReason($result);
        Alert::create([
            'organization_id' => $car->organization_id,
            'alert_type' => 'car_listing_unavailable',
            'reference_type' => 'car',
            'reference_id' => $car->id,
            'message' => "El anuncio original del coche {$car->brand} {$car->model} ya no esta disponible ({$humanReason}). Se ha desactivado del marketplace.",
            'resolved' => false,
        ]);
    }

    private function humanReason(array $result): string
    {
        return match ($result['reason']) {
            ListingAvailabilityChecker::REASON_404 => 'el portal devuelve 404',
            ListingAvailabilityChecker::REASON_SOLD_KEYWORD => 'el portal indica que esta vendido',
            ListingAvailabilityChecker::REASON_TIMEOUT => 'timeout al comprobar la URL',
            ListingAvailabilityChecker::REASON_5XX => 'el portal devolvio un error 5xx',
            default => 'motivo: '.($result['reason'] ?? 'desconocido'),
        };
    }
}
