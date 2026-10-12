<?php

namespace App\Events;

use App\Models\Car;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Despues de importar un coche via API (Flujo A/B/C).
 *
 * Es el evento que el listener NotifyImportWebhook usa para hacer push
 * al Desktop local sin que el chat tenga que esperar a subir-informe.ps1.
 */
class CarImported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Car $car,
        public readonly string $flujo, // 'A' | 'B' | 'C' | 'web'
        public readonly string $carUrl,
        public readonly string $schemaVersion = '1',
    ) {}
}
