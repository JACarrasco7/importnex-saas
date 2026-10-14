<?php

namespace App\Observers;

use App\Http\Controllers\SitemapController;
use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\CarDocument;
use App\Models\CarPublicLink;
use App\Support\CarChecklistDefinitions;
use App\Support\CarDocumentDefinitions;
use Illuminate\Support\Facades\Cache;

class CarObserver
{
    /**
     * Campos cuyo cambio dispara recálculo de semáforo.
     */
    private const COST_FIELDS = [
        'purchase_price', 'transport', 'itv_fee', 'coc_fee',
        'dgt_fees', 'professional_fees', 'deposit',
        'market_avg', 'co2', 'manual_tax_base', 'new_price', 'boe_confirmed',
    ];

    /**
     * Invalida las cachés que dependen del inventario publico del marketplace.
     * Centralizado para que al anadir una clave nueva (p. ej. filter_counts)
     * no haya que tocar los 4 sitios donde se publica/despublica un coche.
     */
    private function flushMarketplaceCaches(): void
    {
        SitemapController::flush();
        Cache::forget('marketplace.filter_options');
        Cache::forget('marketplace.filter_counts');
        Cache::forget('marketplace.filter_bounds');
    }

    public function saving(Car $car): void
    {
        if (! $this->shouldRecalculateTrafficLight($car)) {
            return;
        }

        $marketAvg = $car->market_avg;
        if ($marketAvg === null || (float) $marketAvg <= 0) {
            return;
        }

        $totalCost = (float) $car->calculateTotalCost();
        $ratio = $totalCost / (float) $marketAvg;

        $car->traffic_light = match (true) {
            $ratio <= 1.00 => 'green',
            $ratio <= 1.05 => 'amber',
            default => 'red',
        };

        // Auditoria 2026-09-06 (B9): antes solo invalidaba marketplace si
        // cambiaba is_marketplace; pero el filtro tambien depende de status y
        // verdict (un coche que pasa a Delivered/Discarded debe desaparecer
        // del catalogo publico). Tambien se invalida el sitemap por la misma
        // razon.
        $marketplaceRelevant = collect([
            'is_marketplace', 'status', 'verdict', 'purchase_price', 'sale_price',
            // filter_counts depende de brand y estimated_saving (chips de
            // marca + boton "Muy buen precio"); sin esto, los contadores
            // se quedaban obsoletos hasta 30 min.
            'brand', 'estimated_saving', 'market_avg',
        ])
            ->contains(fn ($f) => $car->isDirty($f));
        if ($marketplaceRelevant) {
            $this->flushMarketplaceCaches();
        }
    }

    public function created(Car $car): void
    {
        $this->seedChecklist($car);
        $this->seedDocuments($car);

        if ($car->is_marketplace) {
            $this->flushMarketplaceCaches();

            // Generar link público automáticamente si el coche nace ya publicado.
            if ($car->activePublicLink() === null) {
                CarPublicLink::generateFor($car);
            }
        }
    }

    public function updated(Car $car): void
    {
        if ($car->wasChanged('is_marketplace')) {
            $this->flushMarketplaceCaches();

            // Cuando el coche se publica al marketplace por primera vez,
            // generamos automáticamente un link público (token) para que
            // el operador pueda compartirlo y el perfil Inertia /p/{token}
            // funcione. Si ya hay uno activo, lo respetamos.
            if ($car->is_marketplace && $car->activePublicLink() === null) {
                CarPublicLink::generateFor($car);
            }
        }
    }

    public function deleted(Car $car): void
    {
        if ($car->is_marketplace) {
            $this->flushMarketplaceCaches();
        }
    }

    private function shouldRecalculateTrafficLight(Car $car): bool
    {
        if ($car->preserveTrafficLight) {
            return false;
        }

        if (! $car->exists) {
            return true;
        }
        foreach (self::COST_FIELDS as $field) {
            if ($car->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    private function seedChecklist(Car $car): void
    {
        $definitions = app(CarChecklistDefinitions::class);
        $now = now();
        $rows = [];
        foreach ($definitions->all() as $def) {
            $rows[] = [
                'organization_id' => $car->organization_id,
                'car_id' => $car->id,
                'item_key' => $def['key'],
                'kind' => $def['kind'],
                'priority' => $def['priority'] ?? null,
                'section' => $def['section'] ?? null,
                'completed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            CarChecklist::insert($rows);
        }
    }

    private function seedDocuments(Car $car): void
    {
        $definitions = app(CarDocumentDefinitions::class);
        $now = now();
        $rows = [];
        foreach ($definitions->all() as $def) {
            $rows[] = [
                'organization_id' => $car->organization_id,
                'car_id' => $car->id,
                'name' => $def['name'],
                'doc_key' => $def['key'],
                'doc_type' => $def['doc_type'] ?? 'other',
                'group' => $def['group'],
                'status' => 'pending',
                'url' => null,
                'uploaded_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            CarDocument::insert($rows);
        }
    }
}
