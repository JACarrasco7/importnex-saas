<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Imports real cars from the legacy JSON file into the SaaS database.
 *
 * Usage:
 *   php artisan import:real-cars --file="path/to/coches.json" --org="JJ Import Motors"
 */
class ImportRealCars extends Command
{
    protected $signature = 'import:real-cars
                            {--file= : Path to the JSON file}
                            {--org= : Organization name (default: first organization)}
                            {--dry-run : Parse but do not insert}';

    protected $description = 'Import real cars from the legacy JSON file into the SaaS';

    public function handle(): int
    {
        $filePath = $this->option('file');
        if (! $filePath) {
            $this->error('Please provide --file path.');
            return self::FAILURE;
        }

        if (! File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $orgName = $this->option('org');
        $org = $orgName
            ? Organization::where('name', $orgName)->first()
            : Organization::first();

        if (! $org) {
            $this->error('No organization found. Create one first.');
            return self::FAILURE;
        }

        $this->info("Importing into organization: {$org->name} (ID: {$org->id})");

        $data = json_decode(File::get($filePath), true);
        if (! is_array($data)) {
            $this->error('Invalid JSON format.');
            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $row) {
            $bar->advance();

            $externalId = $row['id'] ?? null;
            if (! $externalId) {
                $skipped++;
                continue;
            }

            $payload = $this->mapPayload($row, $org->id);

            if ($this->option('dry-run')) {
                $this->line("\n[DRY] Would import: {$payload['brand']} {$payload['model']}");
                continue;
            }

            $car = Car::updateOrCreate(
                ['organization_id' => $org->id, 'url_link' => $payload['url_link']],
                $payload
            );

            if ($car->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Import complete:");
        $this->line("  Created: {$created}");
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Map legacy Spanish-keyed JSON to our English schema.
     */
    private function mapPayload(array $row, int $orgId): array
    {
        return [
            'organization_id' => $orgId,

            // Technical specs
            'brand' => $row['marca'] ?? null,
            'model' => $row['modelo'] ?? null,
            'version' => $row['version'] ?? null,
            'year' => $row['anio'] ?? null,
            'mileage' => $row['km'] ?? 0,
            'fuel' => $row['combustible'] ?? null,
            'transmission' => $row['cambio'] ?? null,
            'cv' => $row['cv'] ?? 0,
            'displacement' => $row['cilindrada'] ?? null,
            'co2' => $row['co2'] ?? 0,
            'consumption' => $row['consumo'] ?? null,
            'owners' => $row['propietarios'] ?? 0,
            'doors' => $row['puertas'] ?? null,
            'seats' => $row['plazas'] ?? 5,
            'euro_norm' => $row['norma'] ?? null,
            'color' => $row['color'] ?? null,
            'itv_date' => $row['itv'] ?? null,

            // Costs
            'purchase_price' => $row['precioCoche'] ?? 0,
            'new_price' => $row['precioNuevo'] ?? 0,
            'manual_tax_base' => $row['baseImpManual'] ?? 0,
            'boe_confirmed' => $row['boeConfirmado'] ?? false,
            'transport' => $row['transporte'] ?? 0,
            'itv_fee' => $row['itvImp'] ?? 0,
            'coc_fee' => $row['coc'] ?? 0,
            'dgt_fees' => $row['tasas'] ?? 0,
            'professional_fees' => $row['honorarios'] ?? 0,
            'deposit' => $row['senal'] ?? 0,

            // VIN / VAT
            'vin' => $row['vin'] ?? null,
            'vat_scenario' => $row['ivaEscenario'] ?? 'margin',

            // Location
            'seller' => $row['vendedor'] ?? null,
            'city' => $row['ciudad'] ?? null,
            'lat' => $row['lat'] ?? null,
            'lng' => $row['lng'] ?? null,

            // Status
            'status' => $this->mapStatus($row['estado'] ?? 'Located'),
            'url_link' => $row['enlace'] ?? null,
            'traffic_light' => $row['semaforo'] ?? 'neutral',

            // AI Analysis (already present in legacy)
            'valuation' => $row['valoracion'] ?? null,
            'recommendation' => $row['recomendacion'] ?? null,
            'description' => $row['descripcion'] ?? null,
            'equipment' => $row['equipamiento'] ?? [],
            'tips' => $row['consejos'] ?? [],
            'red_flags' => $row['banderasRojas'] ?? [],

            // Photos (store URLs in fotos_json)
            'fotos_json' => $row['fotos'] ?? [],
        ];
    }

    /**
     * Map Spanish status to English.
     */
    private function mapStatus(string $spanishStatus): string
    {
        $map = [
            'Localizado' => 'Located',
            'Valorando' => 'Valuing',
            'Ofrecido' => 'Offered',
            'Reservado' => 'Reserved',
            'Comprado' => 'Purchased',
            'En_transito' => 'In_transit',
            'En_tránsito' => 'In_transit',
            'Procesando' => 'Processing',
            'Entregado' => 'Delivered',
            'Pendiente revisión' => 'Pending review',
            'Pendiente revision' => 'Pending review',
            'Verificando' => 'Verifying',
        ];

        return $map[$spanishStatus] ?? 'Located';
    }
}
