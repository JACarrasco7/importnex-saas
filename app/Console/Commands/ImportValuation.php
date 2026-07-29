<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ValuationImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Importa informes JSON de valoración generados por el chat.
 *
 * Origen por defecto: storage/app/importnex/import/
 *
 * Uso:
 *   php artisan importnex:import-valuation
 *   php artisan importnex:import-valuation --file=path/to/informe.json
 *   php artisan importnex:import-valuation --org="JJ Import Motors"
 *   php artisan importnex:import-valuation --dry-run
 */
class ImportValuation extends Command
{
    protected $signature = 'importnex:import-valuation
                            {--file= : Ruta a un JSON concreto (si no, procesa todos los del directorio)}
                            {--org= : Nombre de la organización destino}
                            {--dir= : Directorio de import (default: storage/app/importnex/import)}
                            {--dry-run : Validar sin guardar}';

    protected $description = 'Importa informes JSON de valoración del chat al esquema de coches.';

    public function handle(ValuationImporter $importer): int
    {
        $org = $this->resolveOrg();
        if (! $org) {
            $this->error('No organization found. Create one first or pass --org=');
            return self::FAILURE;
        }

        $files = $this->collectFiles();
        if (empty($files)) {
            $this->warn('No JSON files found.');
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $bar->advance();
            try {
                $raw = File::get($file);
                $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
                $payload = $importer->validate($payload);

                if ($this->option('dry-run')) {
                    $this->line("\n[DRY] " . basename($file) . ' -> OK (validated)');
                    continue;
                }

                $car = $importer->resolveCar($payload, $org);
                $wasNew = ! $car->exists;
                $importer->apply($car, $payload);

                $wasNew ? $created++ : $updated++;
                Log::info('importnex:import-valuation', [
                    'file' => basename($file),
                    'car_id' => $car->id,
                    'created' => $wasNew,
                ]);
            } catch (\Throwable $e) {
                $skipped++;
                $this->error("\n[SKIP] " . basename($file) . ' — ' . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Importación completada:");
        $this->line("  Creados:   {$created}");
        $this->line("  Actualizados: {$updated}");
        $this->line("  Saltados:  {$skipped}");

        return self::SUCCESS;
    }

    private function resolveOrg(): ?Organization
    {
        $name = $this->option('org');
        if ($name) {
            return Organization::where('name', $name)->first();
        }
        return Organization::first();
    }

    /**
     * @return array<int,string>
     */
    private function collectFiles(): array
    {
        if ($file = $this->option('file')) {
            return File::exists($file) ? [$file] : [];
        }
        $dir = $this->option('dir') ?: storage_path('app/importnex/import');
        if (! File::isDirectory($dir)) {
            $this->warn("Directory not found: {$dir}");
            return [];
        }
        return File::glob($dir . '/*.json') ?: [];
    }
}
