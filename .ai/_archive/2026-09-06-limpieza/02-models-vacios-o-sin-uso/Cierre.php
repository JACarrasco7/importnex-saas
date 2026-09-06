<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registro de cierre de venta (o no-venta) de un coche investigado.
 *
 * §3.5 — Permite calcular KPIs de negocio: precisión de veredictos,
 * tiempo hasta venta, desviación de precio, tasa de falsos positivos.
 *
 * Estructura basada en operaciones_cierre.md §15.
 */
class Cierre extends Model
{
    use SoftDeletes;

    protected $table = 'cierres';

    protected $fillable = [
        'organization_id',
        'coche_id',
        'car_id',
        'brand',
        'model',
        'fecha_investigacion',
        'veredicto',
        'precio_objetivo',
        'fecha_venta',
        'precio_final',
        'cliente',
        'plataforma',
        'dias_hasta_venta',
        'comentario',
        'estado',
    ];

    protected $casts = [
        'fecha_investigacion' => 'date',
        'fecha_venta' => 'date',
        'precio_objetivo' => 'decimal:2',
        'precio_final' => 'decimal:2',
        'dias_hasta_venta' => 'integer',
        'organization_id' => 'integer',
        'car_id' => 'integer',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scopes (para queries KPI)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Filtrar por periodo (YYYY-MM). Ej: scopePeriodo($q, '2026-08')
     */
    public function scopePeriodo(Builder $query, string $periodo): Builder
    {
        [$year, $month] = explode('-', $periodo);

        return $query->whereYear('fecha_investigacion', $year)
            ->whereMonth('fecha_investigacion', $month);
    }

    /**
     * Solo cierres vendidos (para KPI de tiempo hasta venta)
     */
    public function scopeVendidos(Builder $query): Builder
    {
        return $query->where('estado', 'vendido');
    }

    /**
     * Solo veredictos positivos (Comprar / Comprar si baja...)
     */
    public function scopeVeredictoPositivo(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('veredicto', 'like', 'Comprar%')
                ->orWhere('veredicto', 'like', 'COMPRAR%');
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Accessors / Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Desviación de precio: (precio_final - precio_objetivo) / precio_objetivo
     * Positiva = se vendió por encima del objetivo (bueno)
     * Negativa = se vendió por debajo del objetivo
     */
    public function desviacionPorcentaje(): ?float
    {
        if ($this->precio_objetivo <= 0 || $this->precio_final === null) {
            return null;
        }

        return round(
            (($this->precio_final - $this->precio_objetivo) / $this->precio_objetivo) * 100,
            2
        );
    }

    /**
     * Calcula automáticamente dias_hasta_venta si faltan ambas fechas.
     * Se llama antes de guardar vía evento o desde el controller.
     */
    public function calcularDiasHastaVenta(): void
    {
        if ($this->fecha_investigacion && $this->fecha_venta && $this->dias_hasta_venta === null) {
            $this->dias_hasta_venta = $this->fecha_investigacion->diffInDays($this->fecha_venta);
        }
    }
}
