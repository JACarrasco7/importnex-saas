<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use App\Models\Client;
use App\Services\Scraping\CarScrapingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request): Response
    {
        $cars = Car::query()
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
            ],
        ]);
    }

    private function docGroupLabel(string $group): string
    {
        return match ($group) {
            'seller_origin' => 'Seller / Country of origin',
            'purchase_transport' => 'Purchase & transport',
            'spain_procedures' => 'Spain procedures',
            'ai_reports' => 'AI briefing reports',
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
