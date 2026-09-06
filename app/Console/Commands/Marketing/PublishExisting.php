<?php

namespace App\Console\Commands\Marketing;

use App\Models\CarMarketingContent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Publica en masa contenido marketing importado del ZIP de Claude que quedó
 * en 'draft' por el bug histórico del ingestor.
 *
 * Solo afecta source='zip' (NO toca los manuales creados en UI).
 * Filtros: --car, --channel, --dry-run.
 */
#[Signature('marketing:publish-existing {--car= : ID del coche} {--channel= : canal (tiktok, instagram...)} {--dry-run : solo contar, no modificar}')]
#[Description('Pasa a published todos los marketing_contents source=zip que estén en draft (bug del ingestor pre-fix).')]
class PublishExisting extends Command
{
    public function handle(): int
    {
        $query = CarMarketingContent::query()
            ->where('source', CarMarketingContent::SOURCE_ZIP)
            ->where('status', CarMarketingContent::STATUS_DRAFT);

        if ($car = $this->option('car')) {
            $query->where('car_id', (int) $car);
        }
        if ($channel = $this->option('channel')) {
            $query->where('channel', $channel);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nada que publicar: 0 filas source=zip en draft.');

            return self::SUCCESS;
        }

        $this->info("Encontradas {$total} filas source=zip en draft.");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: no se modifica nada.');
            $this->table(
                ['car_id', 'channel', 'kind', 'slot', 'status'],
                $query->orderBy('car_id')->orderBy('channel')->orderBy('kind')->orderBy('slot')
                    ->get(['car_id', 'channel', 'kind', 'slot', 'status'])
                    ->map(fn ($r) => (array) $r)->toArray()
            );

            return self::SUCCESS;
        }

        $updated = $query->update([
            'status' => CarMarketingContent::STATUS_PUBLISHED,
            'published_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("OK {$updated} filas pasadas a published.");

        return self::SUCCESS;
    }
}
