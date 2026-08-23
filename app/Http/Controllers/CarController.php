<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use App\Models\CarRequest;
use App\Models\Client;
use App\Services\Scraping\CarScrapingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CarController extends Controller
{
    public function index(Request $request): Response
    {
        $cars = Car::query()
            ->with('photos')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('traffic_light'), fn ($q, $t) => $q->where('traffic_light', $t))
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('brand', 'like', "%$s%")
                        ->orWhere('model', 'like', "%$s%")
                        ->orWhere('vin', 'like', "%$s%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $statuses = Car::STATUSES;
        $lights = ['green', 'amber', 'red', 'neutral'];

        return Inertia::render('Cars/Index', [
            'cars' => Inertia::defer(fn () => $cars),
            'statuses' => $statuses,
            'lights' => $lights,
            'filters' => $request->only(['status', 'traffic_light', 'search']),
        ]);
    }

    /**
     * Scrape a car listing URL and return extracted data for form auto-fill.
     *
     * POST /cars/scrape-url
     * Body: { url: string }
     */
    public function scrapeUrl(Request $request, CarScrapingService $scraper): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'max:2048', 'regex:#^https?://#i'],
        ]);

        $org = auth()->user()->organization;
        $url = $request->input('url');

        $result = $scraper->scrape($url, $org);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function create(): Response
    {
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Cars/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreCarRequest $request): RedirectResponse
    {
        $org = auth()->user()->organization;
        if ($org->limitReached('cars')) {
            return back()->with('error', "You've reached your plan's car limit. Please upgrade your subscription.");
        }

        Car::create([
            ...$request->validated(),
            'organization_id' => auth()->user()->organization_id,
        ]);

        return redirect()->route('cars.index')
            ->with('success', 'Car created successfully.');
    }

    public function show(Car $car): Response
    {
        $car->load(['photos', 'documents', 'expenses', 'checklists', 'client', 'contractAcceptances']);

        // Pre-compute derived data for the enriched valuation UI
        $car->researchGaps;       // touch accessor
        $car->comparablesStats;    // touch accessor
        $car->calculateTotalCost(); // touch method

        $checklistMilestones = $car->checklists->where('kind', 'milestone')->values();
        $checklistInspections = $car->checklists->where('kind', 'inspection')->values();

        // Group inspections by section for the UI
        $inspectionsBySection = $checklistInspections
            ->groupBy(fn ($item) => $item->section ?? 'Other')
            ->map(fn ($items, $section) => [
                'section' => $section,
                'items' => $items->values(),
            ])
            ->values();

        // Group documents by phase for the UI
        $documentsByGroup = $car->documents
            ->groupBy(fn ($doc) => $doc->group ?? 'other')
            ->map(fn ($items, $group) => [
                'group' => $group,
                'label' => $this->docGroupLabel($group),
                'items' => $items->values(),
            ])
            ->values();

        $milestonesProgress = [
            'completed' => $checklistMilestones->where('completed', true)->count(),
            'total' => $checklistMilestones->count(),
        ];

        $inspectionsProgress = [
            'completed' => $checklistInspections->where('completed', true)->count(),
            'total' => $checklistInspections->count(),
        ];

        return Inertia::render('Cars/Show', [
            'car' => $car,
            'derived' => [
                'total_cost' => $car->calculateTotalCost(),
                'iedmt' => $car->calculateIEDMT(),
                'research_gaps' => $car->researchGaps,
                'comparables_stats' => $car->comparablesStats,
                'milestones_progress' => $milestonesProgress,
                'inspections_progress' => $inspectionsProgress,
                'inspections_by_section' => $inspectionsBySection,
                'documents_by_group' => $documentsByGroup,
                'selectable_requests' => $car->client ? [] : $this->selectableRequests($car),
                'linked_request' => $this->linkedRequest($car),
                'laravel_pdfs' => $this->laravelPdfs($car),
                'contract' => $this->contractSummary($car),
                'tracking' => [
                    'is_shared' => $car->is_tracking_shared,
                    'is_public_trackable' => $car->is_public_trackable,
                    'url' => $car->tracking_url,
                    'token' => $car->tracking_token,
                    'shared_at' => $car->tracking_shared_at?->toIso8601String(),
                    'shared_with_email' => $car->tracking_shared_with_email,
                    'views' => (int) $car->tracking_views,
                ],
            ],
        ]);
    }

    /**
     * PDFs que genera LARAVEL (Blade + Browsershot) a partir de los esqueletos
     * .txt del ZIP (o con fallback desde los datos del coche). Se listan en la
     * pestaña Documentos para diferenciarlos de los PDFs que genera Claude.
     *
     * Los 3 están SIEMPRE disponibles: las rutas sirven el esqueleto del ZIP
     * si existe y, si no, generan el documento desde los datos del coche
     * (PaqueteValoracionController tiene fallbacks propios).
     *
     * @return array<int, array{key: string, label: string, route: string, available: bool}>
     */
    private function laravelPdfs(Car $car): array
    {
        return [
            [
                'key' => 'ficha',
                'label' => 'Ficha cliente',
                'route' => route('cars.ficha', $car->id),
                'available' => true,
            ],
            [
                'key' => 'folleto',
                'label' => 'Folleto',
                'route' => route('cars.folleto', $car->id),
                'available' => true,
            ],
            [
                'key' => 'informe_interno',
                'label' => 'Informe interno',
                'route' => route('cars.informe-interno', $car->id),
                'available' => true,
            ],
        ];
    }

    /**
     * Resumen del contrato (último creado) para mostrar el panel en Cars/Show.
     * Si no hay ninguno, devuelve null y la UI muestra el botón "Generar".
     * Incluye un QR (SVG data URI) para que el cliente escanee y abra el
     * contrato desde el móvil sin escribir el enlace.
     *
     * @return array{public_url:string, pdf_url:string, accepted_at:?string, hash:string, qr_svg:string}|null
     */
    private function contractSummary(Car $car): ?array
    {
        $contract = $car->contractAcceptances()->latest('accepted_at')->latest('id')->first();
        if (! $contract) {
            return null;
        }

        $qr = QrCode::format('svg')->size(160)->margin(0)->generate($contract->public_url);

        return [
            'public_url' => $contract->public_url,
            'pdf_url' => $contract->accepted_at
                ? route('public.contract.pdf', $contract->public_token)
                : null,
            'accepted_at' => $contract->accepted_at?->toIso8601String(),
            'hash' => $contract->contract_hash,
            'version' => $contract->contract_version,
            'qr_svg' => 'data:image/svg+xml;base64,'.base64_encode($qr),
        ];
    }

    /**
     * Solicitudes seleccionables para vincular a este coche: todas las de la
     * organización en estado pendiente/contactada (aún sin coche asignado).
     * El usuario elige una desde un <select> en la ficha y la vincula en un
     * clic. Excluye solicitudes cuyo cliente ya tiene otro coche asignado.
     *
     * @return array<int, array<string, mixed>>
     */
    private function selectableRequests(Car $car): array
    {
        return CarRequest::query()
            // Multi-tenancy: CarRequest NO tiene global scope propio (a
            // diferencia de Car), así que el filtro por organización es
            // obligatorio aquí o se filtraría información de otros tenants.
            ->where('organization_id', $car->organization_id)
            ->whereIn('status', ['pending', 'contacted'])
            // Incluir solicitudes sin cliente (client_id NULL) y excluir
            // solo las cuyo cliente ya tiene otro coche asignado en la org.
            ->where(fn ($q) => $q
                ->whereNull('client_id')
                ->orWhereNotIn('client_id', function ($q2) use ($car) {
                    $q2->select('client_id')
                        ->from('cars')
                        ->where('organization_id', $car->organization_id)
                        ->whereNotNull('client_id');
                }))
            ->with('client:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'client_id', 'name', 'brand', 'model', 'budget_max', 'status', 'created_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name ?: $r->client?->name,
                'brand' => $r->brand,
                'model' => $r->model,
                'budget_max' => $r->budget_max,
                'status' => $r->status,
                'client_id' => $r->client_id,
            ])
            ->values()
            ->all();
    }

    /**
     * Solicitud del cliente ya vinculado al coche (si lo hay). Se usa en la
     * ficha para mostrar los datos de la solicitud junto al cliente asignado.
     *
     * @return array<string, mixed>|null
     */
    private function linkedRequest(Car $car): ?array
    {
        if (! $car->client_id) {
            return null;
        }

        $req = CarRequest::query()
            ->where('organization_id', $car->organization_id)
            ->where('client_id', $car->client_id)
            ->whereIn('status', ['pending', 'contacted', 'in_progress'])
            ->orderByDesc('created_at')
            ->first(['id', 'name', 'brand', 'model', 'budget_max', 'budget_min', 'status', 'created_at', 'notes']);

        if (! $req) {
            return null;
        }

        return [
            'id' => $req->id,
            'name' => $req->name,
            'brand' => $req->brand,
            'model' => $req->model,
            'budget_max' => $req->budget_max,
            'budget_min' => $req->budget_min,
            'status' => $req->status,
            'created_at' => $req->created_at,
            'notes' => $req->notes,
        ];
    }

    /**
     * Vincula este coche con la solicitud de un cliente: asigna el cliente al
     * coche (si la solicitud lo tiene) y pasa la solicitud a "en curso".
     *
     * Seguridad multi-tenant: aborta 403 si la solicitud no pertenece a la
     * misma organización que el coche (evita vincular/modificar datos ajenos).
     */
    public function matchRequest(Car $car, CarRequest $carRequest): RedirectResponse
    {
        Log::info('cars.match-request: start', [
            'car_id' => $car->id,
            'car_org' => $car->organization_id,
            'car_client_before' => $car->client_id,
            'car_request_id' => $carRequest->id,
            'car_request_org' => $carRequest->organization_id,
            'car_request_status' => $carRequest->status,
            'car_request_client' => $carRequest->client_id,
            'car_request_brand' => $carRequest->brand,
            'car_request_model' => $carRequest->model,
        ]);

        if ((int) $carRequest->organization_id !== (int) $car->organization_id) {
            Log::warning('cars.match-request: 403 cross-org', [
                'car_id' => $car->id,
                'car_org' => $car->organization_id,
                'car_request_id' => $carRequest->id,
                'car_request_org' => $carRequest->organization_id,
            ]);
            abort(403, 'La solicitud pertenece a otra organización.');
        }

        // El coche ya tiene cliente → no se puede vincular otro sin desvincular primero.
        if ($car->client_id && $car->client_id !== $carRequest->client_id) {
            Log::warning('cars.match-request: car already linked to another client', [
                'car_id' => $car->id,
                'existing_client_id' => $car->client_id,
                'incoming_request_id' => $carRequest->id,
                'incoming_client_id' => $carRequest->client_id,
            ]);

            return redirect()->route('cars.show', $car->id)
                ->with('error', 'Este coche ya tiene un cliente vinculado. Desvincúlalo primero si quieres cambiarlo.');
        }

        try {
            return DB::transaction(function () use ($car, $carRequest) {
                // Fallback: si la solicitud no tiene cliente (datos viejos o manuales),
                // crearlo a partir de sus datos para que el coche nunca quede sin cliente.
                if (! $carRequest->client_id) {
                    $client = $this->createClientFromRequestData($carRequest);
                    $carRequest->client_id = $client->id;
                    Log::info('cars.match-request: client created from request data', [
                        'car_request_id' => $carRequest->id,
                        'new_client_id' => $client->id,
                    ]);
                }

                $car->client_id = $carRequest->client_id;
                // Al vincular a un cliente, el coche queda RESERVADO para él (visible en kanban).
                $this->markCarReserved($car);
                $car->save();

                $carRequest->status = 'in_progress';
                $carRequest->notes = trim(($carRequest->notes ? $carRequest->notes."\n" : '')
                    .'['.now()->format('d/m/Y H:i')."] Vinculado a vehículo #{$car->id} ({$car->brand} {$car->model}).");
                $carRequest->save();

                Log::info('cars.match-request: OK', [
                    'car_id' => $car->id,
                    'car_request_id' => $carRequest->id,
                    'final_client_id' => $car->client_id,
                ]);

                return redirect()->route('cars.show', $car->id)
                    ->with('success', 'Vehículo vinculado a la solicitud y reservado para el cliente.');
            });
        } catch (\Throwable $e) {
            Log::error('cars.match-request: exception', [
                'car_id' => $car->id,
                'car_request_id' => $carRequest->id,
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('cars.show', $car->id)
                ->with('error', 'No se pudo vincular el vehículo: '.$e->getMessage());
        }
    }

    /**
     * Crea una SOLICITUD para este coche (boca a boca / manual): la solicitud
     * es el acto primario — de ella nace el cliente (se busca por email/phone
     * o se crea), se vincula al coche y pasa a "en curso".
     *
     * Regla (21-ago-2026): se crea la solicitud y con eso se crea el cliente.
     * NUNCA al revés (cliente suelto que luego inventa una solicitud).
     */
    public function createRequest(Car $car, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'requirements' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($car, $data) {
            $client = $this->resolveClientFromData($data, $car->organization_id);

            $carRequest = CarRequest::create([
                'organization_id' => $car->organization_id,
                'client_id' => $client->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'brand' => $car->brand,
                'model' => $car->model,
                'status' => 'in_progress',
                'requirements' => $data['requirements']
                    ?? 'Solicitud creada desde la ficha del vehículo (boca a boca / manual).',
                'notes' => '['.now()->format('d/m/Y H:i')."] Vinculado a vehículo #{$car->id} ({$car->brand} {$car->model}).",
            ]);

            $car->client_id = $client->id;
            // Al crear la solicitud y vincular, el coche queda RESERVADO para el cliente.
            $this->markCarReserved($car);
            $car->save();

            return redirect()->route('cars.show', $car->id)
                ->with('success', 'Solicitud creada y cliente vinculado al vehículo.');
        });
    }

    /**
     * Pasa el coche a "Reserved" (reservado para cliente) si aún está en una
     * fase previa del pipeline (Located/Valuing/Offered). No toca estados ya
     * avanzados (Purchased/In_transit/Processing/Delivered) ni la entrega.
     */
    private function markCarReserved(Car $car): void
    {
        if (in_array($car->status, ['Located', 'Valuing', 'Offered'], true)) {
            $car->status = 'Reserved';
        }
    }

    /**
     * Busca un cliente por email (y por teléfono como fallback); si no existe,
     * lo crea a partir de los datos dados. Compatible con MySQL y SQLite.
     */
    private function resolveClientFromData(array $data, int $organizationId): Client
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        if ($email) {
            $client = Client::where('organization_id', $organizationId)
                ->where('contact_info', 'like', '%"email":"'.$email.'"%')
                ->first();

            if ($client) {
                return $client;
            }
        }

        if ($phone) {
            $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
            $client = Client::where('organization_id', $organizationId)
                ->where('contact_info', 'like', '%"phone":"%'.substr($normalizedPhone, -9).'%')
                ->first();

            if ($client) {
                return $client;
            }
        }

        return Client::create([
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'contact_info' => json_encode([
                'email' => $email,
                'phone' => $phone,
            ]),
            'status' => 'New',
        ]);
    }

    /**
     * Crea un cliente a partir de los datos de una solicitud que no lo tenía.
     */
    private function createClientFromRequestData(CarRequest $carRequest): Client
    {
        $existing = Client::where('organization_id', $carRequest->organization_id)
            ->where('name', $carRequest->name)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Client::create([
            'organization_id' => $carRequest->organization_id,
            'name' => $carRequest->name ?: 'Cliente sin nombre',
            'contact_info' => json_encode([
                'email' => $carRequest->email,
                'phone' => $carRequest->phone,
            ]),
            'looking_for' => trim(($carRequest->brand ?? '').' '.($carRequest->model ?? '')),
            'budget_min' => $carRequest->budget_min,
            'budget_max' => $carRequest->budget_max,
            'status' => 'New',
        ]);
    }

    private function docGroupLabel(string $group): string
    {
        return match ($group) {
            'seller_origin' => 'Seller / Country of origin',
            'purchase_transport' => 'Purchase & transport',
            'spain_procedures' => 'Spain procedures',
            'ai_reports' => 'AI research reports (Claude)',
            default => ucfirst(str_replace('_', ' ', $group)),
        };
    }

    public function edit(Car $car): Response
    {
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Cars/Edit', [
            'car' => $car,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateCarRequest $request, Car $car): RedirectResponse
    {
        $car->update($request->validated());

        return redirect()->route('cars.index')
            ->with('success', 'Car updated successfully.');
    }

    public function destroy(Car $car): RedirectResponse
    {
        $car->delete();

        return redirect()->route('cars.index')
            ->with('success', 'Car deleted successfully.');
    }
}
