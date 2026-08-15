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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

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
        $car->load(['photos', 'documents', 'expenses', 'checklists', 'client']);

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
                'matching_requests' => $this->matchingRequests($car),
                'laravel_pdfs' => $this->laravelPdfs($car),
            ],
        ]);
    }

    /**
     * PDFs que genera LARAVEL (Blade + Browsershot) a partir de los esqueletos
     * .txt del ZIP. Se listan en la pestaña Documentos para diferenciarlos de
     * los PDFs que genera Claude (informe de búsqueda/unidad).
     *
     * @return array<int, array{key: string, label: string, route: string, available: bool}>
     */
    private function laravelPdfs(Car $car): array
    {
        $contenidoDir = 'cars/'.$car->id.'/contenido';
        $has = function (string $file) use ($contenidoDir): bool {
            foreach (['local', 'public'] as $disk) {
                if (Storage::disk($disk)->exists($contenidoDir.'/'.$file)) {
                    return true;
                }
            }

            return false;
        };

        return [
            [
                'key' => 'ficha',
                'label' => 'Ficha cliente',
                'route' => route('cars.ficha', $car->id),
                'available' => $has('ficha-publicitaria.txt'),
            ],
            [
                'key' => 'informe_interno',
                'label' => 'Informe interno',
                'route' => route('cars.informe-interno', $car->id),
                'available' => $has('informe-interno.txt'),
            ],
        ];
    }

    /**
     * Solicitudes de clientes compatibles con este coche: misma marca y, si la
     * solicitud concreta modelo, que coincida también. Solo estados vivos
     * (pendiente / contactada / en curso). Sirve para vincular el coche al
     * cliente de la solicitud en un clic tras importarlo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function matchingRequests(Car $car): array
    {
        return CarRequest::query()
            ->whereIn('status', ['pending', 'contacted', 'in_progress'])
            ->where(fn ($q) => $q
                ->whereNull('brand')
                ->orWhere('brand', 'like', '%'.$car->brand.'%'))
            ->where(fn ($q) => $q
                ->where(fn ($q2) => $q2->whereNull('model')->orWhere('model', ''))
                ->orWhere('model', 'like', '%'.$car->model.'%'))
            ->with('client:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
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
     * Vincula este coche con la solicitud de un cliente: asigna el cliente al
     * coche (si la solicitud lo tiene) y pasa la solicitud a "en curso".
     */
    public function matchRequest(Car $car, CarRequest $carRequest): RedirectResponse
    {
        if ($carRequest->client_id) {
            $car->client_id = $carRequest->client_id;
            $car->save();
        }

        $carRequest->status = 'in_progress';
        $carRequest->notes = trim(($carRequest->notes ? $carRequest->notes."\n" : '')
            .'['.now()->format('d/m/Y H:i')."] Vinculado a vehículo #{$car->id} ({$car->brand} {$car->model}).");
        $carRequest->save();

        return redirect()->route('cars.show', $car->id)
            ->with('success', 'Vehículo vinculado a la solicitud.');
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
