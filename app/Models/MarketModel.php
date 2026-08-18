<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketModel extends Model
{
    protected $table = 'market_models';

    public const CATEGORIAS = ['showstoppers', 'alta_rotacion', 'gemas_economicas'];

    public const SEGMENTOS = ['compacto', 'suv', 'berlina', 'deportivo', 'familiar', 'urbano'];

    public const RANGOS_PRECIO = ['0-8k', '8-14k', '14-25k', '25k+'];

    public const TIPOS_CLIENTE = [
        'primer_coche', 'familia', 'premium_imagen', 'deporte_ocio',
        'diario_eficiencia', 'negocio_reventa', 'impacto_showstopper',
    ];

    public const VEREDICTOS = ['verde', 'amarillo', 'rojo'];

    public const MEJORES_MERCADOS = ['DE', 'ES', 'paridad'];

    public const FUENTES_MEDICION = ['estudio', 'flujo_b', 'flujo_a', 'flujo_e_delta'];

    protected $fillable = [
        'organization_id',
        'slug',
        'alias',
        'categoria',
        'segmento',
        'rango_precio',
        'tipo_cliente',
        'tipos_cliente_secundarios',
        'categorias_secundarias',
        'modelo',
        'version',
        'oferta_de',
        'oferta_es',
        'mediana_de',
        'mediana_es',
        'precio_desde_de',
        'precio_desde_es',
        'sello_precio_de',
        'sello_precio_es',
        'hueco_pct',
        'hueco_neto_pct',
        'coste_importacion_estimado',
        'iedmt_estimado',
        'rotacion_dias_de',
        'rotacion_dias_es',
        'rotacion_fuente',
        'demanda_trends',
        'transferencias_mes_dgt',
        'matriculaciones_kba',
        'veredicto',
        'veredicto_fuente',
        'mejor_mercado',
        'fuente_medicion',
        'confianza_precio',
        'oportunidad',
        'publicar_en_catalogo',
        'foto_url',
        'vendibilidad',
        'pendiente_fase2',
        'query_reejecutable',
        'nota',
        'tasacion_pro',
        'refrescar_antes_de',
        'schema_version',
    ];

    protected function casts(): array
    {
        return [
            'alias' => 'array',
            'categorias_secundarias' => 'array',
            'tipos_cliente_secundarios' => 'array',
            'query_reejecutable' => 'array',
            'oferta_de' => 'integer',
            'oferta_es' => 'integer',
            'mediana_de' => 'decimal:0',
            'mediana_es' => 'decimal:0',
            'precio_desde_de' => 'decimal:0',
            'precio_desde_es' => 'decimal:0',
            'hueco_pct' => 'decimal:2',
            'hueco_neto_pct' => 'decimal:2',
            'coste_importacion_estimado' => 'integer',
            'iedmt_estimado' => 'integer',
            'rotacion_dias_de' => 'integer',
            'rotacion_dias_es' => 'integer',
            'transferencias_mes_dgt' => 'integer',
            'matriculaciones_kba' => 'integer',
            'confianza_precio' => 'integer',
            'oportunidad' => 'boolean',
            'publicar_en_catalogo' => 'boolean',
            'vendibilidad' => 'integer',
            'pendiente_fase2' => 'boolean',
            'tasacion_pro' => 'decimal:2',
            'refrescar_antes_de' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(MarketLead::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(MarketModelHistory::class)->orderByDesc('medido_el');
    }

    /**
     * Score compuesto de vendibilidad (0-100): hueco + rotación + demanda + confianza.
     * Si ya viene calculado del estudio, se respeta; aquí se usa como fallback por si falta.
     */
    public function calcularVendibilidad(): int
    {
        $hueco = min(100, max(0, (float) ($this->hueco_pct ?? 0) * 2.2));       // hueco 0-45% → 0-100
        $rotacion = match ((int) ($this->rotacion_dias_de ?? 60)) {
            default => max(0, min(100, 100 - (((int) $this->rotacion_dias_de - 15) / 60) * 100)),
        };
        $demanda = match ($this->demanda_trends) {
            'creciente' => 100, 'estable' => 65, default => 35,
        };
        $confianza = min(100, (int) ($this->confianza_precio ?? 2) * 20);

        return (int) round($hueco * 0.45 + $rotacion * 0.2 + $demanda * 0.2 + $confianza * 0.15);
    }

    public function costePuestoEnHuelva(?float $precioOrigen = null): array
    {
        $precio = $precioOrigen ?? (float) $this->precio_desde_de;
        $transporte = 900;
        $ausfuhr = 114;
        $itv = 115;
        $iedmt = (int) $this->iedmt_estimado;
        $honorarios = 1500; // M2 por defecto

        $total = $precio + $transporte + $ausfuhr + $itv + $iedmt + $honorarios;

        return [
            'precio_origen' => round($precio, 2),
            'transporte' => $transporte,
            'ausfuhr' => $ausfuhr,
            'itv' => $itv,
            'iedmt' => $iedmt,
            'honorarios' => $honorarios,
            'total' => round($total, 2),
        ];
    }

    /**
     * Tendencia vs la medición anterior (último histórico). Devuelve null si no hay.
     */
    public function tendencia(): ?array
    {
        // Si la relación ya está cargada (eager load en admin), usa la colección
        // para evitar N+1; si no, consulta la BD.
        $prev = $this->relationLoaded('history')
            ? $this->history->first()
            : $this->history()->first();
        if (! $prev) {
            return null;
        }

        return [
            'medida_anterior' => $prev->medido_el?->toDateString(),
            'mediana_de_anterior' => (float) $prev->mediana_de,
            'hueco_pct_anterior' => (float) $prev->hueco_pct,
            'delta_hueco' => $prev->hueco_pct !== null && $this->hueco_pct !== null
                ? round((float) $this->hueco_pct - (float) $prev->hueco_pct, 2)
                : null,
        ];
    }

    public function scopeVerdes(Builder $query): Builder
    {
        return $query->where('veredicto', 'verde');
    }

    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorSegmento(Builder $query, string $segmento): Builder
    {
        return $query->where('segmento', $segmento);
    }

    public function scopePorRango(Builder $query, string $rango): Builder
    {
        return $query->where('rango_precio', $rango);
    }

    public function scopePorTipoCliente(Builder $query, string $tipo): Builder
    {
        return $query->where(function (Builder $q) use ($tipo) {
            $q->where('tipo_cliente', $tipo)
                ->orWhereJsonContains('tipos_cliente_secundarios', $tipo);
        });
    }

    public function scopeOportunidades(Builder $query): Builder
    {
        return $query->where('oportunidad', true);
    }

    public function scopePublicos(Builder $query): Builder
    {
        return $query->where('publicar_en_catalogo', true);
    }

    /**
     * Aislamiento multi-tenant: modelos de una organización + los globales (null).
     */
    public function scopeDeOrganizacion(Builder $query, ?int $orgId): Builder
    {
        return $query->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'));
    }

    public function scopeCaducados(Builder $query, ?string $asOf = null): Builder
    {
        return $query->whereNotNull('refrescar_antes_de')
            ->where('refrescar_antes_de', '<', $asOf ?? now()->toDateString());
    }

    public function scopeVigentes(Builder $query, ?string $asOf = null): Builder
    {
        return $query->where(function (Builder $q) use ($asOf) {
            $q->whereNull('refrescar_antes_de')
                ->orWhere('refrescar_antes_de', '>=', $asOf ?? now()->toDateString());
        });
    }

    public function scopeConNegocio(Builder $query): Builder
    {
        return $query->where('hueco_neto_pct', '>', 0);
    }
}
