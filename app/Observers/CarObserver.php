<?php

namespace App\Observers;

use App\Http\Controllers\SitemapController;
use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\CarDocument;
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

        // Flush sitemap cache if marketplace visibility changed.
        if ($car->isDirty('is_marketplace')) {
            SitemapController::flush();
            Cache::forget('marketplace.filter_options');
        }
    }

    public function created(Car $car): void
    {
        $this->seedChecklist($car);
        $this->seedDocuments($car);

        if ($car->is_marketplace) {
            SitemapController::flush();
            Cache::forget('marketplace.filter_options');
        }
    }

    public function updated(Car $car): void
    {
        if ($car->wasChanged('is_marketplace')) {
            SitemapController::flush();
            Cache::forget('marketplace.filter_options');
        }
    }

    public function deleted(Car $car): void
    {
        if ($car->is_marketplace) {
            SitemapController::flush();
            Cache::forget('marketplace.filter_options');
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
