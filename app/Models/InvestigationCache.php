<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InvestigationCache extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investigation_cache';

    protected $fillable = [
        'clave_modelo',
        'marca',
        'modelo',
        'potencia',
        'combustible',
        'aspectos',
        'organization_id',
    ];

    protected $casts = [
        'aspectos' => 'array',
        'potencia' => 'integer',
        'organization_id' => 'integer',
    ];

    /**
     * Meses de validez por aspecto (de cache_investigacion.py + auditoría §1.2)
     * Los 9 aspectos del contrato JSON.
     */
    public const CADUCIDAD = [
        'problemas_comunes' => 18,
        'fiabilidad' => 18,
        'precio_mercado' => 18,
        'piezas' => 12,
        'homologacion' => 24,
        'etiqueta_ambiental' => 24,
        'seguro' => 12,
        'recalls' => 6,
        'otros' => 24,
    ];

    /**
     * Genera la clave única del modelo
     */
    public static function generarClave(string $marca, string $modelo, ?int $potencia = null, ?string $combustible = null): string
    {
        $partes = [
            Str::slug($marca),
            Str::slug($modelo),
        ];

        if ($potencia) {
            $partes[] = "{$potencia}cv";
        }

        if ($combustible) {
            $partes[] = Str::slug($combustible);
        }

        return implode('-', array_filter($partes));
    }

    /**
     * Verifica si un aspecto está caducado
     */
    public function estaCaducado(string $aspecto): bool
    {
        if (! isset($this->aspectos[$aspecto]['fecha'])) {
            return true; // No hay fecha = caducado
        }

        $mesesValidez = self::CADUCIDAD[$aspecto] ?? 12;
        $fecha = Carbon::parse($this->aspectos[$aspecto]['fecha']);
        $mesesTranscurridos = $fecha->diffInMonths(now());

        return $mesesTranscurridos > $mesesValidez;
    }

    /**
     * Devuelve solo los aspectos no caducados
     */
    public function aspectosValidos(): array
    {
        $validos = [];

        foreach ($this->aspectos as $aspecto => $datos) {
            if (! $this->estaCaducado($aspecto)) {
                $validos[$aspecto] = $datos;
            }
        }

        return $validos;
    }

    /**
     * Devuelve solo los aspectos caducados
     */
    public function aspectosCaducados(): array
    {
        $caducados = [];

        foreach ($this->aspectos as $aspecto => $datos) {
            if ($this->estaCaducado($aspecto)) {
                $caducados[$aspecto] = $datos;
            }
        }

        return $caducados;
    }

    /**
     * Organización propietaria de la caché (multi-tenant).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
