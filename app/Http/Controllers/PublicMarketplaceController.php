<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\MarketplaceVisit;
use App\Models\Organization;
use App\Support\CostBreakdown;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
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
     */
    private function publicRequestUrl(?Organization $organization = null): ?string
    {
        $org = $organization
            ?? Organization::query()->where('is_public', true)->orderBy('id')->first();

        return $org?->public_url;
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

        // CTA "Enviar solicitud" → apunta a la org del primer coche
        // (todas las fichas son de la misma org pública en la práctica).
        $firstCar = $cars->first();
        $requestUrl = $this->publicRequestUrl(
            $firstCar?->relationLoaded('organization') ? $firstCar->organization : null
        );

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        // Opciones de filtro cacheadas (invalidadas por CarObserver al cambiar marketplace)
        $filterOptions = Cache::remember('marketplace.filter_options', 1800, function () {
            $base = fn () => Car::query()->publicMarketplace();

            return [
                'brands' => $base()->distinct()->orderBy('brand')->pluck('brand')->values(),
                'fuels' => $base()->whereNotNull('fuel')->distinct()->orderBy('fuel')->pluck('fuel')->values(),
                'transmissions' => $base()->whereNotNull('transmission')->distinct()->orderBy('transmission')->pluck('transmission')->values(),
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
            ->get();

        $firstCar = $cars->first();
        $requestUrl = $this->publicRequestUrl(
            $firstCar?->relationLoaded('organization') ? $firstCar->organization : null
        );

        return Inertia::render('Public/MarketplaceCompare', [
            'cars' => $cars,
            'requestedIds' => $ids->all(),
            'requestUrl' => $requestUrl,
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
            'requestUrl' => $this->publicRequestUrl($car->organization),
            'publicProfileUrl' => $car->public_profile_url,
            'relatedCars' => $this->relatedCars($car),
            'isSharedProfile' => false,
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
     * Coches relacionados: misma marca, distinto id, mismo estado de visibilidad.
     * Usado en la ficha para sugerir alternativas sin salir del marketplace.
     */
    private function relatedCars(Car $car): Collection
    {
        return Car::query()
            ->publicMarketplace()
            ->where('brand', $car->brand)
            ->whereKeyNot($car->getKey())
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->with('photos')
            ->get();
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
            'requestUrl' => $this->publicRequestUrl($car->organization),
            'relatedCars' => $this->relatedCars($car),
            'isSharedProfile' => true,
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
     *  - cons: contras, el cliente solo ve lo bueno
     *  - notes: notas internas del operador
     *  - red_flags, research, tips, valuation internals
     *  - busquedas_realizadas, ai_analysis_json, etc.
     *
     * Devuelve un array asociativo (no el modelo) para que Inertia no incluya
     * accidentalmente campos que se hayan añadido al fillable en el futuro.
     *
     * @return array<string, mixed>
     */
    private function publicCarPayload(Car $car): array
    {
        // Campos que SÍ pueden llegar al cliente final. El verdict y el
        // traffic_light son texto admin-interno ("Comprar con confianza
        // baja (cobertura parcial 2/7)", "Dudoso: kilometraje muy alto
        // para un R de 2024", etc.) y NO deben filtrarse a la web
        // pública: las vistas admin (Cars/Index, Dashboard) los siguen
        // viendo directamente sobre el modelo. (ver #2)
        $attrs = $car->only([
            'id', 'brand', 'model', 'version', 'year', 'mileage', 'fuel',
            'transmission', 'drivetrain', 'cv', 'displacement', 'co2',
            'consumption', 'owners', 'doors', 'seats', 'euro_norm', 'color',
            'itv_date', 'pais_origen', 'purchase_price', 'new_price',
            'transport', 'itv_fee', 'coc_fee', 'dgt_fees', 'professional_fees',
            'deposit', 'vin', 'seller', 'city', 'lat', 'lng', 'status',
            'description', 'equipment', 'pros', 'is_marketplace',
            'marketplace_views',
            'market_avg', 'market_min', 'market_max', 'estimated_saving',
            'comparables_list', 'expected_delivery_date', 'tracking_token',
            'tracking_shared_at', 'schema_version',
        ]);

        // Fotos y org se cargan aparte.
        $attrs['photos'] = $car->photos;
        $attrs['organization'] = $car->organization;

        return $attrs;
    }
}
