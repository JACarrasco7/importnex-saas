<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Flag temporal (no persistido) para que el observer NO recalcule el
     * semáforo cuando viene explícito del import JSON (mercado.semaforo).
     */
    public bool $preserveTrafficLight = false;

    protected $fillable = [
        'brand', 'model', 'version', 'year', 'mileage', 'fuel', 'transmission', 'drivetrain',
        'cv', 'displacement', 'co2', 'consumption', 'owners', 'doors',
        'seats', 'euro_norm', 'color', 'itv_date',
        'pais_origen', 'co2_confirmado',
        'purchase_price', 'new_price', 'manual_tax_base', 'boe_confirmed',
        'transport', 'itv_fee', 'coc_fee', 'dgt_fees', 'professional_fees', 'deposit',
        'vin', 'vat_scenario', 'seller', 'city', 'lat', 'lng',
        'status', 'url_link', 'traffic_light', 'valuation', 'recommendation',
        'description', 'original_description', 'equipment', 'tips', 'red_flags',
        'research', 'pros', 'cons',
        'is_marketplace', 'marketplace_views',
        'verdict', 'verdict_confidence', 'verdict_reasoning', 'verdict_changes', 'verdict_at',
        'market_avg', 'market_min', 'market_max', 'estimated_saving',
        'research_source', 'schema_version',
        'comparables_list', 'fotos_json', 'notes', 'organization_id', 'client_id',
        'tracking_token', 'tracking_shared_at', 'tracking_shared_with_email',
        'tracking_revoked_at', 'tracking_views', 'expected_delivery_date',
        'ai_analysis_json', 'ai_verified_at',
    ];

    protected $casts = [
        'equipment' => 'array', 'tips' => 'array', 'red_flags' => 'array',
        'research' => 'array', 'pros' => 'array', 'cons' => 'array',
        'ai_analysis_json' => 'array',
        'ai_verified_at' => 'datetime',
        'comparables_list' => 'array', 'fotos_json' => 'array', 'boe_confirmed' => 'boolean',
        'co2_confirmado' => 'boolean',
        'is_marketplace' => 'boolean',
        'verdict_at' => 'datetime',
        'tracking_shared_at' => 'datetime',
        'tracking_revoked_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'lat' => 'decimal:8', 'lng' => 'decimal:8',
        'purchase_price' => 'decimal:2', 'new_price' => 'decimal:2',
        'manual_tax_base' => 'decimal:2', 'transport' => 'decimal:2',
        'itv_fee' => 'decimal:2', 'coc_fee' => 'decimal:2', 'dgt_fees' => 'decimal:2',
        'professional_fees' => 'decimal:2', 'deposit' => 'decimal:2',
        'market_avg' => 'decimal:2', 'market_min' => 'decimal:2',
        'market_max' => 'decimal:2', 'estimated_saving' => 'decimal:2',
        'schema_version' => 'integer',
        'marketplace_views' => 'integer',
    ];

    public const VERDICTS = ['Buy', 'Buy if price drops', 'Doubtful', 'Discard'];

    public const VERDICT_CONFIDENCE = ['high', 'medium', 'low'];

    public const RESEARCH_ASPECTS = [
        'common_issues', 'recalls', 'market_price', 'reliability',
        'spain_homologation', 'dgt_label', 'insurance_estimate',
        'parts_maintenance', 'unit_specific',
    ];

    public const STATUSES = [
        'Located', 'Valuing', 'Offered', 'Reserved', 'Purchased',
        'In_transit', 'Processing', 'Pending review', 'Verifying',
        'Delivered', 'Discarded',
    ];

    public const ACTIVE_STATUSES = [
        'Located', 'Valuing', 'Offered', 'Reserved', 'Purchased',
        'In_transit', 'Processing',
    ];

    public const KANBAN_STATUSES = [
        'Located', 'Valuing', 'Offered', 'Reserved', 'Purchased',
        'In_transit', 'Processing', 'Delivered',
    ];

    /** Estados en los que el tracking público está disponible para el cliente. */
    public const TRACKABLE_STATUSES = [
        'Purchased', 'In_transit', 'Processing',
        'Pending review', 'Verifying', 'Delivered',
    ];

    public const CURRENT_SCHEMA_VERSION = 1;

    protected static function booted()
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && auth()->user()->organization_id) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function photos()
    {
        return $this->hasMany(CarPhoto::class)->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(CarDocument::class);
    }

    public function expenses()
    {
        return $this->hasMany(CarExpense::class);
    }

    public function checklists()
    {
        return $this->hasMany(CarChecklist::class);
    }

    public function marketingContents()
    {
        return $this->hasMany(CarMarketingContent::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeTrafficLight($query, $color)
    {
        return $query->where('traffic_light', $color);
    }

    /**
     * El IEDMT (impuesto de matriculación) solo se paga al IMPORTAR un coche
     * de otro país. Si la unidad es de origen español (compra nacional), no
     * se devenga: el método devuelve 0 y el coste total no lo suma.
     *
     * Sin `pais_origen` (legacy) se asume importación — el negocio es
     * principalmente DE→ES y así se preserva el comportamiento previo.
     */
    public function isImport(): bool
    {
        $pais = strtolower((string) ($this->pais_origen ?? ''));

        // Sin origen → asumir importación (comportamiento previo).
        if ($pais === '') {
            return true;
        }

        // Explícitamente español → compra nacional, sin IEDMT.
        if ($pais === 'es' || str_contains($pais, 'espa')) {
            return false;
        }

        // Cualquier otro país (de, alemania, ...) → importación.
        return true;
    }

    public function calculateIEDMT()
    {
        // Unidad española: sin IEDMT (no hay importación).
        if (! $this->isImport()) {
            return 0;
        }

        if (! $this->co2) {
            return 0;
        }

        $coefficients = config('iedmt.coeficientes_antiguedad');
        $currentYear = (int) date('Y');
        $carYear = (int) substr($this->year, -4);
        $years = max(0, $currentYear - $carYear);
        $index = min($years, count($coefficients) - 1);
        $coefficient = $coefficients[$index];

        $co2 = (int) $this->co2;
        $co2Pct = match (true) {
            $co2 <= 120 => config('iedmt.tipos_co2.max_120'),
            $co2 <= 159 => config('iedmt.tipos_co2.max_159'),
            $co2 <= 199 => config('iedmt.tipos_co2.max_199'),
            default => config('iedmt.tipos_co2.default'),
        };

        $taxBase = $this->boe_confirmed ? $this->new_price : $this->manual_tax_base;

        return $taxBase * $coefficient * $co2Pct;
    }

    public function calculateTotalCost()
    {
        return $this->purchase_price
            + $this->transport
            + $this->itv_fee
            + $this->coc_fee
            + $this->dgt_fees
            + $this->professional_fees
            + $this->calculateIEDMT();
    }

    /**
     * Aspectos de investigación que aún no tienen finding.
     */
    public function getResearchGapsAttribute(): array
    {
        $research = $this->research ?? [];
        $gaps = [];
        foreach (self::RESEARCH_ASPECTS as $aspect) {
            $finding = $research[$aspect]['finding'] ?? null;
            if ($finding === null || $finding === '') {
                $gaps[] = $aspect;
            }
        }

        return $gaps;
    }

    /**
     * Stats de mercado: avg, min, max, count calculados desde comparables_list.
     * Soporta tanto el formato legacy ({p: '17.990 €'}) como el nuevo ({price: 17990}).
     */
    public function getComparablesStatsAttribute(): array
    {
        $comparables = $this->comparables_list ?? [];
        $prices = [];
        foreach ($comparables as $c) {
            if (! is_array($c)) {
                continue;
            }
            $raw = $c['price'] ?? $c['p'] ?? null;
            if ($raw === null) {
                continue;
            }
            if (is_numeric($raw)) {
                $prices[] = (float) $raw;

                continue;
            }
            if (preg_match('/(\d[\d\.]*)/', (string) $raw, $m)) {
                $prices[] = (float) str_replace('.', '', $m[1]);
            }
        }
        if (empty($prices)) {
            return ['avg' => null, 'min' => null, 'max' => null, 'count' => 0];
        }

        return [
            'avg' => array_sum($prices) / count($prices),
            'min' => min($prices),
            'max' => max($prices),
            'count' => count($prices),
        ];
    }

    /**
     * Crea una fila vacía de research para un aspecto si no existe.
     */
    public function setResearchAspect(string $aspect, array $data): void
    {
        $research = $this->research ?? [];
        $research[$aspect] = array_merge([
            'finding' => null,
            'source' => null,
            'rating' => null,
            'date' => null,
        ], $data);
        $this->research = $research;
    }

    // ───────────────────────────────────────────────────────────────────────
    // Tracking público del proceso (URL compartida con el cliente)
    // ───────────────────────────────────────────────────────────────────────

    /** Coches con token válido, no revocado y en estado trackeable. */
    public function scopePublicTracking($query)
    {
        return $query
            ->whereNotNull('tracking_token')
            ->whereNull('tracking_revoked_at')
            ->whereIn('status', self::TRACKABLE_STATUSES);
    }

    /** Genera un token único de 40 caracteres. NO persiste (uso interno). */
    public static function generateTrackingToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::withoutGlobalScopes()->where('tracking_token', $token)->exists());

        return $token;
    }

    /** Genera un token nuevo, lo persiste y devuelve la URL pública completa. */
    public function regenerateTrackingToken(): string
    {
        $this->tracking_token = self::generateTrackingToken();
        $this->save();

        return $this->tracking_url;
    }

    /** Marca como compartido: timestamp + email opcional + URL lista. */
    public function shareTracking(?string $email = null): string
    {
        if (! $this->tracking_token) {
            $this->tracking_token = self::generateTrackingToken();
        }
        $this->tracking_shared_at = now();
        $this->tracking_revoked_at = null;
        $this->tracking_shared_with_email = $email;
        $this->save();

        return $this->tracking_url;
    }

    /** Soft revoke: el token sigue existiendo pero deja de ser público. */
    public function revokeTracking(): void
    {
        $this->tracking_revoked_at = now();
        $this->save();
    }

    public function getTrackingUrlAttribute(): string
    {
        if (! $this->tracking_token) {
            return '';
        }

        $base = rtrim(config('app.url', ''), '/');

        return $base.'/tracking/'.$this->tracking_token;
    }

    public function getIsTrackingSharedAttribute(): bool
    {
        return ! empty($this->tracking_token) && empty($this->tracking_revoked_at);
    }

    public function getIsPublicTrackableAttribute(): bool
    {
        return $this->is_tracking_shared
            && in_array($this->status, self::TRACKABLE_STATUSES, true);
    }
}
