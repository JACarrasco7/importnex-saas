<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarExpense extends Model
{
    use HasFactory;

    protected $table = 'car_expenses';

    protected $fillable = ['organization_id', 'car_id', 'concept', 'estimated', 'actual', 'notes'];

    protected $casts = [
        'estimated' => 'decimal:2',
        'actual' => 'decimal:2',
    ];

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
