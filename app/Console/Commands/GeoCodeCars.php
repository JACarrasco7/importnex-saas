<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Services\GeoCoder;
use Illuminate\Console\Command;

/**
 * Geocodifica coches sin coordenadas a partir de su ciudad (Nominatim).
 *
 * Útil tras importar coches antes de que existiera el geocoding automático:
 *   php artisan importnex:geocode-cars
 *
 * Es idempotente: solo toca los coches con lat/lng nulos y salta los que
 * tengan ciudad vacía.
 */
class GeoCodeCars extends Command
{
    protected $signature = 'importnex:geocode-cars
                            {--dry-run : Muestra qué se haría sin guardar}
                            {--limit=50 : Máximo de coches a procesar por pasada}';

    protected $description = 'Geocodifica coches sin coordenadas desde su ciudad (Nominatim + cache)';

    public function handle(GeoCoder $geoCoder): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $cars = Car::query()
            ->where(fn ($q) => $q->whereNull('lat')->orWhereNull('lng'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->limit($limit)
            ->get(['id', 'brand', 'model', 'city', 'lat', 'lng']);

        if ($cars->isEmpty()) {
            $this->info('No hay coches sin coordenadas con ciudad.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($cars as $car) {
            $coords = $geoCoder->geocodeCity($car->city);
            if (! $coords) {
                $this->warn("  #{$car->id} {$car->brand} {$car->model} — sin resultado para '{$car->city}'");
                $fail++;

                continue;
            }

            $this->line("  #{$car->id} {$car->brand} {$car->model} ({$car->city}) -> {$coords['lat']}, {$coords['lng']}");

            if (! $dryRun) {
                $car->lat = $coords['lat'];
                $car->lng = $coords['lng'];
                $car->save();
            }
            $ok++;
        }

        $this->info(sprintf('%s: %d geocodificados, %d sin resultado.', $dryRun ? 'Dry run' : 'Hecho', $ok, $fail));

        return self::SUCCESS;
    }
}
