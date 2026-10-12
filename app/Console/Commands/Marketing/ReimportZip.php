<?php

namespace App\Console\Commands\Marketing;

use App\Models\Car;
use App\Models\Organization;
use App\Services\ValuationPackageIngestor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Reimporta un ZIP del paquete de valoración sobre un coche existente.
 *
 * Útil para regenerar contenido marketing faltante (p.ej. un slot que
 * falló por charset en el import original) sin tener que subirlo por la web.
 *
 * Uso:
 *   php artisan marketing:reimport-zip {car_id} {path/al/paquete.zip}
 *
 * La organización se toma del coche (no del argumento).
 */
#[Signature('marketing:reimport-zip {car : ID del coche} {zip : ruta absoluta al ZIP}')]
#[Description('Reimporta un paquete ZIP de valoración sobre un coche existente.')]
class ReimportZip extends Command
{
    public function handle(ValuationPackageIngestor $ingestor): int
    {
        $carId = (int) $this->argument('car');
        $zipPath = (string) $this->argument('zip');

        $car = Car::find($carId);
        if (! $car) {
            $this->error("Coche {$carId} no encontrado.");

            return self::FAILURE;
        }
        if (! file_exists($zipPath)) {
            $this->error("ZIP no encontrado: {$zipPath}");

            return self::FAILURE;
        }

        /** @var Organization $org */
        $org = $car->organization;
        if (! $org) {
            $this->error("Coche {$carId} sin organización asociada.");

            return self::FAILURE;
        }

        $this->info("Reimportando ZIP en coche {$car->id} ({$car->brand} {$car->model}) org={$org->name}…");

        try {
            $result = $ingestor->ingest($zipPath, $org);
        } catch (\Throwable $e) {
            $this->error('Fallo en ingesta: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'OK · car=%d · was_new=%s · photos=%d · documents=%d · contents=%d · marketing=%d · warnings=%d',
            $car->id,
            $result['was_new'] ? 'yes' : 'no',
            $result['photos'],
            $result['documents'],
            $result['contents'],
            $result['marketing'],
            count($result['warnings']),
        ));
        foreach ($result['warnings'] as $w) {
            $this->warn("  · {$w}");
        }

        return self::SUCCESS;
    }
}
