<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name', 'plan', 'stripe_id', 'trial_ends_at', 'subscribed_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscribed_at' => 'datetime',
    ];

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
