<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarChecklist extends Model
{
    use HasFactory;

    protected $table = 'car_checklists';

    protected $fillable = ['organization_id', 'car_id', 'item_key', 'kind', 'priority', 'section', 'completed', 'completed_at', 'notes'];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public const KIND_MILESTONE = 'milestone';
    public const KIND_INSPECTION = 'inspection';

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_IMPORTANT = 'important';
    public const PRIORITY_MINOR = 'minor';

    public function scopeMilestones($query)
    {
        return $query->where('kind', self::KIND_MILESTONE);
    }

    public function scopeInspections($query)
    {
        return $query->where('kind', self::KIND_INSPECTION);
    }

    protected static function booted()
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && auth()->user()->organization_id) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
