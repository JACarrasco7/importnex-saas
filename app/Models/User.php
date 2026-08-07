<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
        'locale',
        'notification_preferences',
        'notification_channels',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'notification_channels' => 'array',
        ];
    }

    public function isAlertTypeEnabled(string $alertType): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return ($prefs[$alertType] ?? true) === true;
    }

    public function isChannelEnabled(string $channel): bool
    {
        $channels = $this->notification_channels ?? ['email', 'push'];

        return in_array($channel, $channels, true);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function onboardingProgress()
    {
        return $this->hasOne(UserOnboardingProgress::class);
    }

    public function scopeFromOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function scopeOwner($query)
    {
        return $query->where('role', 'owner');
    }

    public function scopeOperator($query)
    {
        return $query->where('role', 'operator');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }
}
