<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Imports a chat valuation JSON report into a Car.
 *
 * Contract (English keys — Spanish JSON is auto-translated):
 *   _meta.schema_version, vehiculo, anuncio, investigacion, balance, veredicto, costes, mercado, avisos
 *
 * Validation and persistence are split so the same code can run from CLI or HTTP.
 */
class ValuationImporter
{
    /**
     * Schema versioning — bump when contract changes incompatibly.
     */
    public const SUPPORTED_SCHEMA_VERSION = 1;

    /** Aspect keys we accept (mapped to canonical English). */
    private const RESEARCH_ASPECT_MAP = [
        'problemas_comunes' => 'common_issues',
        'recalls'           => 'recalls',
        'precio_mercado'    => 'market_price',
        'fiabilidad'        => 'reliability',
        'homologacion'      => 'spain_homologation',
        'etiqueta_ambiental'=> 'dgt_label',
        'seguro'            => 'insurance_estimate',
        'piezas'            => 'parts_maintenance',
        'otros'             => 'unit_specific',
    ];

    /** Verdict translation table — chat uses Spanish, DB enum uses English. */
    private const VERDICT_MAP = [
        'comprar'                  => 'Buy',
        'comprar si baja de precio'=> 'Buy if price drops',
        'comprar si baja'          => 'Buy if price drops',
        'dudoso'                   => 'Doubtful',
        'descartar'                => 'Discard',
        // English already (passthrough)
        'buy'                       => 'Buy',
        'buy if price drops'        => 'Buy if price drops',
        'doubtful'                  => 'Doubtful',
        'discard'                   => 'Discard',
    ];

    private const CONFIDENCE_MAP = [
        'alta' => 'high', 'alto' => 'high', 'high' => 'high',
        'media'=> 'medium', 'medio'=> 'medium', 'medium'=> 'medium',
        'baja' => 'low', 'bajo' => 'low', 'low' => 'low',
    ];

    private const RATING_MAP = [
        'favorable'    => 'favorable',
        'positivo'     => 'favorable',
        'bueno'        => 'favorable',
        'neutro'       => 'neutral',
        'neutral'      => 'neutral',
        'desfavorable' => 'unfavorable',
        'negativo'     => 'unfavorable',
        'malo'         => 'unfavorable',
        'unfavorable'  => 'unfavorable',
    ];

    private const WEIGHT_MAP = [
        'alto'  => 'high',   'alta'  => 'high',   'high'   => 'high',
        'medio' => 'medium', 'media' => 'medium', 'medium' => 'medium',
        'bajo'  => 'low',    'baja'  => 'low',    'low'    => 'low',
    ];

    private const FUEL_MAP = [
        'diésel'  => 'Diesel',  'diesel'  => 'Diesel',
        'gasolina'=> 'Gasoline','gasoline'=> 'Gasoline',
        'híbrido' => 'Hybrid',  'hibrido' => 'Hybrid',  'hybrid' => 'Hybrid',
        'eléctrico'=> 'Electric','electrico'=> 'Electric','electric'=> 'Electric',
    ];

    private const TRANSMISSION_MAP = [
        'manual'    => 'Manual', 'manual'    => 'Manual',
        'automático'=> 'Automatic','automatico'=> 'Automatic','automatic'=> 'Automatic',
    ];

    /**
     * Validate structure and schema_version.
     *
     * @throws RuntimeException
     */
    public function validate(array $payload): array
    {
        if (! isset($payload['_meta']['schema_version'])) {
            throw new RuntimeException('Missing _meta.schema_version');
        }

        $version = (int) $payload['_meta']['schema_version'];
        if ($version !== self::SUPPORTED_SCHEMA_VERSION) {
            throw new RuntimeException(sprintf(
                'Schema version %d not supported (current: %d). Update ValuationImporter before running.',
                $version,
                self::SUPPORTED_SCHEMA_VERSION
            ));
        }

        return $payload;
    }

    /**
     * Resolve an existing car by VIN, then by url_link; otherwise return a new Car instance.
     */
    public function resolveCar(array $payload, Organization $org): Car
    {
        $vin = $payload['vehiculo']['vin'] ?? null;
        $url = $payload['anuncio']['url'] ?? null;

        if ($vin) {
            $car = Car::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->where('vin', $vin)
                ->first();
            if ($car) {
                return $car;
            }
        }

        if ($url) {
            $car = Car::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->where('url_link', $url)
                ->first();
            if ($car) {
                return $car;
            }
        }

        return new Car(['organization_id' => $org->id]);
    }

    /**
     * Apply report fields to the car and persist.
     */
    public function apply(Car $car, array $payload): Car
    {
        $v  = $payload['vehiculo']  ?? [];
        $a  = $payload['anuncio']   ?? [];
        $i  = $payload['investigacion'] ?? [];
        $b  = $payload['balance']   ?? [];
        $vd = $payload['veredicto'] ?? [];
        $c  = $payload['costes']    ?? [];
        $m  = $payload['mercado']   ?? [];

        DB::transaction(function () use ($car, $v, $a, $i, $b, $vd, $c, $m, $payload) {
            $car->fill(array_filter([
                // Identity
                'brand'   => $v['marca']    ?? null,
                'model'   => $v['modelo']   ?? null,
                'version' => $v['version']  ?? null,
                'mileage' => $v['km']       ?? null,
                'vin'     => $v['vin']      ?? null,
                'fuel'    => $this->translate($v['combustible'] ?? null, self::FUEL_MAP),
                'transmission' => $this->translate($v['cambio'] ?? null, self::TRANSMISSION_MAP),
                'cv'      => $v['potencia_cv'] ?? null,
                'co2'     => $v['co2_gkm']     ?? null,
                'color'   => $v['color_exterior'] ?? null,
                'doors'   => $v['puertas'] ?? null,
                'seats'   => $v['plazas']   ?? null,
                'owners'  => $v['propietarios'] ?? null,

                // year: JSON may be "2019" or "MM/YYYY" — normalize
                'year'    => $this->normalizeYear($v['anio'] ?? null),

                // Listing
                'url_link'=> $a['url']          ?? null,
                'city'    => $a['ciudad']       ?? null,
                'seller'  => $this->formatSeller($a),
                'description' => $a['descripcion_traducida'] ?? $a['descripcion_original'] ?? null,
                'equipment'   => $this->normalizeEquipment($v['equipamiento'] ?? []),

                // Costs (from the chat's breakdown)
                'purchase_price'  => $c['precio_coche']       ?? null,
                'transport'       => $c['transporte']         ?? null,
                'itv_fee'         => $c['itv_matriculacion']  ?? null,
                'dgt_fees'        => $c['tasa_dgt']           ?? null,
                'professional_fees'=> $c['gestoria']           ?? null,

                // Enriched valuation
                'research'        => $this->normalizeResearch($i),
                'pros'            => $this->normalizeWeighted($b['a_favor'] ?? []),
                'cons'            => $this->normalizeWeighted($b['en_contra'] ?? []),

                'verdict'             => $this->translate($vd['recomendacion'] ?? null, self::VERDICT_MAP),
                'verdict_confidence'  => $this->translate($vd['confianza']     ?? null, self::CONFIDENCE_MAP),
                'verdict_reasoning'   => $vd['razonamiento']    ?? null,
                'verdict_changes'     => $vd['que_cambiaria']   ?? null,
                'verdict_at'          => now(),

                // Market
                'market_avg'        => $m['precio_medio']    ?? null,
                'market_min'        => $m['precio_min']      ?? null,
                'market_max'        => $m['precio_max']      ?? null,
                'estimated_saving'  => $m['ahorro_estimado'] ?? null,
                'comparables_list'  => $this->normalizeComparables($m['comparables'] ?? []),

                // Source
                'research_source'  => 'chat',
                'schema_version'   => self::SUPPORTED_SCHEMA_VERSION,

                // Notes: append everything that didn't map to a column
                'notes' => $this->buildNotes($v, $c, $vd, $payload['avisos'] ?? []),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []));

            $car->save();
        });

        return $car;
    }

    private function translate(?string $value, array $map): ?string
    {
        if ($value === null) {
            return null;
        }
        $key = mb_strtolower(trim($value));
        return $map[$key] ?? ucfirst($value);
    }

    private function normalizeYear($raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        if (is_int($raw) || (is_string($raw) && preg_match('/^\d{4}$/', $raw))) {
            // "2019" → "01/2019"
            return sprintf('01/%04d', (int) $raw);
        }
        // already "MM/YYYY" or similar — trust it
        return (string) $raw;
    }

    private function formatSeller(array $ad): ?string
    {
        $name = $ad['vendedor_nombre'] ?? null;
        $type = $ad['vendedor_tipo']   ?? null;
        if ($name && $type) {
            return "{$name} ({$type})";
        }
        return $name ?: ($type ?: null);
    }

    private function normalizeEquipment(array $items): array
    {
        return array_values(array_filter(array_map('strval', $items)));
    }

    private function normalizeResearch(array $research): array
    {
        $out = [];
        foreach ($research as $aspectKey => $data) {
            if (! is_array($data)) {
                continue;
            }
            $canonical = self::RESEARCH_ASPECT_MAP[mb_strtolower((string) $aspectKey)] ?? $aspectKey;
            $out[$canonical] = [
                'finding' => $data['hallazgo']   ?? $data['finding']  ?? null,
                'source'  => $data['fuente']     ?? $data['source']   ?? null,
                'rating'  => $this->translate($data['valoracion'] ?? null, self::RATING_MAP),
                'date'    => $data['fecha']      ?? $data['date']     ?? null,
            ];
        }
        return $out;
    }

    private function normalizeWeighted(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $out[] = ['text' => $item, 'weight' => 'medium'];
                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'text'   => $item['texto']  ?? $item['text']   ?? '',
                'weight' => $this->translate($item['peso'] ?? null, self::WEIGHT_MAP) ?? 'medium',
            ];
        }
        return $out;
    }

    private function normalizeComparables(array $comparables): array
    {
        $out = [];
        foreach ($comparables as $c) {
            if (! is_array($c)) {
                continue;
            }
            $out[] = array_filter([
                'title' => $c['titulo']  ?? $c['t'] ?? null,
                'price' => $c['precio']  ?? $c['p'] ?? null,
                'km'    => $c['km']      ?? null,
                'url'   => $c['url']     ?? $c['u'] ?? null,
                'country'=> $c['pais']   ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }
        return $out;
    }

    private function buildNotes(array $v, array $c, array $vd, array $avisos): string
    {
        $lines = [];
        if (! empty($v['garantia'])) {
            $lines[] = 'Warranty: ' . $v['garantia'];
        }
        if (! empty($v['accidentes_declarados'])) {
            $lines[] = 'Accidents (declared): ' . $v['accidentes_declarados'];
        }
        if (! empty($v['historial_mantenimiento'])) {
            $lines[] = 'Maintenance: ' . $v['historial_mantenimiento'];
        }
        if (! empty($c['iedmt_es_estimacion'])) {
            $lines[] = 'IEDMT is an estimate — Hacienda calculates on its official tables.';
        }
        if (! empty($vd['fecha'])) {
            $lines[] = 'Verdict date: ' . $vd['fecha'];
        }
        foreach ($avisos as $a) {
            $lines[] = '• ' . $a;
        }
        return implode("\n", $lines);
    }
}
