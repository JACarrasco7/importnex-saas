<?php

namespace App\Console\Commands;

use App\Support\Esqueleto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill retroactivo de `cars.recommendation` desde el bloque legacy
 * [RECOMENDACION] de `contenido/informe-interno.txt` (auditoría 09-sep-2026,
 * fix 10-sep).
 *
 * Caso: ZIPs antiguos subidos con la skill v1 usaban el bloque
 * [RECOMENDACION] en informe-interno.txt. El ingestor actual NO lo extrae
 * al campo `recommendation`, así que los enlaces públicos de esos coches
 * muestran "car-unavailable" aunque el veredicto interno fuera "Comprar*".
 *
 * Uso:
 *   php artisan cars:backfill-recommendation            # procesa todos
 *   php artisan cars:backfill-recommendation --dry-run   # simula
 *   php artisan cars:backfill-recommendation --id=10     # solo uno
 */
class BackfillRecommendationFromLegacyZip extends Command
{
    protected $signature = 'cars:backfill-recommendation {--id=* : IDs específicos (opcional)} {--dry-run : Solo muestra qué cambiaría}';

    protected $description = 'Rellena cars.recommendation desde el bloque [RECOMENDACION] del TXT legacy.';

    public function handle(): int
    {
        $ids = (array) $this->option('id');
        $dryRun = (bool) $this->option('dry-run');

        $query = DB::table('cars')
            ->whereNull('recommendation')
            ->orWhere('recommendation', '')
            ->select(['id', 'brand', 'model', 'recommendation']);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $candidatos = $query->get();
        $this->info("Candidatos: {$candidatos->count()} coches sin recommendation.");

        $actualizados = 0;
        $omitidos = 0;
        foreach ($candidatos as $car) {
            $path = "cars/{$car->id}/contenido/informe-interno.txt";
            if (! Storage::disk('local')->exists($path)) {
                $omitidos++;

                continue;
            }

            try {
                $esq = Esqueleto::desde(Storage::disk('local')->get($path));
            } catch (\Throwable $e) {
                $omitidos++;

                continue;
            }

            $recomendacion = $esq->uno('RECOMENDACION');
            if (! is_string($recomendacion) || trim($recomendacion) === '') {
                $omitidos++;

                continue;
            }

            $recomendacion = trim($recomendacion);
            $this->line(sprintf(
                '  [%d] %s %s → "%s"',
                $car->id,
                $car->brand ?? '?',
                $car->model ?? '?',
                mb_substr($recomendacion, 0, 60)
            ));

            if (! $dryRun) {
                DB::table('cars')->where('id', $car->id)->update([
                    'recommendation' => $recomendacion,
                    'updated_at' => now(),
                ]);
            }
            $actualizados++;
        }

        $this->info(sprintf(
            'Resultado: %d actualizados, %d omitidos (sin TXT o sin bloque).',
            $actualizados,
            $omitidos
        ));

        return self::SUCCESS;
    }
}
