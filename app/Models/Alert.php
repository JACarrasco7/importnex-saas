<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'alert_type', 'reference_type', 'reference_id', 'message', 'resolved', 'resolved_at', 'snoozed_until'];

    protected function casts(): array
    {
        return [
            'resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    protected $appends = ['target_url'];

    protected static function booted()
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && auth()->user()->organization_id) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    public function scopeActive($query)
    {
        // Pendientes y no pospuestas (o pospuestas pero ya caducadas)
        return $query
            ->where('resolved', false)
            ->where(function ($q) {
                $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now());
            });
    }

    public function scopeSnoozed($query)
    {
        return $query->where('resolved', false)->where('snoozed_until', '>', now());
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    public function snooze(int $hours): void
    {
        $this->update(['snoozed_until' => now()->addHours($hours)]);
    }

    public function unsnooze(): void
    {
        $this->update(['snoozed_until' => null]);
    }

    public function markAsResolved()
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * URL de la página real del recurso referenciado (si existe ruta).
     * Permite que "Ver" lleve directo al recurso en vez del detalle genérico.
     */
    public function getTargetUrlAttribute(): ?string
    {
        if (! $this->reference_id) {
            return null;
        }

        $routeName = match ($this->reference_type) {
            CarRequest::class => 'car-requests.show',
            'car' => 'cars.show',
            'client' => 'clients.show',
            default => null,
        };

        if ($routeName === null) {
            return null;
        }

        try {
            return route($routeName, $this->reference_id);
        } catch (\Throwable) {
            // Si la ruta no existe o no hay contexto HTTP, devolvemos null
            // y el front hace fallback a alerts.show.
            return null;
        }
    }
}
