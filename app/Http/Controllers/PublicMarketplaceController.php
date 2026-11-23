<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\MarketplaceVisit;
use App\Models\Organization;
use App\Support\Brand;
use App\Support\CostBreakdown;
use App\Support\FichaPublica;
use App\Support\PublicSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
    /** Número de WhatsApp (solo dígitos, con prefijo) de la marca dada. */
    private static function whatsappDigits(Brand $brand): string
    {
        $digits = preg_replace('/\D/', '', (string) ($brand->get('whatsapp') ?: $brand->get('phone')));

        return strlen($digits) === 9 ? '34'.$digits : $digits;
    }

    /**
     * Ahorro minimo (EUR) para que un coche cuente como "muy buen precio"
     * en el filtro rapido del marketplace. Antes bastaba con ahorro > 0,
     * que metia coches con 50 EUR de ahorro — el filtro no significaba
     * nada. 1.500 EUR es un ahorro que el cliente percibe de verdad.
     */
    private const DEAL_MIN_SAVING = 1500;

    /**
     * Claves de cache del inventario publico del marketplace.
     *
     * Son constantes (y no literales sueltos) para que:
     *  - los tests las referencien en vez de duplicar el string,
     *  - al cambiar el CONTENIDO cacheado baste subir el sufijo de version
     *    (`_V4` -> `_V5`) sin tener que esperar los 30 min de TTL ni tocar
     *    los tests, y CarObserver invalide siempre la clave correcta.
     */
    public const CACHE_KEY_FILTER_OPTIONS = 'marketplace.filter_options.v6';

    public const CACHE_KEY_FILTER_COUNTS = 'marketplace.filter_counts.v6';

    public const CACHE_KEY_FILTER_BOUNDS = 'marketplace.filter_bounds.v6';

    /** TTL de las caches de filtros (segundos). */
    private const FILTER_CACHE_TTL = 1800;

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
     * Clave de caché de filtros de una web: la de la plataforma conserva la
     * clave histórica; cada empresa tiene la suya.
     */
    public static function filterCacheKey(string $key, ?int $organizationId, bool $isPlatform = false): string
    {
        return $isPlatform || $organizationId === null ? $key : $key.'.org'.$organizationId;
    }

    /** Olvida las cachés de filtros de la web de una empresa y la de la plataforma. */
    public static function forgetFilterCaches(?int $organizationId): void
    {
        foreach ([self::CACHE_KEY_FILTER_OPTIONS, self::CACHE_KEY_FILTER_COUNTS, self::CACHE_KEY_FILTER_BOUNDS] as $key) {
            Cache::forget($key);
            if ($organizationId !== null) {
                Cache::forget(self::filterCacheKey($key, $organizationId));
            }
        }
    }

    /** Coches publicados en la web de una empresa (solo los suyos). */
    private function siteCars(PublicSite $site): Builder
    {
        return Car::query()
            ->publicMarketplace()
            ->where('cars.organization_id', $site->organizationId());
    }

    private function resolveSite(string $slug): PublicSite
    {
        $site = PublicSite::forSlug($slug);
        abort_if($site === null, 404);

        return $site;
    }

    /**
     * Props comunes de la web: marca, contacto y URLs de la empresa.
     *
     * @return array<string, mixed>
     */
    private function siteProps(PublicSite $site): array
    {
        $brand = $site->brand();

        return [
            'marketplace' => $site->context(),
            'orgBrand' => $brand->toPublicArray(),
            'whatsappNumber' => self::whatsappDigits($brand),
        ];
    }

    /**
     * Show a paginated list of publicly available cars.
     *
     * GET /marketplace (web de la plataforma) · GET /web/{slug} (web de una empresa)
     */
    public function index(Request $request): Response
    {
        return $this->renderIndex($request, PublicSite::platform());
    }

    public function siteIndex(Request $request, string $site): Response|RedirectResponse
    {
        $resolved = $this->resolveSite($site);
        if ($resolved->isPlatform) {
            return redirect()->route('marketplace.index', $request->query(), 301);
        }

        return $this->renderIndex($request, $resolved);
    }

    private function renderIndex(Request $request, PublicSite $site): Response
    {
        // Contador global de visitas (excluye bots para no inflar la metrica).
        // Es 1 UPDATE por visita a la home publica, aceptable para el volumen
        // esperado; si crece mucho se mueve a Cache::increment + flush periodico.
        $isBot = $this->looksLikeBot($request->userAgent());
        if ($site->isPlatform || ! $site->organization) {
            $visitsCount = $isBot ? (int) MarketplaceVisit::current()->visits_count : MarketplaceVisit::recordView();
        } else {
            $visitsCount = $isBot ? (int) $site->organization->web_visits : $site->organization->recordWebVisit();
        }

        $sort = $request->input('sort', 'recent');
        if (! array_key_exists($sort, self::SORT_OPTIONS)) {
            $sort = 'recent';
        }

        $deal = filter_var($request->input('deal'), FILTER_VALIDATE_BOOLEAN);

        $cars = $this->siteCars($site)
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
            // El filtro de color recibe la paleta base ("Negro") y debe
            // encontrar las variantes comerciales ("Negro Deep Black
            // nacarado"), asi que matchea por prefijo, no por igualdad.
            ->when($request->input('color'), fn ($q, $c) => $q->where('color', 'like', $c.'%'))
            ->when($request->input('year_min'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) >= ?', [$y]))
            ->when($request->input('year_max'), fn ($q, $y) => $q->whereRaw('SUBSTRING(year, -4) <= ?', [$y]))
            // "Muy buen precio": el campo `estimated_saving` a veces esta
            // en 0/null aunque la ficha del coche diga "Ahorra 5.000 EUR"
            // (el ahorro nominal se calcula despues y se escribe en el
            // bullet de pros, no en la columna). Para no dejar fuera esos
            // coches, el filtro es la UNION de tres senales (cualquiera
            // basta para entrar como oferta):
            //  a) estimated_saving >= 1500 (campo estructurado)
            //  b) purchase_price <= market_avg - 1500 (al menos 1.500 EUR
            //     por debajo del precio medio del mercado)
            //  c) purchase_price <= market_min - 1500 (al menos 1.500 EUR
            //     por debajo del precio minimo del mercado — el caso real
            //     del Golf 70 en produccion, donde market_avg esta NULL
            //     pero market_min = 42.900 EUR y el coche esta a 33.999)
            // Asi el filtro es estable aunque los campos estructurados esten
            // vacios. (ver #7)
            ->when($deal, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('estimated_saving', '>=', self::DEAL_MIN_SAVING)
                        ->orWhereRaw('(market_avg IS NOT NULL AND market_avg > 0 AND purchase_price <= (market_avg - ?))', [self::DEAL_MIN_SAVING])
                        ->orWhereRaw('(market_min IS NOT NULL AND market_min > 0 AND purchase_price <= (market_min - ?))', [self::DEAL_MIN_SAVING]);
                });
            });
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

        $requestUrl = $site->requestUrl();
        $orgId = $site->organizationId();

        $verdicts = Car::VERDICTS;
        $lights = ['green', 'amber', 'red', 'neutral'];

        // Opciones de filtro cacheadas (invalidadas por CarObserver al cambiar marketplace)
        $filterOptions = Cache::remember(self::filterCacheKey(self::CACHE_KEY_FILTER_OPTIONS, $orgId, $site->isPlatform), self::FILTER_CACHE_TTL, function () use ($site) {
            $base = fn () => $this->siteCars($site);

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
                // Colores normalizados a la paleta base (Negro, Blanco,
                // Azul...). La BD guarda el nombre comercial completo
                // ("Azul Lapislazuli metalizado (Lapis-Blau)"), inservible
                // como opcion de filtro.
                'colors' => $base()
                    ->whereNotNull('color')
                    ->pluck('color')
                    ->map(fn (string $c) => Car::baseColor($c))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
            ];
        });

        // Contadores para la UI de filtros: cuantos coches hay por marca y
        // cuantos entran en "Muy buen precio". Se muestran en los chips
        // para que el cliente vea de un vistazo donde hay stock (antes el
        // chip de marca no tenia numero y el boton de oferta parecia vacio).
        $filterCounts = Cache::remember(self::filterCacheKey(self::CACHE_KEY_FILTER_COUNTS, $orgId, $site->isPlatform), self::FILTER_CACHE_TTL, function () use ($site) {
            $base = fn () => $this->siteCars($site);

            return [
                'brands' => $base()
                    ->selectRaw('brand, COUNT(*) as total')
                    ->groupBy('brand')
                    ->orderByDesc('total')
                    ->pluck('total', 'brand')
                    ->all(),
                // Ver la nota de `index()` sobre los tres senales.
                'deal' => $base()
                    ->where(function ($q) {
                        $q->where('estimated_saving', '>=', self::DEAL_MIN_SAVING)
                            ->orWhereRaw('(market_avg IS NOT NULL AND market_avg > 0 AND purchase_price <= (market_avg - ?))', [self::DEAL_MIN_SAVING])
                            ->orWhereRaw('(market_min IS NOT NULL AND market_min > 0 AND purchase_price <= (market_min - ?))', [self::DEAL_MIN_SAVING]);
                    })
                    ->count(),
                'total' => $base()->count(),
            ];
        });

        // Bounds para los inputs de precio/año (usados por el frontend en
        // los atributos min/max de los <input type="number">).
        $filterBounds = Cache::remember(self::filterCacheKey(self::CACHE_KEY_FILTER_BOUNDS, $orgId, $site->isPlatform), self::FILTER_CACHE_TTL, function () use ($site) {
            $base = $this->siteCars($site);

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
            'filterCounts' => $filterCounts,
            'requestUrl' => $requestUrl,
            'sort' => $sort,
            'sortOptions' => array_keys(self::SORT_OPTIONS),
            'filters' => $request->only([
                'search', 'verdict', 'traffic_light', 'min_price', 'max_price',
                'year_min', 'year_max', 'brand', 'deal', 'mileage', 'fuel',
                'transmission', 'doors', 'color',
            ]),
            'visitsCount' => $visitsCount,
            ...$this->siteProps($site),
        ])->withViewData(['meta' => $this->siteMeta(
            $site,
            'Coches localizados',
            'Coches localizados con su total aproximado puesto en destino. Gestionamos la compra por ti, con honorarios fijos.',
            $site->homeUrl(),
        )]);
    }

    /**
     * Compare up to 4 marketplace cars side by side.
     *
     * GET /marketplace/compare?ids=1,2,3
     */
    public function compare(Request $request): Response
    {
        return $this->renderCompare($request, PublicSite::platform());
    }

    public function siteCompare(Request $request, string $site): Response|RedirectResponse
    {
        $resolved = $this->resolveSite($site);
        if ($resolved->isPlatform) {
            return redirect()->route('marketplace.compare', $request->query(), 301);
        }

        return $this->renderCompare($request, $resolved);
    }

    private function renderCompare(Request $request, PublicSite $site): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        $cars = $this->siteCars($site)
            ->whereIn('id', $ids->all())
            ->with(['photos', 'organization'])
            ->get();

        $cars = $cars->map(fn (Car $c) => $this->publicCarPayload($c));

        return Inertia::render('Public/MarketplaceCompare', [
            'cars' => $cars,
            'requestedIds' => $ids->all(),
            'requestUrl' => $site->requestUrl(),
            ...$this->siteProps($site),
        ])->withViewData(['meta' => $this->siteMeta(
            $site,
            'Comparar coches',
            'Compara los coches localizados con su total aproximado puesto en destino.',
            $request->fullUrl(),
        )]);
    }

    /**
     * Show a single car from the marketplace.
     *
     * GET /marketplace/{car}
     */
    public function show(Car $car): Response|RedirectResponse
    {
        $site = PublicSite::platform();

        // Las fichas de otras empresas viven en su propia web: los enlaces
        // antiguos a /marketplace/{id} se redirigen allí.
        if ((int) $car->organization_id !== (int) $site->organizationId()) {
            $owner = $car->organization;
            abort_unless($owner && PublicSite::isAvailable($owner), 404);

            return redirect()->to(PublicSite::forOrganization($owner)->carUrl($car), 301);
        }

        return $this->renderShow($car, $site);
    }

    public function siteShow(string $site, Car $car): Response|RedirectResponse
    {
        $resolved = $this->resolveSite($site);
        abort_unless((int) $car->organization_id === (int) $resolved->organizationId(), 404);
        if ($resolved->isPlatform) {
            return redirect()->route('marketplace.show', ['car' => $car->getKey()], 301);
        }

        return $this->renderShow($car, $resolved);
    }

    private function renderShow(Car $car, PublicSite $site): Response
    {
        // Verificar visibilidad pública con el MISMO criterio que el listado
        // (scope único en Car — antes estaba copiado aquí y se desincronizó).
        $visible = $this->siteCars($site)->whereKey($car->getKey())->exists();
        if (! $visible) {
            abort(404);
        }

        $car->increment('marketplace_views');

        $car->load(['photos', 'organization']);

        $costBreakdown = CostBreakdown::forCar($car, (float) $car->calculateIEDMT());

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $this->publicCarPayload($car),
            'requestUrl' => $site->requestUrl(['car' => $car->id]),
            'publicProfileUrl' => $car->public_profile_url,
            'dossierUrl' => $car->dossier_url,
            'relatedCars' => $this->relatedCars($car, $site),
            'isSharedProfile' => false,
            ...$this->siteProps($site),
            ...$this->researchPayload($car),
            'costBreakdown' => $costBreakdown,
            'showMarketComparison' => CostBreakdown::marketComparisonFavoursClient($car, (float) $costBreakdown['total']),
        ])->withViewData(['meta' => $this->shareMeta($car, $costBreakdown, $site->carUrl($car), $site)]);
    }

    /**
     * Coches relacionados. Logica en cascada, de mas estricto a mas
     * permisivo (para que el cliente siempre vea al menos UN coche similar
     * cuando hay stock):
     *
     * 1. Misma marca + modelo (>=2): los "hermanos gemelos" — el caso ideal.
     * 2. Misma marca, rango de precio +-40% (>=1): modelos similares en precio.
     * 3. Misma marca sin filtro de precio: ultimo fallback. Antes este era
     *    el unico caso y mezclaba Golf R 333 CV con Golf 1.0 TSI. Ahora
     *    solo se usa si los pasos 1 y 2 no devolvieron nada (p. ej.
     *    catalogo con un solo coche por modelo).
     */
    private function relatedCars(Car $car, PublicSite $site): Collection
    {
        // 1) Misma marca + modelo
        $sameModel = $this->siteCars($site)
            ->where('brand', $car->brand)
            ->where('model', $car->model)
            ->whereKeyNot($car->getKey())
            ->get();

        if ($sameModel->count() >= 2) {
            return $sameModel
                ->sortBy(fn (Car $c) => abs((float) $c->purchase_price - (float) $car->purchase_price))
                ->take(4)
                ->map(fn (Car $c) => $this->relatedCarPayload($c))
                ->values();
        }

        // 2) Misma marca, rango de precio +-40%
        $minPrice = (float) $car->purchase_price * 0.6;
        $maxPrice = (float) $car->purchase_price * 1.4;
        $sameBrandAndPrice = $this->siteCars($site)
            ->where('brand', $car->brand)
            ->whereKeyNot($car->getKey())
            ->when((float) $car->purchase_price > 0, fn ($q) => $q->whereBetween('purchase_price', [$minPrice, $maxPrice]))
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->with('photos')
            ->get();

        if ($sameBrandAndPrice->count() >= 1) {
            return $sameBrandAndPrice->map(fn (Car $c) => $this->relatedCarPayload($c));
        }

        // 3) Misma marca sin filtro de precio (ultimo recurso)
        return $this->siteCars($site)
            ->where('brand', $car->brand)
            ->whereKeyNot($car->getKey())
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->with('photos')
            ->get()
            ->map(fn (Car $c) => $this->relatedCarPayload($c));
    }

    /**
     * Tarjeta de "Otros coches similares": el mismo allowlist publico, pero
     * con el total aproximado puesto en destino en vez del precio del
     * anuncio de origen (decision 20-nov-2026: el cliente no debe ver el
     * precio aleman suelto al lado de una ficha que muestra el total).
     *
     * @return array<string, mixed>
     */
    private function relatedCarPayload(Car $car): array
    {
        return $this->publicCarPayload($car);
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
        $visible = Car::query()->publicMarketplace()->whereKey($car->getKey())->exists()
            && $car->organization && PublicSite::isAvailable($car->organization);
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
        $site = PublicSite::forOrganization($car->organization);

        $costBreakdown = CostBreakdown::forCar($car, (float) $car->calculateIEDMT());

        return Inertia::render('Public/MarketplaceShow', [
            'car' => $this->publicCarPayload($car),
            'publicProfileUrl' => $link->publicProfileUrl(),
            'dossierUrl' => $link->publicUrl(),
            'requestUrl' => $site->requestUrl(['car' => $car->id]),
            'relatedCars' => $this->relatedCars($car, $site),
            'isSharedProfile' => true,
            ...$this->siteProps($site),
            ...$this->researchPayload($car),
            'costBreakdown' => $costBreakdown,
            'showMarketComparison' => CostBreakdown::marketComparisonFavoursClient($car, (float) $costBreakdown['total']),
        ])->withViewData(['meta' => $this->shareMeta($car, $costBreakdown, $link->publicProfileUrl(), $site)]);
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
     * Lo que la ficha publica cuenta de la investigacion de la unidad: la
     * valoracion escrita para el cliente y que se comprueba antes de la
     * reserva (mismo contenido que el dossier, via FichaPublica).
     *
     * @return array{valuation: array<int, string>, checks: array{verificado: array<int, string>, pendiente: array<int, string>}}
     */
    private function researchPayload(Car $car): array
    {
        return [
            'valuation' => FichaPublica::valoracion(FichaPublica::esqueleto($car)),
            'checks' => FichaPublica::comprobaciones(FichaPublica::fichaCliente($car)),
        ];
    }

    /**
     * Meta etiquetas para la previsualizacion del enlace (WhatsApp, Telegram,
     * Instagram...). Van en el HTML del servidor porque esos robots no
     * ejecutan JavaScript: antes el enlace salia como "Laravel" con el texto
     * generico de la plataforma y sin foto (revision 20-nov-2026). Con la web
     * por empresa, el nombre y la ciudad son los de la empresa del coche.
     *
     * @param  array{total: float}  $costBreakdown
     * @return array{title: string, description: string, image: string|null, url: string}
     */
    private function shareMeta(Car $car, array $costBreakdown, string $url, PublicSite $site): array
    {
        $brand = $site->brand();
        $titulo = trim(implode(' ', array_filter([$car->brand, $car->model, $car->version])));
        $datos = array_filter([
            $car->year,
            $car->mileage ? number_format((int) $car->mileage, 0, ',', '.').' km' : null,
        ]);
        $total = (float) ($costBreakdown['total'] ?? 0);
        $destino = $brand->get('city') ?: 'destino';

        $descripcion = ($total > 0 ? '~ '.number_format($total, 0, ',', '.').' € puesto en '.$destino.' (aprox.) · ' : '')
            .($datos ? implode(' · ', $datos).' · ' : '')
            .'Gestionamos la compra por ti';

        $foto = $car->photos->sortBy('sort_order')->first();
        $imagen = null;
        if ($foto && $foto->url) {
            $imagen = str_starts_with((string) $foto->url, 'http')
                ? (string) $foto->url
                : url('storage/'.ltrim(preg_replace('#^/?storage/#', '', (string) $foto->url), '/'));
        }

        return [
            'title' => $titulo.' · '.$brand->name(),
            'description' => $descripcion,
            'image' => $imagen,
            'url' => $url,
        ];
    }

    /**
     * Meta del listado y de la comparativa de una web, con el nombre de su
     * empresa.
     *
     * @return array{title: string, description: string, image: string, url: string}
     */
    private function siteMeta(PublicSite $site, string $title, string $description, string $url): array
    {
        $brand = $site->brand();
        $destino = $brand->get('city');

        return [
            'title' => $title.' · '.$brand->name(),
            'description' => $destino ? str_replace('puesto en destino', 'puesto en '.$destino, $description) : $description,
            'image' => $brand->get('logo_url') && ! $site->isPlatform ? (string) $brand->get('logo_url') : url('img/og-image.jpg'),
            'url' => $url,
        ];
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
     *  - cons: contras de investigacion interna. Se enviaron un momento
     *    (13-oct-2026) por una decision de "transparencia total", pero
     *    el contenido real es admin-only ("Sin VIN publicado: recalls
     *    pendientes", "Condiciones de garantia solo como declaracion
     *    del anuncio", "Color negro menos vistoso en redes") — al
     *    cliente no le aporta y ensucia la ficha publica. Vuelto a
     *    filtrar el 14-oct-2026 tras feedback del usuario. Si algun dia
     *    hace falta mostrar contras al cliente, crear un campo nuevo
     *    `cons_public` escrito PARA el cliente. (ver #6)
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
        //  - notes, red_flags, research, tips, cons: info admin
        //  - vin, lat, lng: el VIN NO debe ser publico, las coords son
        //    para el admin y la ficha de tracking (privada)
        //  - city, seller: la ciudad y el nombre del vendedor de origen
        //    identifican al vendedor (decision 20-nov-2026)
        //  - busquedas_realizadas, ai_analysis_json: debug
        // Las vistas admin (Cars/Index, Dashboard, Tracking) acceden
        // al modelo completo directamente y siguen viendo todo.
        $attrs = $car->only([
            'id', 'brand', 'model', 'version', 'year', 'mileage', 'fuel',
            'transmission', 'drivetrain', 'cv', 'displacement',
            'consumption', 'owners', 'euro_norm', 'color',
            'itv_date', 'pais_origen', 'purchase_price', 'status',
            'equipment', 'pros', 'is_marketplace', 'marketplace_views',
            'market_avg', 'market_min', 'market_max', 'estimated_saving',
        ]);

        // 20-nov-2026: los costes sueltos (transporte, tasas, honorarios,
        // fianza, precio nuevo) viajan ya sumados en `costBreakdown`; la
        // descripcion del anuncio de origen puede llevar el nombre y el
        // telefono del vendedor. Ninguno de los dos llega al navegador.

        // "Lo mejor de esta unidad" pasa por el mismo filtro que el dossier.
        $attrs['pros'] = FichaPublica::puntosAFavor($car, FichaPublica::argumentos($car));

        // Solo el tipo de vendedor (para avisar de que un particular no da
        // garantia y de que puede haber ITP), nunca su nombre ni su ciudad.
        $attrs['seller_is_private'] = $car->isFromPrivateSeller();

        // Total aproximado puesto en Huelva: lo que ve el cliente en el
        // listado, la comparativa y "Otros coches similares" (21-nov-2026).
        // El ahorro se recalcula con ese total y solo si la comparativa le
        // favorece; el campo de BD (del ZIP) puede venir de otro total.
        $total = (float) CostBreakdown::forCar($car, (float) $car->calculateIEDMT())['total'];
        $attrs['estimated_total'] = $total > 0 ? $total : null;
        $attrs['estimated_saving'] = CostBreakdown::marketComparisonFavoursClient($car, $total)
            ? round((float) $car->market_min - $total)
            : null;

        // Fotos y org se cargan aparte (necesarios para el render).
        $attrs['photos'] = $car->photos
            ->map(fn ($photo) => $photo->only(['id', 'url', 'sort_order', 'photo_type', 'description']))
            ->values();
        // Solo lo que pinta la ficha: el modelo completo llevaba el plan,
        // el proveedor de IA, el periodo de prueba y el id de Stripe.
        $attrs['organization'] = $car->organization?->only(['name', 'slug', 'logo']);

        return $attrs;
    }
}
