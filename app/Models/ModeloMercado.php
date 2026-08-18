<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloMercado extends Model
{
    protected $table = 'modelos_mercado';

    protected $fillable = [
        'scouting_mercado_id',
        'modelo',
        'segmento',
        'hueco_pct',
        'n_uds_de',
        'mediana_es',
        'mediana_de',
        'vendibilidad_estimada',
        'recomendacion_aprox',
        'mejor_anuncio_url',
        'fuente_cobertura',
    ];

    protected function casts(): array
    {
        return [
            'hueco_pct' => 'decimal:2',
            'n_uds_de' => 'integer',
            'mediana_es' => 'decimal:2',
            'mediana_de' => 'decimal:2',
            'vendibilidad_estimada' => 'integer',
            'fuente_cobertura' => 'array',
        ];
    }

    public function scouting(): BelongsTo
    {
        return $this->belongsTo(ScoutingMercado::class, 'scouting_mercado_id');
    }
}
