<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\MarketplaceVisit;
use App\Models\Organization;
use App\Support\CostBreakdown;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
    /**
     * Número de WhatsApp (solo dígitos, sin +) para CTAs públicos.
     * Coincide con el que usan PublicTrackingController y las vistas
     * del marketplace. Centralizado aquí para no duplicarlo en cada
     * componente Vue.
     */
    private const WHATSAPP_NUMBER = '34675701439';

    /**
     * Opciones de ordenación permitidas para el listado público.
     * Cada valor es [columna, dirección]; 'recent' usa created_at + id
     * para tener un orden determinista en imports en lote.
     */
    private const SORT_OPTIONS = [
        'recent' => [['created_at', 'desc'], ['id', 'desc']],
        'oldest' => [['created_at', 'asc'], ['id', 'asc']],
        'price_asc' => [['purchase_price', 'asc'], ['id', 'asc']],
        'price_desc' => [['purchase_price', 'desc'], ['id', 'desc']],
        'mileage_asc' => [['mileage', 'asc'], ['id', 'asc']],
        'mileage_desc' => [['mileage', 'desc'], ['id', 'desc']],
    ];

    /**
     * Resolves the public request-form URL for the marketplace CTA
     * ("Enviar solicitud"). Falls back to the first public org
     * when no car organization can be determined.
     *
     * Si se pasa `$query` con un car_id, lo añade como `?car=ID` para que
     * el formulario sepa que viene de una ficha concreta y pueda
     * pre-rellenar la marca/modelo. (ver #3)
     *
     * @param  array<string,mixed>  $query
     */
    private function publicRequestUrl(?Organization $organization = null, array $query = []): ?string
    {
        $org = $organization
            ?? Organization::query()->where('is_public', true)->orderBy('id')->first();

        $url = $org?->public_url;
        if (! $url) {
            return null;
        }
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $url;
    }

    /**
     * Show a paginated list of publicly available cars.
     *
     * GET /marketplace
     */
    public function index(Request $request): Response
    {
        // Contador global de visitas (excluye bots para no inflar la metrica).
        // Es 1 UPDATE por visita a la home publica, aceptable para el volumen
        // esperado; si crece mucho se mueve a Cache::increment + flush periodico.
        $visitsCount = 0;
        if (! $this->looksLikeBot($request->userAgent())) {
            $visitsCount = MarketplaceVisit::recordView();
        } else {
            $visitsCount = (int) MarketplaceVisit::current()->visits_count;
        }

        $sort = $request->input('sort', 'recent');
        if (! array_key_exists($sort, self::SORT_OPTIONS)) {
            $sort = 'recent';
        }

        $deal = filter_var($request->input('deal'), FILTER_VALIDATE_BOOLEAN);

        $cars = Car::query()
            ->publicMarketplace()
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('brand', 'like', "%$s%")
                        ->orWhere('model', 'like', "%$s%")
                        ->orWhere('vin', 'like', "%$s%");
                });
            })
            ->when($request->input('brand'), fn ($q, $b) => $q->where('brand', $b))
            ->when($request->input('verdict'), fn ($q, $v) => $q->where('verdict', $v))
            ->when($request->input('traffic_light'), fn ($q, $tl) => $q->where('traffic_light', $tl))
            ->when($request->input('min_price'), fn ($q, $p) => $q->where('purchase_price', '>=', $p))
            ->when($request->input('max_price'), fn ($q, $p) => $q->where('purchase_price', '<=', $p))
            ->when($request->input('mileage'), fn ($q, $m) => $q->where('mileage', '<=', $m))
            ->when($request->input('fuel'), fn ($q, $f) => $q->where('fuel', $f))
            ->when($request->input('transmission'), fn ($q, $tr) => $q->where('transmission', $tr))
            ->when($request->input('doors') !== null && $request->input('doors') !== '', fn ($q, $d) => $q->where('doors', $request->input('doors')))
            ->when($request->input('color'), fn ($q, $c) => $q->where('color', $c))
            ->when($request->input('year_min'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) >= ?', [$y]))
            ->when($request->input('year_max'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) <= ?', [$y]))
            ->when($deal, fn ($q) => $q->where('estimated_saving', '>', 0));
        foreach (self::SORT_OPTIONS[$sort] as [$column, $direction]) {
            $cars->orderBy($column, $direction);
        }
        $cars = $cars->paginate(12)->withQueryString();

        // Load photos and organization for each car
        $cars->load(['photos', 'organization']);

        // Filtrar campos admin (verdict, traffic_light, etc.) en cada
        // coche antes de exponerlos vía Inertia. Sin esto, los modelos
        // Eloquent serializan TODOS los $fillable (incluyendo verdict
        // y verdict_changes) al cliente. (ver #2)
        $cars->setCollection(
            $cars->getCollection()->map(fn (Car $c) => $this->publicCarPayload($c))
        );

        // CTA "Enviar solicitud" → apunta a la org del primer coche
        // (todas las fichas son de la misma org pública en la práctica).
        $firstCar = $cars->first();
        $firstCarOrg = is_array($firstCar) ? ($firstCar['organization'] ?? null) : $firstCar?->organization;
        $requestUrl = $this->publicRequestUrl($firstCarOrg);

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        // Opciones de filtro cacheadas (invalidadas por CarObserver al cambiar marketplace)
        $filterOptions = Cache::remember('marketplace.filter_options.v3', 1800, function () {
            $base = fn () => Car::query()->publicMarketplace();

            return [
                'brands' => $base()->distinct()->orderBy('brand')->pluck('brand')->values(),
                'fuels' => $base()->whereNotNull('fuel')
                    ->whereIn('fuel', ['Diesel', 'Gasoline', 'Hybrid', 'Electric', 'diesel', 'gasoline', 'hybrid', 'electric'])
                    ->distinct()
                    ->orderBy('fuel')
                    ->pluck('fuel')
                    ->values(),
                // Solo los valores canonicos para el filtro. Los strings
                // descriptivos largos (ej. "Automatico DSG de 7 marchas con
                // levas") se ven en la ficha del coche, no como opcion de
                // filtro — el cliente no busca por tipo de DSG.
                'transmissions' => $base()->whereNotNull('transmission')
                    ->whereIn('transmission', ['manual', 'automatic'])
                    ->distinct()
                    ->orderBy('transmission')
                    ->pluck('transmission')
                    ->values(),
                'doors' => $base()->whereNotNull('doors')->distinct()->orderBy('doors')->pluck('doors')->values(),
                'colors' => $base()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color')->values(),
            ];
        });

        // Bounds para los inputs de precio/año (usados por el frontend en
        // los atributos min/max de los <input type="number">).
        $filterBounds = Cache::remember('marketplace.filter_bounds', 1800, function () {
            $base = Car::query()->publicMarketplace();

            return [
                'price' => [
                    'min' => (int) ($base->min('purchase_price') ?? 0),
                    'max' => (int) ($base->max('purchase_price') ?? 9999999),
                ],
                'year' => [
                    'min' => 1990,
                    'max' => (int) date('Y') + 1,
                ],
            ];
        });

        return Inertia::render('Public/MarketplaceIndex', [
            'cars' => Inertia::defer(fn () => $cars),
            'verdicts' => $verdicts,
            'lights' => $lights,
            'filterOptions' => $filterOptions,
            'filterBounds' => $filterBounds,
            'requestUrl' => $requestUrl,
            'sort' => $sort,
            'sortOptions' => array_keys(self::SORT_OPTIONS),
            'filters' => $request->only([
                'search', 'verdict', 'traffic_light', 'min_price', 'max_price',
                'year_min', 'year_max', 'brand', 'deal', 'mileage', 'fuel',
                'transmission', 'doors', 'color',
            ]),
            'visitsCount' => $visitsCount,
            'whatsappNumber' => self::WHATSAPP_NUMBER,
        ]);
    }

    /**
     * Compare up to 4 marketplace cars side by side.
     *
     * GET /marketplace/compare?ids=1,2,3
     */
    public function compare(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        $cars = Car::query()
            ->publicMarketplace()
            ->whereIn('id', $ids->all())
            ->with(['photos', 'organization'])
            ->get()
            ->map(fn (Car $c) => $this->publicCarPayload($c));

        // El primer coche ya viene como array (publicCarPayload), pero la
        // org esta en su clave 'organization' (modelo serializado).
        $firstCar = $cars->first();
        $firstCarOrg = is_array($firstCar) ? ($firstCar['organization'] ?? null) : $firstCar?->organization;
        $requestUrl = $this->publicRequestUrl($firstCarOrg);

        return Inertia::render('Public/MarketplaceCompare', [
            'cars' => $cars,
            'requestedIds' => $ids->all(),
            'requestUrl' => $requestUrl,
            'whatsappNumber' => self::WHATSAPP_NUMBER,
        ]);
    }

    /**
     * Show a single car from the marketplace.
     *
     * GET /marketplace/{car}
     */
    public function show(Car $car): Response
    {
        // Verificar visibilidad pública con el MISMO criterio que el listado
        // (scope único en Car — antes estaba copiado aquí y se desincronizó).
        $visible = Car::query()->publicMarketplace()->whereKey($car->getKey())->exists();
        if (! $visible) {
            abort(404);
        }

        $car->increment('marketplace_views');

        $car->load(['photos', 'organization']);

        // Pre-compute derived data for the enriched valuation UI
        $car->researchGaps;       // touch accessor
        $car->comparablesStats;    // touch accessor
        $car->calculateTotalCost(); // touch method

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $this->publicCarPayload($car),
            'requestUrl' => $this->publicRequestUrl($car->organization, ['car' => $car->id]),
            'publicProfileUrl' => $car->public_profile_url,
            'dossierUrl' => $car->dossier_url,
            'relatedCars' => $this->relatedCars($car),
            'isSharedProfile' => false,
            'whatsappNumber' => self::WHATSAPP_NUMBER,
            'derived' => [
                'total_cost' => $car->calculateTotalCost(),
                'iedmt' => $car->calculateIEDMT(),
                'research_gaps' => $car->researchGaps,
                'comparables_stats' => $car->comparablesStats,
            ],
            'costBreakdown' => CostBreakdown::forCar($car, (float) $car->calculateIEDMT()),
        ]);
    }

    /**
     * Coches relacionados: misma marca y modelo si hay >=2, si no fallback
     * a misma marca con rango de precio +-40%. Antes filtraba solo por
     * marca, lo que metia un Golf R 333 CV junto a un Golf 1.0 TSI basico
     * — confunde al cliente.
     */
    private function relatedCars(Car $car): Collection
    {
        $sameModel = Car::query()
            ->publicMarketplace()
            ->where('brand', $car->brand)
            ->where('model', $car->model)
            ->whereKeyNot($car->getKey())
            ->get();

        if ($sameModel->count() >= 2) {
            return $sameModel
                ->sortBy(fn (Car $c) => abs((float) $c->purchase_price - (float) $car->purchase_price))
                ->take(4)
                ->map(fn (Car $c) => $this->publicCarPayload($c))
                ->values();
        }

        $minPrice = (float) $car->purchase_price * 0.6;
        $maxPrice = (float) $car->purchase_price * 1.4;

        return Car::query()
            ->publicMarketplace()
            ->where('brand', $car->brand)
            ->whereKeyNot($car->getKey())
            ->when((float) $car->purchase_price > 0, fn ($q) => $q->whereBetween('purchase_price', [$minPrice, $maxPrice]))
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->with('photos')
            ->get()
            ->map(fn (Car $c) => $this->publicCarPayload($c));
    }

    /**
     * Perfil público de marketing accesible por token.
     *
     * URL: GET /p/{token}
     *
     * Es la misma ficha visual que /marketplace/{id} pero:
     *  - No expone el id numérico del coche (más seguro de compartir).
     *  - No incrementa marketplace_views (es una métrica del listado, no
     *    de la ficha compartible).
     *  - Se contabiliza en CarPublicLink.views_count (link de share).
     *
     * Si el token es inválido/revocado/expirado o el coche ya no es
     * público (is_marketplace=false, status Delivered/Discarded) → 404.
     */
    public function showPublicProfile(string $token): Response
    {
        $link = CarPublicLink::where('token', $token)->first();
        if (! $link || ! $link->isActive()) {
            abort(404);
        }

        $car = $link->car()->first();
        if (! $car) {
            abort(404);
        }

        // Reutilizamos la misma verificación de visibilidad que show()
        // (scope único en el modelo, ya documentado).
        $visible = Car::query()->publicMarketplace()->whereKey($car->getKey())->exists();
        if (! $visible) {
            abort(404);
        }

        // Tracking de la vista del link de share (no bloquea si falla).
        try {
            $link->recordView();
        } catch (\Throwable $e) {
            // No interrumpimos la carga por un fallo de tracking.
        }

        $car->load(['photos', 'organization']);

        $car->researchGaps;       // touch accessor
        $car->comparablesStats;    // touch accessor
        $car->calculateTotalCost(); // touch method

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $this->publicCarPayload($car),
            'publicProfileUrl' => $link->publicProfileUrl(),
            'dossierUrl' => $link->publicUrl(),
            'requestUrl' => $this->publicRequestUrl($car->organization, ['car' => $car->id]),
            'relatedCars' => $this->relatedCars($car),
            'isSharedProfile' => true,
            'whatsappNumber' => self::WHATSAPP_NUMBER,
            'derived' => [
                'total_cost' => $car->calculateTotalCost(),
                'iedmt' => $car->calculateIEDMT(),
                'research_gaps' => $car->researchGaps,
                'comparables_stats' => $car->comparablesStats,
            ],
            'costBreakdown' => CostBreakdown::forCar($car, (float) $car->calculateIEDMT()),
        ]);
    }

    /**
     * Deteccion basica de bots para no inflar el contador del marketplace.
     * Mismo patron que PublicTrackingController::looksLikeBot.
     */
    private function looksLikeBot(?string $ua): bool
    {
        if (! $ua) {
            return false;
        }

        return (bool) preg_match('/bot|crawl|spider|slurp|curl|wget|python-requests|headless/i', $ua);
    }

    /**
     * Filtra el Car para excluir campos internos que NO deben llegar al
     * cliente (decision 12-oct-2026):
     *  - verdict, verdict_at, verdict_confidence: el verdict almacenado en
     *    BD ya viene como texto admin-interno ("Comprar con confianza
     *    baja (cobertura parcial 2/7)", "Dudoso: kilometraje muy alto
     *    para un R de 2024", "Descartar por riesgo", etc.) — filtrar.
     *  - verdict_reasoning / verdict_changes: valoracion interna del equipo
     *  - traffic_light: señal admin ("neutral", "amber"...), no aporta al cliente
     *  - notes: notas internas del operador
     *  - red_flags, research, tips, valuation internals
     *  - busquedas_realizadas, ai_analysis_json, etc.
     *
     * NOTA: `cons` (contras/puntos debiles del coche) SÍ se envia al
     * cliente. La politica de "el cliente solo ve lo bueno" cambio:
     * 13-oct-2026 el usuario decidio que la ficha publica debe mostrar
     * lo bueno Y lo malo. El cliente es adulto y decide con informacion
     * completa. Los admins siguen viendo TODO (verdict, red_flags, etc.)
     * en Cars/Index. (ver #4)
     *
     * Devuelve un array asociativo (no el modelo) para que Inertia no incluya
     * accidentalmente campos que se hayan añadido al fillable en el futuro.
     *
     * @return array<string, mixed>
     */
    private function publicCarPayload(Car $car): array
    {
        // Campos que SÍ pueden llegar al cliente final. Se filtran los
        // sensibles (decision 12-oct-2026):
        //  - verdict, verdict_at, verdict_confidence: texto admin-interno
        //    ("Comprar con confianza baja (cobertura parcial 2/7)", etc.)
        //  - verdict_reasoning, verdict_changes: valoracion interna
        //  - traffic_light: semaforo admin
        //  - notes, red_flags, research, tips: info admin
        //  - vin, lat, lng: el VIN NO debe ser publico, las coords son
        //    para el admin y la ficha de tracking (privada)
        //  - busquedas_realizadas, ai_analysis_json: debug
        // Las vistas admin (Cars/Index, Dashboard, Tracking) acceden
        // al modelo completo directamente y siguen viendo todo.
        $attrs = $car->only([
            'id', 'brand', 'model', 'version', 'year', 'mileage', 'fuel',
            'transmission', 'drivetrain', 'cv', 'displacement',
            'consumption', 'owners', 'euro_norm',
            'itv_date', 'pais_origen', 'purchase_price', 'new_price',
            'transport', 'itv_fee', 'coc_fee', 'dgt_fees', 'professional_fees',
            'deposit', 'city', 'status',
            'description', 'equipment', 'pros', 'cons', 'is_marketplace',
            'marketplace_views', 'marketplace_checked_at', 'marketplace_unavailable_reason',
            'market_avg', 'market_min', 'market_max', 'estimated_saving',
            'expected_delivery_date',
        ]);

        // Fotos y org se cargan aparte (necesarios para el render).
        $attrs['photos'] = $car->photos;
        $attrs['organization'] = $car->organization;

        return $attrs;
    }
}
