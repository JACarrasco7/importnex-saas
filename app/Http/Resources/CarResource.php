<?php

namespace App\Http\Resources;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Car
 */
class CarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Wire format used by:
     * - /api/cars (mobile app, integrations)
     * - /api/marketplace (public, filtered view)
     * - /api/import-valuation (post-import sync)
     *
     * Snake_case + ISO dates + nullable fields omitted from output.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'version' => $this->version,
            'year' => $this->year,
            'mileage' => $this->mileage,

            // Pricing
            'purchase_price' => (float) $this->purchase_price,
            'currency' => config('app.currency', 'EUR'),

            // Specs
            'fuel' => $this->fuel,
            'transmission' => $this->transmission,
            'cv' => $this->cv,
            'displacement' => $this->displacement,
            'co2' => $this->co2,
            'consumption' => $this->consumption,
            'doors' => $this->doors,
            'seats' => $this->seats,
            'color' => $this->color,
            'owners' => $this->owners,

            // Status
            'status' => $this->status,
            'traffic_light' => $this->traffic_light,
            'is_marketplace' => (bool) $this->is_marketplace,

            // AI verdict (omit if null)
            'verdict' => $this->verdict,
            'verdict_confidence' => $this->verdict_confidence,
            'verdict_reasoning' => $this->verdict_reasoning,

            // Market data (omit if null)
            'market_avg' => $this->market_avg !== null ? (float) $this->market_avg : null,
            'market_min' => $this->market_min !== null ? (float) $this->market_min : null,
            'market_max' => $this->market_max !== null ? (float) $this->market_max : null,
            'estimated_saving' => $this->estimated_saving !== null ? (float) $this->estimated_saving : null,

            // Media (only when loaded to avoid N+1)
            'photos' => $this->relationLoaded('photos') ? $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'photo_type' => $photo->photo_type,
                'is_primary' => (bool) $photo->is_primary,
                'order' => $photo->order,
            ])->all() : null,

            // Relations (only when loaded)
            'client' => $this->relationLoaded('client') && $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ] : null,

            // ISO timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'verdict_at' => $this->verdict_at?->toIso8601String(),

            // HATEOAS-style links
            '_links' => [
                'self' => url("/api/cars/{$this->id}"),
                'web' => url("/marketplace/{$this->id}"),
                'admin' => url("/cars/{$this->id}"),
            ],
        ];
    }
}
