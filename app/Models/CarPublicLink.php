<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CarPublicLink extends Model
{
    protected $fillable = [
        'car_id',
        'token',
        'expires_at',
        'revoked_at',
        'views_count',
        'last_viewed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'views_count' => 'integer',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function publicUrl(): string
    {
        return url('/c/'.$this->token);
    }

    public static function generateFor(Car $car, ?Carbon $expiresAt = null): self
    {
        return self::create([
            'car_id' => $car->id,
            'token' => Str::random(32),
            'expires_at' => $expiresAt,
        ]);
    }

    public function recordView(): void
    {
        $this->forceFill([
            'views_count' => $this->views_count + 1,
            'last_viewed_at' => now(),
        ])->save();
    }
}
