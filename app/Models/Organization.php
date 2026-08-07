<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Organization extends Model
{
    use Billable, HasFactory;

    protected $fillable = [
        'name', 'slug', 'logo', 'is_public', 'plan', 'is_owner', 'stripe_id', 'trial_ends_at', 'subscribed_at', 'payment_failed_at',
        'ai_provider', 'ai_model', 'ai_api_key',
        'notification_webhook_url', 'notification_webhook_types', 'notification_preferences',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'is_public' => 'boolean',
        'is_owner' => 'boolean',
        'ai_api_key' => 'encrypted',
        'notification_webhook_url' => 'encrypted',
        'notification_webhook_types' => 'array',
        'notification_preferences' => 'array',
    ];

    protected $hidden = ['ai_api_key', 'notification_webhook_url'];

    // Tipos de alerta que la organización quiere silenciar.
    // Configurado vía /organization/{org}/edit > Notification preferences.
    public function isAlertTypeEnabled(string $alertType): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return ($prefs[$alertType] ?? true) === true;
    }

    public function webhookEnabledFor(string $alertType): bool
    {
        if (empty($this->notification_webhook_url)) {
            return false;
        }
        $types = $this->notification_webhook_types;

        // null/[] = enviar todos los tipos (los que pasen isAlertTypeEnabled).
        if (empty($types)) {
            return true;
        }

        return in_array($alertType, $types, true);
    }

    public const OWNER_UNLIMITED = PHP_INT_MAX;

    public function isOwner(): bool
    {
        return (bool) $this->is_owner;
    }

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
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
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
        return ! empty($this->ai_provider) && ! empty($this->ai_api_key);
    }

    public function carRequests()
    {
        return $this->hasMany(CarRequest::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
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
        if ($this->isOwner()) {
            return false;
        }

        $plan = config('subscription.plans.'.$this->plan);

        if (! $plan) {
            return false;
        }

        $current = $this->currentCount($type);
        $limit = $this->rawPlanLimit($plan, $type);

        return $current >= $limit;
    }

    public function available(string $type): int
    {
        if ($this->isOwner()) {
            return self::OWNER_UNLIMITED;
        }

        $plan = config('subscription.plans.'.$this->plan);

        if (! $plan) {
            return 0;
        }

        $limit = $this->rawPlanLimit($plan, $type);
        $current = $this->currentCount($type);

        return max(0, $limit - $current);
    }

    public function limitFor(string $type): int
    {
        if ($this->isOwner()) {
            return self::OWNER_UNLIMITED;
        }

        return (int) ($this->rawPlanLimit(config('subscription.plans.'.$this->plan) ?: [], $type) ?? 0);
    }

    private function rawPlanLimit(?array $plan, string $type): int
    {
        if (! $plan) {
            return 0;
        }

        $candidates = [
            rtrim($type, 's').'_limit',
            $type.'_limit',
        ];

        foreach ($candidates as $key) {
            if (array_key_exists($key, $plan)) {
                return (int) $plan[$key];
            }
        }

        return 0;
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
        $unlimited = $this->isOwner();

        return [
            'current' => $current,
            'limit' => $unlimited ? null : $limit,
            'available' => $unlimited ? null : max(0, $limit - $current),
            'percentage' => $unlimited ? 0 : ($limit > 0 ? min(100, (int) round(($current / $limit) * 100)) : 0),
            'reached' => ! $unlimited && $limit > 0 && $current >= $limit,
            'unlimited' => $unlimited,
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
