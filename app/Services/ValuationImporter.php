<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Organization;
use App\Support\UrlNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Cuando el informe llega dentro de un paquete .zip que ya trae las fotos
     * en local, no tiene sentido descargarlas otra vez del portal (y ademas el
     * portal suele bloquear la descarga). El ingestor del paquete pone esto a
     * true antes de llamar a apply().
     */
    public bool $skipRemotePhotos = false;

    public function __construct(private readonly GeoCoder $geoCoder = new GeoCoder) {}

    /** Aspect keys we accept (mapped to canonical English). */
    private const RESEARCH_ASPECT_MAP = [
        'problemas_comunes' => 'common_issues',
        'recalls' => 'recalls',
        'precio_mercado' => 'market_price',
        'fiabilidad' => 'reliability',
        'homologacion' => 'spain_homologation',
        'etiqueta_ambiental' => 'dgt_label',
        'seguro' => 'insurance_estimate',
        'piezas' => 'parts_maintenance',
        'otros' => 'unit_specific',
    ];

    /** Verdict translation table — chat uses Spanish, DB enum uses English. */
    private const VERDICT_MAP = [
        'comprar' => 'Buy',
        'comprar si baja de precio' => 'Buy if price drops',
        'comprar si baja' => 'Buy if price drops',
        'dudoso' => 'Doubtful',
        'descartar' => 'Discard',
        // English already (passthrough)
        'buy' => 'Buy',
        'buy if price drops' => 'Buy if price drops',
        'doubtful' => 'Doubtful',
        'discard' => 'Discard',
    ];

    private const CONFIDENCE_MAP = [
        'alta' => 'high', 'alto' => 'high', 'high' => 'high',
        'media' => 'medium', 'medio' => 'medium', 'medium' => 'medium',
        'baja' => 'low', 'bajo' => 'low', 'low' => 'low',
    ];

    private const RATING_MAP = [
        'favorable' => 'favorable',
        'positivo' => 'favorable',
        'bueno' => 'favorable',
        'neutro' => 'neutral',
        'neutral' => 'neutral',
        'desfavorable' => 'unfavorable',
        'negativo' => 'unfavorable',
        'malo' => 'unfavorable',
        'unfavorable' => 'unfavorable',
    ];

    private const WEIGHT_MAP = [
        'alto' => 'high',   'alta' => 'high',   'high' => 'high',
        'medio' => 'medium', 'media' => 'medium', 'medium' => 'medium',
        'bajo' => 'low',    'baja' => 'low',    'low' => 'low',
    ];

    private const FUEL_MAP = [
        'diésel' => 'Diesel',  'diesel' => 'Diesel',
        'gasolina' => 'Gasoline', 'gasoline' => 'Gasoline',
        'híbrido' => 'Hybrid',  'hibrido' => 'Hybrid',  'hybrid' => 'Hybrid',
        'eléctrico' => 'Electric', 'electrico' => 'Electric', 'electric' => 'Electric',
    ];

    private const TRANSMISSION_MAP = [
        'manual' => 'Manual', 'manual' => 'Manual',
        'automático' => 'Automatic', 'automatico' => 'Automatic', 'automatic' => 'Automatic',
    ];

    /**
     * §3.4 — Mapeo de traccion (chat JSON) → drivetrain (BD).
     * Español/inglés/alemán → valores canónicos en inglés.
     */
    private const DRIVETRAIN_MAP = [
        // Español
        'delantera' => 'FWD', 'delantero' => 'FWD', 'tracción delantera' => 'FWD',
        'trasera' => 'RWD', 'trasero' => 'RWD', 'tracción trasera' => 'RWD', 'propulsión' => 'RWD',
        'total' => 'AWD', 'tracción total' => 'AWD', 'integral' => 'AWD', 'tracción integral' => 'AWD',
        '4x4' => 'AWD', 'cuatro por cuatro' => 'AWD',
        // Inglés (passthrough)
        'fwd' => 'FWD', 'front' => 'FWD', 'front-wheel drive' => 'FWD',
        'rwd' => 'RWD', 'rear' => 'RWD', 'rear-wheel drive' => 'RWD',
        'awd' => 'AWD', 'all-wheel drive' => 'AWD', '4wd' => 'AWD', 'four-wheel drive' => 'AWD',
        // Alemán (mobile.de)
        'front' => 'FWD', 'heck' => 'RWD', 'allrad' => 'AWD',
    ];

    /**
     * Validate structure, schema_version, required blocks and optional flujo.
     *
     * @param  array  $payload  The full JSON payload
     * @param  string[]  $requiredBlocks  Top-level blocks that must be present (e.g. ['_meta','vehiculo','anuncio'])
     * @param  string|null  $expectedFlujo  If set, _meta.flujo must match (for endpoints B/C). Null = don't check.
     *
     * @throws RuntimeException
     */
    public function validate(array $payload, array $requiredBlocks = ['_meta'], ?string $expectedFlujo = null): array
    {
        // §10.7 — validate minimum structure
        foreach ($requiredBlocks as $block) {
            if (! isset($payload[$block])) {
                throw new RuntimeException("Missing required top-level block: '{$block}'");
            }
        }

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

        // §10.6 — validate flujo if endpoint requires it (Flujo B/C)
        if ($expectedFlujo !== null) {
            $actualFlujo = $payload['_meta']['flujo'] ?? null;
            if ($actualFlujo !== $expectedFlujo) {
                throw new RuntimeException(sprintf(
                    "Invalid _meta.flujo. Expected '%s', got '%s'.",
                    $expectedFlujo,
                    $actualFlujo ?? 'null'
                ));
            }
        }

        // §contrato — brand/model son NOT NULL en BD: devolver 422 limpio en vez de 500 SQL
        if (isset($payload['vehiculo'])) {
            $brand = trim((string) ($payload['vehiculo']['marca'] ?? ''));
            $model = trim((string) ($payload['vehiculo']['modelo'] ?? ''));
            if ($brand === '' || $model === '') {
                throw new RuntimeException('Missing required fields: vehiculo.marca y vehiculo.modelo son obligatorios.');
            }
        }

        // §contrato — pvp_nuevo es OBLIGATORIO en Flujo A: sin él, IEDMT = 0 y el coste total sale mal
        $flujo = $payload['_meta']['flujo'] ?? 'A';
        if ($flujo === 'A' && isset($payload['costes']) && ! isset($payload['costes']['pvp_nuevo'])) {
            throw new RuntimeException('Missing required field: costes.pvp_nuevo (obligatorio para calcular el IEDMT).');
        }

        return $payload;
    }

    /**
     * Resolve an existing car by VIN, then by url_link, then by combined criteria; otherwise return a new Car instance.
     */
    public function resolveCar(array $payload, Organization $org): Car
    {
        $vin = $payload['vehiculo']['vin'] ?? null;
        $url = $payload['anuncio']['url'] ?? null;
        $brand = $payload['vehiculo']['marca'] ?? null;
        $model = $payload['vehiculo']['modelo'] ?? null;
        $year = $payload['vehiculo']['anio'] ?? null;

        // Normalizar año (puede venir como "2019" o "MM/YYYY")
        $normalizedYear = $this->normalizeYear($year);

        // Buscar primero por VIN (es el identificador más fiable)
        if ($vin && ! empty(trim($vin))) {
            $car = Car::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->where('vin', trim($vin))
                ->first();
            if ($car) {
                Log::info("Coche encontrado por VIN: {$vin}", ['car_id' => $car->id]);

                return $car;
            }
        }

        // Buscar por URL (segundo identificador fiable)
        if ($url && ! empty(trim($url))) {
            // §2.1 — Usar helper reutilizable para normalizar URL
            $normalizedUrl = UrlNormalizer::normalize($url);

            // También buscar con la URL normalizada en BD
            $car = Car::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->where(function ($query) use ($url, $normalizedUrl) {
                    $query->where('url_link', trim($url))
                        ->orWhere('url_link', $normalizedUrl);
                })
                ->first();

            if ($car) {
                Log::info("Coche encontrado por URL: {$url}", ['car_id' => $car->id]);

                return $car;
            }
        }

        // Buscar por combinación de marca, modelo y año (para casos sin VIN ni URL)
        if ($brand && $model && $normalizedYear) {
            $car = Car::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->where('brand', trim($brand))
                ->where('model', trim($model))
                ->where('year', $normalizedYear)
                ->first();

            if ($car) {
                Log::info("Coche encontrado por marca/modelo/año: {$brand} {$model} {$normalizedYear}", ['car_id' => $car->id]);

                return $car;
            }
        }

        // Si no se encuentra nada, crear un nuevo coche
        Log::warning('No se encontró coche existente, creando nuevo', [
            'vin' => $vin,
            'url' => $url,
            'brand' => $brand,
            'model' => $model,
            'year' => $normalizedYear,
            'organization_id' => $org->id,
        ]);

        return new Car(['organization_id' => $org->id]);
    }

    /**
     * Apply report fields to the car and persist.
     */
    public function apply(Car $car, array $payload): Car
    {
        $v = $payload['vehiculo'] ?? [];
        $a = $payload['anuncio'] ?? [];
        $i = $payload['investigacion'] ?? [];
        $b = $payload['balance'] ?? [];
        $vd = $payload['veredicto'] ?? [];
        $c = $payload['costes'] ?? [];
        $m = $payload['mercado'] ?? [];

        // Si Claude manda semáforo explícito, respetarlo (el observer lo pisaría).
        if (isset($m['semaforo']) && $m['semaforo'] !== null && $m['semaforo'] !== '') {
            $car->preserveTrafficLight = true;
        }

        // §mapa (15-ago-2026) — coordenadas: si el JSON trae lat/lng se usan;
        // si no, se geolocaliza la ciudad (cache permanente) para que el
        // coche importado aparezca en el mapa.
        $coords = $this->resolveCoords($a);
        if ($coords !== null) {
            $car->lat = $coords['lat'];
            $car->lng = $coords['lng'];
        }

        // §3.1 — co2_confirmado: warning en avisos
        $avisos = $payload['avisos'] ?? [];
        if (isset($v['co2_confirmado']) && $v['co2_confirmado'] === false) {
            $avisos[] = 'CO₂ no confirmado por COC. El IEDMT puede variar significativamente.';
            Log::info('ValuationImporter: CO₂ no confirmado', [
                'marca' => $v['marca'] ?? null,
                'modelo' => $v['modelo'] ?? null,
                'co2_gkm' => $v['co2_gkm'] ?? null,
            ]);
        }

        // §3.2 — comparables: filtrar los que no tienen URL
        $comparablesOriginales = $m['comparables'] ?? [];
        $comparablesFiltrados = array_values(array_filter(
            $comparablesOriginales,
            fn ($c) => ! empty($c['url'])
        ));
        $sinUrl = count($comparablesOriginales) - count($comparablesFiltrados);
        if ($sinUrl > 0) {
            Log::warning('ValuationImporter: comparables sin URL filtrados', [
                'count' => $sinUrl,
                'total_original' => count($comparablesOriginales),
            ]);
            $avisos[] = "{$sinUrl} comparables descartados por no tener URL.";
            $m['comparables'] = $comparablesFiltrados;
        }

        // §3.3 — precio_objetivo obligatorio si recomendación es "Comprar si baja..."
        $recomendacion = strtolower($vd['recomendacion'] ?? '');
        if (str_contains($recomendacion, 'comprar si baja') && ! isset($vd['precio_objetivo'])) {
            throw new RuntimeException(
                'precio_objetivo es obligatorio cuando la recomendación es "Comprar si baja de precio"'
            );
        }

        // §contrato — verificar IEDMT de Claude contra el recálculo de Laravel (>10% → aviso)
        $iedmtClaude = isset($c['iedmt_estimado']) && $c['iedmt_estimado'] !== null
            ? (float) $c['iedmt_estimado']
            : null;
        if ($iedmtClaude !== null) {
            $probe = new Car;
            $probe->co2 = $v['co2_gkm'] ?? null;
            $probe->year = $this->normalizeYear($v['anio'] ?? null);
            $probe->boe_confirmed = false;
            $probe->manual_tax_base = $c['pvp_nuevo'] ?? null;
            $probe->new_price = $c['pvp_nuevo'] ?? null;
            $iedmtLaravel = (float) $probe->calculateIEDMT();
            if ($iedmtLaravel > 0) {
                $diffPct = abs($iedmtClaude - $iedmtLaravel) / $iedmtLaravel;
                if ($diffPct > 0.10) {
                    $avisos[] = sprintf(
                        'IEDMT: Claude estima %d € y Laravel recalcula %d € (diferencia >10%%). Revisar CO₂ y PVP.',
                        (int) round($iedmtClaude),
                        (int) round($iedmtLaravel)
                    );
                }
            }
        }

        // Persistir avisos actualizados en payload para uso posterior
        $payload['avisos'] = $avisos;

        $wasNew = ! $car->exists;

        DB::transaction(function () use ($car, $v, $a, $i, $b, $vd, $c, $m, $payload, $wasNew) {
            $car->fill(array_filter([
                // Identity
                'brand' => $v['marca'] ?? null,
                'model' => $v['modelo'] ?? null,
                'version' => $v['version'] ?? null,
                'mileage' => $v['km'] ?? null,
                'vin' => $v['vin'] ?? null,
                'fuel' => $this->translate($v['combustible'] ?? null, self::FUEL_MAP),
                'transmission' => $this->translate($v['cambio'] ?? null, self::TRANSMISSION_MAP),
                'drivetrain' => $this->translate($v['traccion'] ?? null, self::DRIVETRAIN_MAP),
                'cv' => $v['potencia_cv'] ?? null,
                'co2' => $v['co2_gkm'] ?? null,
                'co2_confirmado' => $v['co2_confirmado'] ?? null,
                'color' => $v['color_exterior'] ?? null,
                'doors' => $v['puertas'] ?? null,
                'seats' => $v['plazas'] ?? null,
                'owners' => $v['propietarios'] ?? null,

                // year: JSON may be "2019" or "MM/YYYY" — normalize
                'year' => $this->normalizeYear($v['anio'] ?? null),

                // Listing
                'url_link' => $a['url'] ?? null,
                'city' => $a['ciudad'] ?? null,
                'pais_origen' => $a['pais_origen'] ?? null,
                'seller' => $this->formatSeller($a),
                'description' => $a['descripcion_traducida'] ?? $a['descripcion_original'] ?? null,
                'original_description' => $a['descripcion_original'] ?? null,
                'equipment' => $this->normalizeEquipment($v['equipamiento'] ?? []),

                // Costs (from the chat's breakdown)
                'purchase_price' => $c['precio_coche'] ?? null,
                'transport' => $c['transporte'] ?? null,
                'itv_fee' => $c['itv_matriculacion'] ?? null,
                'dgt_fees' => $c['tasa_dgt'] ?? null,

                // Honorarios de JJ + gestoria van a la misma columna: son lo que
                // cobramos por encima del coste. Antes solo se leia 'gestoria'
                // (casi siempre 0) y los honorarios se perdian.
                'professional_fees' => $this->sumProfessionalFees($c),

                // Base del IEDMT. La app NO guarda el importe del impuesto: lo
                // recalcula con Car::calculateIEDMT() a partir de esta base, la
                // antiguedad y el CO2. Si el chat no manda la base, el impuesto
                // sale 0 y el coste total queda por debajo del real.
                //
                // Ojo: aqui va el PVP del coche NUEVO, sin depreciar. El
                // coeficiente por antiguedad lo aplica calculateIEDMT() despues,
                // asi que mandar la base ya depreciada lo depreciaria dos veces.
                'new_price' => $c['pvp_nuevo'] ?? null,
                'manual_tax_base' => $c['pvp_nuevo'] ?? null,

                // Enriched valuation
                'research' => $this->normalizeResearch($i),
                'pros' => $this->normalizeWeighted($b['a_favor'] ?? []),
                'cons' => $this->normalizeWeighted($b['en_contra'] ?? []),

                'verdict' => $this->translate($vd['recomendacion'] ?? null, self::VERDICT_MAP),
                'verdict_confidence' => $this->translate($vd['confianza'] ?? null, self::CONFIDENCE_MAP),
                'verdict_reasoning' => $vd['razonamiento'] ?? null,
                'verdict_changes' => $vd['que_cambiaria'] ?? null,
                'verdict_at' => now(),

                // Market
                'market_avg' => $m['precio_medio'] ?? null,
                'market_min' => $m['precio_min'] ?? null,
                'market_max' => $m['precio_max'] ?? null,
                'estimated_saving' => $m['ahorro_estimado'] ?? null,
                // NOTA: traffic_light NO se persiste desde el JSON — CarObserver::saving()
                // lo recalcula a partir de costes y market_avg en cada guardado.
                'comparables_list' => $this->normalizeComparables($m['comparables'] ?? []),

                // Source
                'research_source' => 'chat',
                // §8 (auditoría) — guardar la versión real del payload, no hardcodear
                'schema_version' => $payload['_meta']['schema_version'] ?? self::SUPPORTED_SCHEMA_VERSION,

                // Notes: append everything that didn't map to a column
                'notes' => $this->buildNotes($v, $c, $vd, $payload['avisos'] ?? [], $payload['fuentes'] ?? [], $a),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []));

            $car->save();

            if ($wasNew) {
                Log::info('Nuevo coche creado', [
                    'car_id' => $car->id,
                    'vin' => $car->vin,
                    'url' => $car->url_link,
                    'organization_id' => $car->organization_id,
                ]);
            } else {
                Log::info('Coche actualizado', [
                    'car_id' => $car->id,
                    'vin' => $car->vin,
                    'url' => $car->url_link,
                    'organization_id' => $car->organization_id,
                ]);
            }

            // Guardar fotos y archivos en la estructura de carpetas por organización
            $this->savePhotosAndFiles($car, $payload);
        });

        return $car;
    }

    /**
     * Guardar fotos y archivos en la estructura de carpetas por organización
     */
    private function savePhotosAndFiles(Car $car, array $payload): void
    {
        $org = $car->organization;
        $orgDirName = str_replace(' ', '_', $org->name);

        // Crear estructura de carpetas: organization_name/vehicles/car_id/
        // Skip if running in testing environment (storage_path is read-only in CI/test)
        if (app()->environment('testing')) {
            return;
        }

        $vehicleDir = storage_path('app/importnex/import/'.$orgDirName.'/vehicles/'.$car->id);
        if (! file_exists($vehicleDir)) {
            @mkdir($vehicleDir, 0755, true);
        }

        // Guardar el informe JSON
        $reportFile = $vehicleDir.'/informe_'.now()->format('Ymd-His').'.json';
        @file_put_contents($reportFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Procesar fotos si existen (salvo que el paquete ya las traiga en local)
        // §contrato — retrocompatibilidad: el contrato documentaba fotos en anuncio.fotos;
        // la ubicación canónica es vehiculo.fotos, pero aceptamos ambas.
        $fotos = $payload['vehiculo']['fotos'] ?? $payload['anuncio']['fotos'] ?? [];
        if (! empty($fotos) && ! $this->skipRemotePhotos) {
            $this->savePhotos($car, $fotos);
        }
    }

    /**
     * Descarga las fotos del anuncio y las registra en car_photos.
     *
     * Dos correcciones importantes frente a la version anterior:
     *  - Se guardan en el disco 'public' bajo cars/{id}/photos, que es donde la
     *    ficha las busca (<img :src="`/storage/${photo.url}`">). Antes se
     *    guardaba el fichero en storage/app/importnex y en `url` se metia la URL
     *    remota, asi que la imagen nunca se veia.
     *  - Se descargan con User-Agent y Referer. Los CDN de los portales
     *    (classistatic.de y compania) rechazan file_get_contents sin cabeceras,
     *    que era la razon por la que no entraba ninguna foto.
     */
    private function savePhotos(Car $car, array $photoUrls): void
    {
        // No duplicar si el coche ya tiene fotos (reimportacion / reevaluacion).
        if ($car->photos()->count() > 0) {
            return;
        }

        $referer = $car->url_link ?: null;
        $order = 0;

        foreach ($photoUrls as $url) {
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $request = Http::timeout(20)->withHeaders(array_filter([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                    'Accept' => 'image/avif,image/webp,image/jpeg,image/png,*/*',
                    'Referer' => $referer,
                ]));

                $response = $request->get($url);

                if (! $response->successful() || $response->body() === '') {
                    Log::warning('Could not download photo', [
                        'url' => $url, 'car_id' => $car->id, 'status' => $response->status(),
                    ]);

                    continue;
                }

                $order++;
                $extension = $this->guessImageExtension($response->header('Content-Type'), $url);
                $path = sprintf('cars/%d/photos/%03d.%s', $car->id, $order, $extension);

                Storage::disk('public')->put($path, $response->body());

                $car->photos()->create([
                    'organization_id' => $car->organization_id,
                    'url' => $path,
                    'sort_order' => $order,
                    'photo_type' => 'exterior',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Photo download failed', [
                    'url' => $url, 'car_id' => $car->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function guessImageExtension(?string $contentType, string $url): string
    {
        $map = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png',
            'image/webp' => 'webp', 'image/avif' => 'avif', 'image/gif' => 'gif',
        ];

        $type = strtolower(trim(explode(';', (string) $contentType)[0]));
        if (isset($map[$type])) {
            return $map[$type];
        }

        $fromUrl = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($fromUrl, ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'], true)
            ? ($fromUrl === 'jpeg' ? 'jpg' : $fromUrl)
            : 'jpg';
    }

    /**
     * Honorarios de JJ + gestoria. Devuelve null si no viene ninguno de los dos,
     * para que array_filter lo descarte y no pise un valor ya guardado.
     */
    private function sumProfessionalFees(array $costes): ?float
    {
        $honorarios = $costes['honorarios'] ?? null;
        $gestoria = $costes['gestoria'] ?? null;

        if ($honorarios === null && $gestoria === null) {
            return null;
        }

        return (float) $honorarios + (float) $gestoria;
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

    /**
     * Coordenadas del anuncio: lat/lng explícitos del JSON o geocoding de la
     * ciudad como fallback. Devuelve null si no hay nada resoluble.
     *
     * @param  array<string, mixed>  $ad
     * @return array{lat: float, lng: float}|null
     */
    private function resolveCoords(array $ad): ?array
    {
        $lat = isset($ad['lat']) && is_numeric($ad['lat']) ? (float) $ad['lat'] : null;
        $lng = isset($ad['lng']) && is_numeric($ad['lng']) ? (float) $ad['lng'] : null;

        if ($lat !== null && $lng !== null && ($lat !== 0.0 || $lng !== 0.0)) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        $city = trim((string) ($ad['ciudad'] ?? ''));
        if ($city === '') {
            return null;
        }

        return $this->geoCoder->geocodeCity($city);
    }

    private function formatSeller(array $ad): ?string
    {
        $name = $ad['vendedor_nombre'] ?? null;
        $type = $ad['vendedor_tipo'] ?? null;
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
                'finding' => $data['hallazgo'] ?? $data['finding'] ?? null,
                'source' => $data['fuente'] ?? $data['source'] ?? null,
                'rating' => $this->translate($data['valoracion'] ?? null, self::RATING_MAP),
                'date' => $data['fecha'] ?? $data['date'] ?? null,
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
                'text' => $item['texto'] ?? $item['text'] ?? '',
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
                'title' => $c['titulo'] ?? $c['t'] ?? null,
                'price' => $c['precio'] ?? $c['p'] ?? null,
                'km' => $c['km'] ?? null,
                'url' => $c['url'] ?? $c['u'] ?? null,
                'country' => $c['pais'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return $out;
    }

    private function buildNotes(array $v, array $c, array $vd, array $avisos, array $fuentes = [], array $a = []): string
    {
        $lines = [];

        // §anuncio (15-ago-2026) — datos extra del anuncio que no tienen columna propia
        if (isset($a['precio_publicado']) && $a['precio_publicado'] !== null && $a['precio_publicado'] !== '') {
            $lines[] = 'Published price: '.$a['precio_publicado'].' €';
        }
        if (isset($a['precio_negociado']) && $a['precio_negociado'] !== null && $a['precio_negociado'] !== '') {
            $lines[] = 'Negotiated price: '.$a['precio_negociado'].' €';
        }
        if (isset($a['dias_publicado']) && $a['dias_publicado'] !== null && $a['dias_publicado'] !== '') {
            $lines[] = 'Days listed: '.$a['dias_publicado'];
        }
        if (! empty($a['tuv_vigente_hasta'])) {
            $lines[] = 'TÜV/HU valid until: '.$a['tuv_vigente_hasta'];
        }
        if (! empty($v['carroceria'])) {
            $lines[] = 'Body: '.$v['carroceria'];
        }
        if (! empty($v['color_interior'])) {
            $lines[] = 'Interior color: '.$v['color_interior'];
        }

        if (! empty($v['garantia'])) {
            $lines[] = 'Warranty: '.$v['garantia'];
        }
        if (! empty($v['accidentes_declarados'])) {
            $lines[] = 'Accidents (declared): '.$v['accidentes_declarados'];
        }
        if (! empty($v['historial_mantenimiento'])) {
            $lines[] = 'Maintenance: '.$v['historial_mantenimiento'];
        }
        if (! empty($c['iedmt_es_estimacion'])) {
            $lines[] = 'IEDMT is an estimate — Hacienda calculates on its official tables.';
        }
        if (! empty($vd['fecha'])) {
            $lines[] = 'Verdict date: '.$vd['fecha'];
        }
        if (! empty($avisos)) {
            $lines[] = '';
            $lines[] = 'Avisos:';
            foreach ($avisos as $a) {
                $lines[] = '• '.$a;
            }
        }
        if (! empty($fuentes)) {
            $lines[] = '';
            $lines[] = 'Fuentes consultadas:';
            foreach ($fuentes as $f) {
                if (! is_array($f)) {
                    continue;
                }
                $aspecto = $f['aspecto'] ?? '';
                $titulo = $f['titulo'] ?? '';
                $url = $f['url'] ?? '';
                $label = trim($titulo !== '' ? $titulo : $aspecto);
                $lines[] = '• ['.$aspecto.'] '.$label.($url ? ' — '.$url : '');
            }
        }

        return implode("\n", $lines);
    }
}
