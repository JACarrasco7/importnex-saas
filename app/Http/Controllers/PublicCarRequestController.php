<?php

namespace App\Http\Controllers;

use App\Models\CarRequest;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublicCarRequestController extends Controller
{
    public function index(string $slug)
    {
        $organization = Organization::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $locale = app()->getLocale();
        $translations = trans('car_request_form', [], $locale);

        return inertia('Public/CarRequestForm', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'logo' => $organization->logo,
            ],
            'translations' => ['car_request_form' => $translations],
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $organization = Organization::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year_min' => 'nullable|integer|min:1990|max:2027',
            'year_max' => ['nullable', 'integer', 'min:1990', 'max:2027', function ($attribute, $value, $fail) use ($request) {
                if ($request->filled('year_min') && (int) $value < (int) $request->input('year_min')) {
                    $fail('El año máximo no puede ser menor que el año mínimo.');
                }
            }],
            'budget_min' => 'required|integer|min:0',
            'budget_max' => ['required', 'integer', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->filled('budget_min') && (int) $value < (int) $request->input('budget_min')) {
                    $fail('El presupuesto máximo no puede ser menor que el mínimo.');
                }
            }],
            'mileage_max' => 'nullable|integer|min:0',
            'power_min' => 'nullable|integer|min:50|max:2000',
            'power_max' => ['nullable', 'integer', 'min:50', 'max:2000', function ($attribute, $value, $fail) use ($request) {
                if ($request->filled('power_min') && (int) $value < (int) $request->input('power_min')) {
                    $fail('La potencia máxima no puede ser menor que la mínima.');
                }
            }],
            'engine_type' => 'nullable|string|max:50',
            'fuel' => 'required|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'body_type' => 'required|string|max:50',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'nullable|integer|min:2|max:9',
            'color' => 'nullable|string|max:50',
            'requirements' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['organization_id'] = $organization->id;
        $data['status'] = 'pending';

        // Try to link to existing client by contact_info (JSON: {email, phone})
        if (!empty($data['email']) || !empty($data['phone'])) {
            $query = Client::where('organization_id', $organization->id);

            if (!empty($data['email'])) {
                $query->where(function ($q) use ($data) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(contact_info, '$.email')) = ?", [$data['email']]);
                });
            }

            $client = $query->first();

            if ($client) {
                $data['client_id'] = $client->id;
            }
        }

        $carRequest = CarRequest::create($data);

        // Create alert for organization admins
        Alert::create([
            'organization_id' => $organization->id,
            'alert_type' => 'car_request',
            'reference_type' => CarRequest::class,
            'reference_id' => $carRequest->id,
            'message' => "Nueva solicitud de {$carRequest->name}" . ($carRequest->brand ? " - {$carRequest->brand} {$carRequest->model}" : ''),
        ]);

        return redirect()
            ->route('public.car-request.success', ['slug' => $slug])
            ->with('success', '¡Solicitud recibida! Te contactaremos pronto.');
    }

    public function success(string $slug)
    {
        $organization = Organization::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        return inertia('Public/CarRequestSuccess', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'logo' => $organization->logo,
            ],
        ]);
    }
}
