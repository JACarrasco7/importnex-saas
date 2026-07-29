<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'contact_info', 'looking_for', 'budget_min', 'budget_max',
        'status', 'notes', 'organization_id',
    ];

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

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function contactLogs()
    {
        return $this->hasMany(ClientContactLog::class)->orderBy('contact_date', 'desc');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('contact_info', 'like', "%{$term}%")
              ->orWhere('looking_for', 'like', "%{$term}%")
              ->orWhere('notes', 'like', "%{$term}%");
        });
    }

    public function scopeBudget($query, $min, $max)
    {
        return $query->whereBetween('budget_max', [$min, $max]);
    }

    public function logContact($channel, $summary)
    {
        return $this->contactLogs()->create([
            'contact_date' => now(),
            'channel' => $channel,
            'summary' => $summary,
        ]);
    }
}
