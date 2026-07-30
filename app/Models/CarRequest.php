<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'client_id',
        'name',
        'email',
        'phone',
        'brand',
        'model',
        'year_min',
        'year_max',
        'budget_min',
        'budget_max',
        'mileage_max',
        'fuel',
        'transmission',
        'body_type',
        'doors',
        'seats',
        'color',
        'requirements',
        'notes',
        'status',
    ];

    protected $casts = [
        'year_min' => 'integer',
        'year_max' => 'integer',
        'budget_min' => 'integer',
        'budget_max' => 'integer',
        'mileage_max' => 'integer',
        'doors' => 'integer',
        'seats' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getBudgetRangeAttribute(): string
    {
        if (!$this->budget_min && !$this->budget_max) {
            return 'No especificado';
        }

        $min = $this->budget_min ? number_format($this->budget_min, 0, ',', '.') . ' €' : '';
        $max = $this->budget_max ? number_format($this->budget_max, 0, ',', '.') . ' €' : '';

        return trim($min . ' - ' . $max, ' - ');
    }

    public function getYearRangeAttribute(): string
    {
        if (!$this->year_min && !$this->year_max) {
            return 'No especificado';
        }

        $min = $this->year_min ?: '';
        $max = $this->year_max ?: '';

        return trim($min . ' - ' . $max, ' - ');
    }
}