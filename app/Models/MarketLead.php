<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketLead extends Model
{
    protected $table = 'market_leads';

    public const ESTADOS = ['nuevo', 'contactado', 'cerrado', 'perdido'];

    protected $fillable = [
        'market_model_id',
        'organization_id',
        'nombre',
        'contacto',
        'presupuesto',
        'mensaje',
        'nota',
        'estado',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'presupuesto' => 'decimal:2',
        ];
    }

    public function marketModel(): BelongsTo
    {
        return $this->belongsTo(MarketModel::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
