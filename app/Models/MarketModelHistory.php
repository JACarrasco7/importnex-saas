<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketModelHistory extends Model
{
    protected $table = 'market_model_history';

    protected $fillable = [
        'market_model_id',
        'mediana_de',
        'mediana_es',
        'hueco_pct',
        'hueco_neto_pct',
        'fuente_medicion',
        'medido_el',
    ];

    protected function casts(): array
    {
        return [
            'mediana_de' => 'decimal:0',
            'mediana_es' => 'decimal:0',
            'hueco_pct' => 'decimal:2',
            'hueco_neto_pct' => 'decimal:2',
            'medido_el' => 'date',
        ];
    }

    public function marketModel(): BelongsTo
    {
        return $this->belongsTo(MarketModel::class);
    }
}
