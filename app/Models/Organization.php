<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name', 'slug', 'logo', 'is_public', 'plan', 'stripe_id', 'trial_ends_at', 'subscribed_at',
        'ai_provider', 'ai_model', 'ai_api_key',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'is_public' => 'boolean',
        'ai_api_key' => 'encrypted',
    ];

    protected $hidden = ['ai_api_key'];

    protected static function booted(): void
    {
        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });

        static::updating(function ($organization) {
            if ($organization->isDirty('name') && empty($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/request/{$this->slug}");
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function hasAiConfigured(): bool
    {
        return !empty($this->ai_provider) && !empty($this->ai_api_key);
    }

    public function carRequests()
    {
        return $this->hasMany(CarRequest::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\Laravel\Cashier\Subscription::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed() || ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '>', now());
        });
    }

    public function limitReached(string $type): bool
    {
        $plan = config('subscription.plans.' . $this->plan);

        if (!$plan) {
            return false;
        }

        $current = $this->currentCount($type);

        return $current >= ($plan[rtrim($type, 's') . '_limit'] ?? 0);
    }

    public function available(string $type): int
    {
        $plan = config('subscription.plans.' . $this->plan);

        if (!$plan) {
            return 0;
        }

        $limit = $plan[rtrim($type, 's') . '_limit'] ?? 0;
        $current = $this->currentCount($type);

        return max(0, $limit - $current);
    }

    public function limitFor(string $type): int
    {
        return (int) (config('subscription.plans.' . $this->plan . '.' . rtrim($type, 's') . '_limit') ?? 0);
    }

    public function currentCount(string $type): int
    {
        return match ($type) {
            'cars' => $this->cars()->count(),
            'clients' => $this->clients()->count(),
            'contacts' => $this->contacts()->count(),
            default => 0,
        };
    }

    public function usageFor(string $type): array
    {
        $limit = $this->limitFor($type);
        $current = $this->currentCount($type);

        return [
            'current' => $current,
            'limit' => $limit,
            'available' => max(0, $limit - $current),
            'percentage' => $limit > 0 ? min(100, (int) round(($current / $limit) * 100)) : 0,
            'reached' => $limit > 0 && $current >= $limit,
        ];
    }

    public function planUsage(): array
    {
        return [
            'cars' => $this->usageFor('cars'),
            'clients' => $this->usageFor('clients'),
            'contacts' => $this->usageFor('contacts'),
        ];
    }
}
