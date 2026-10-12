<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationId();
        $testimonials = Testimonial::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => $testimonials,
            'stats' => [
                'total' => Testimonial::where('organization_id', $organizationId)->count(),
                'approved' => Testimonial::where('organization_id', $organizationId)->where('is_approved', true)->count(),
                'pending' => Testimonial::where('organization_id', $organizationId)->where('is_approved', false)->count(),
                'featured' => Testimonial::where('organization_id', $organizationId)->where('is_featured', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Testimonials/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTestimonial($request);

        Testimonial::create($data + [
            'organization_id' => $this->currentOrganizationId(),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonio creado.');
    }

    public function edit(Testimonial $testimonial): Response
    {
        $this->authorizeOwnership($testimonial);

        return Inertia::render('Admin/Testimonials/Edit', [
            'testimonial' => $testimonial,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorizeOwnership($testimonial);
        $data = $this->validateTestimonial($request, $testimonial);

        $testimonial->update($data);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonio actualizado.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorizeOwnership($testimonial);
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonio eliminado.');
    }

    public function toggleFeatured(Testimonial $testimonial): RedirectResponse
    {
        $this->authorizeOwnership($testimonial);
        $testimonial->update(['is_featured' => ! $testimonial->is_featured]);

        return back();
    }

    public function toggleApproved(Testimonial $testimonial): RedirectResponse
    {
        $this->authorizeOwnership($testimonial);
        $testimonial->update(['is_approved' => ! $testimonial->is_approved]);

        return back();
    }

    private function validateTestimonial(Request $request, ?Testimonial $testimonial = null): array
    {
        return $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'author_company' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'min:20', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'car_purchased' => ['nullable', 'string', 'max:200'],
            'is_approved' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);
    }

    private function currentOrganizationId(): int
    {
        return Auth::user()->organization_id
            ?? Organization::where('is_owner', true)->value('id')
            ?? abort(403, 'No organization');
    }

    private function authorizeOwnership(Testimonial $testimonial): void
    {
        abort_unless($testimonial->organization_id === $this->currentOrganizationId(), 403);
    }
}
