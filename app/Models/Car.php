<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand', 'model', 'version', 'year', 'mileage', 'fuel', 'transmission',
        'cv', 'displacement', 'co2', 'consumption', 'owners', 'doors',
        'seats', 'euro_norm', 'color', 'itv_date',
        'purchase_price', 'new_price', 'manual_tax_base', 'boe_confirmed',
        'transport', 'itv_fee', 'coc_fee', 'dgt_fees', 'professional_fees', 'deposit',
        'vin', 'vat_scenario', 'seller', 'city', 'lat', 'lng',
        'status', 'url_link', 'traffic_light', 'valuation', 'recommendation',
        'description', 'equipment', 'tips', 'red_flags',
        'research', 'pros', 'cons',
        'verdict', 'verdict_confidence', 'verdict_reasoning', 'verdict_changes', 'verdict_at',
        'market_avg', 'market_min', 'market_max', 'estimated_saving',
        'research_source', 'schema_version',
        'comparables_list', 'fotos_json', 'notes', 'organization_id', 'client_id',
    ];

    protected $casts = [
        'equipment' => 'array', 'tips' => 'array', 'red_flags' => 'array',
        'research' => 'array', 'pros' => 'array', 'cons' => 'array',
        'comparables_list' => 'array', 'fotos_json' => 'array', 'boe_confirmed' => 'boolean',
        'verdict_at' => 'datetime',
        'lat' => 'decimal:8', 'lng' => 'decimal:8',
        'purchase_price' => 'decimal:2', 'new_price' => 'decimal:2',
        'manual_tax_base' => 'decimal:2', 'transport' => 'decimal:2',
        'itv_fee' => 'decimal:2', 'coc_fee' => 'decimal:2', 'dgt_fees' => 'decimal:2',
        'professional_fees' => 'decimal:2', 'deposit' => 'decimal:2',
        'market_avg' => 'decimal:2', 'market_min' => 'decimal:2',
        'market_max' => 'decimal:2', 'estimated_saving' => 'decimal:2',
        'schema_version' => 'integer',
    ];

    public const VERDICTS = ['Buy', 'Buy if price drops', 'Doubtful', 'Discard'];

    public const VERDICT_CONFIDENCE = ['high', 'medium', 'low'];

    public const RESEARCH_ASPECTS = [
        'common_issues', 'recalls', 'market_price', 'reliability',
        'spain_homologation', 'dgt_label', 'insurance_estimate',
        'parts_maintenance', 'unit_specific',
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

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing']);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeTrafficLight($query, $color)
    {
        return $query->where('traffic_light', $color);
    }

    public function calculateIEDMT()
    {
        if (!$this->co2) return 0;

        $coefficients = [1.00, 0.84, 0.68, 0.57, 0.47, 0.39, 0.33, 0.28, 0.24, 0.19, 0.14, 0.10];
        $currentYear = (int) date('Y');
        $carYear = (int) substr($this->year, -4);
        $years = $currentYear - $carYear;
        $index = min(max($years, 0), count($coefficients) - 1);
        $coefficient = $coefficients[$index];

        $co2Pct = match (true) {
            $this->co2 < 120 => 0.00,
            $this->co2 < 160 => 0.0475,
            $this->co2 < 200 => 0.0975,
            default => 0.1475,
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
}
