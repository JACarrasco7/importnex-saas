<?php

namespace App\Http\Controllers;

use App\Models\CarRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarRequestController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->user()->organization;

        $query = CarRequest::where('organization_id', $organization->id)
            ->with('client')
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => CarRequest::where('organization_id', $organization->id)->count(),
            'pending' => CarRequest::where('organization_id', $organization->id)->where('status', 'pending')->count(),
            'contacted' => CarRequest::where('organization_id', $organization->id)->where('status', 'contacted')->count(),
            'in_progress' => CarRequest::where('organization_id', $organization->id)->where('status', 'in_progress')->count(),
            'completed' => CarRequest::where('organization_id', $organization->id)->where('status', 'completed')->count(),
        ];

        return Inertia::render('CarRequests/Index', [
            'requests' => $requests,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function show(Request $request, CarRequest $carRequest)
    {
        $this->authorize($request, $carRequest);

        $carRequest->load('client');

        return Inertia::render('CarRequests/Show', [
            'carRequest' => $carRequest,
        ]);
    }

    public function updateStatus(Request $request, CarRequest $carRequest)
    {
        $this->authorize($request, $carRequest);

        $validated = $request->validate([
            'status' => 'required|in:pending,contacted,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        $carRequest->update($validated);

        return back()->with('success', 'Estado actualizado');
    }

    public function destroy(Request $request, CarRequest $carRequest)
    {
        $this->authorize($request, $carRequest);
        $carRequest->delete();

        return redirect()->route('car-requests.index')
            ->with('success', 'Solicitud eliminada');
    }

    private function authorize(Request $request, CarRequest $carRequest): void
    {
        if ($carRequest->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
    }
}
