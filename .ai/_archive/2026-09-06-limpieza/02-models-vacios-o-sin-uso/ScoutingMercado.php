<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoutingMercado extends Model
{
    protected $table = 'scouting_mercado';

    protected $fillable = [
        'scouting_id',
        'schema_version',
        'flujo',
        'generado_el',
        'origen',
        'preferencias_usuario',
        'modelos_escaneados',
        'modelos_con_hueco',
        'modelos_sin_hueco',
        'resumen_ejecutivo',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'generado_el' => 'datetime',
            'schema_version' => 'integer',
            'modelos_escaneados' => 'integer',
            'modelos_con_hueco' => 'integer',
            'modelos_sin_hueco' => 'integer',
        ];
    }

    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloMercado::class, 'scouting_mercado_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
