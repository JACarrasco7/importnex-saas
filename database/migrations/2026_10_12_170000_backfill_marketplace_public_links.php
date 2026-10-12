<?php

use App\Models\Car;
use App\Models\CarPublicLink;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill: genera un CarPublicLink (token) para todos los coches que ya
 * estan en el marketplace y aun no tienen un link activo. Esto hace que
 * el nuevo endpoint /p/{token} funcione retroactivamente para los coches
 * ya publicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Car::query()
            ->where('is_marketplace', true)
            ->whereNotIn('status', Car::MARKETPLACE_EXCLUDED_STATUSES)
            ->whereHas('organization', fn ($q) => $q->where('is_public', true))
            ->with('publicLinks')
            ->chunkById(100, function ($cars) {
                foreach ($cars as $car) {
                    $hasActive = $car->publicLinks->contains(fn ($l) => $l->isActive());
                    if (! $hasActive) {
                        CarPublicLink::generateFor($car);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: si rollback, los tokens quedan pero no se usan en la app
        // hasta que vuelvan a autogenerarse en el proximo publish.
    }
};
