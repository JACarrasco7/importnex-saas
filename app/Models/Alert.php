<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'alert_type', 'reference_type', 'reference_id', 'message', 'resolved', 'resolved_at'];

    protected $casts = ['resolved' => 'boolean', 'resolved_at' => 'datetime'];

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

    public function markAsResolved()
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}
