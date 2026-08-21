<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\CarRequest;
use App\Models\Client;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        // Honeypot anti-spam: if the hidden field is filled, it's a bot
        if ($request->filled('website')) {
            Log::warning('Car request honeypot triggered', [
                'organization_id' => $organization->id,
                'ip' => $request->ip(),
            ]);

            // Fake success to confuse the bot
            return redirect()
                ->route('public.car-request.success', ['slug' => $slug])
                ->with('success', '¡Solicitud recibida! Te contactaremos pronto.');
        }

        $currentYear = (int) date('Y');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['required', 'string', 'min:9', 'max:50', 'regex:/^[0-9\s\+\-\(\)]+$/'],
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year_min' => "required|integer|min:1990|max:{$currentYear}",
            'year_max' => ['required', 'integer', 'min:1990', "max:{$currentYear}", function ($attribute, $value, $fail) use ($request) {
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
            'mileage_max' => 'required|integer|min:0',
            'power_min' => 'required|integer|min:50|max:2000',
            'power_max' => ['required', 'integer', 'min:50', 'max:2000', function ($attribute, $value, $fail) use ($request) {
                if ($request->filled('power_min') && (int) $value < (int) $request->input('power_min')) {
                    $fail('La potencia máxima no puede ser menor que la mínima.');
                }
            }],
            'engine_type' => 'nullable|string|max:50',
            'fuel' => 'required|string|max:50',
            'transmission' => 'required|string|max:50',
            'body_type' => 'required|string|max:50',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'required|integer|min:2|max:9',
            'color' => 'required|string|max:50',
            'requirements' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ], [
            'phone.regex' => 'El teléfono solo puede contener números, espacios, +, -, paréntesis.',
            'phone.min' => 'El teléfono debe tener al menos 9 caracteres.',
            'year_min.max' => "El año no puede ser superior a {$currentYear}.",
            'year_max.max' => "El año no puede ser superior a {$currentYear}.",
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['organization_id'] = $organization->id;
        $data['status'] = 'pending';
        // Remove honeypot field before save
        unset($data['website']);

        // Try to link to existing client by email (more reliable than phone)
        if (! empty($data['email'])) {
            $client = Client::where('organization_id', $organization->id)
                ->where(function ($q) use ($data) {
                    // Search in JSON contact_info field (LIKE compatible con MySQL y SQLite)
                    $q->where('contact_info', 'like', '%"email":"'.$data['email'].'"%');
                })
                ->first();

            if (! $client && ! empty($data['phone'])) {
                // Fallback: search by phone in JSON
                $normalizedPhone = preg_replace('/[^0-9]/', '', $data['phone']);
                $client = Client::where('organization_id', $organization->id)
                    ->where('contact_info', 'like', '%"phone":"%'.substr($normalizedPhone, -9).'%')
                    ->first();
            }

            if ($client) {
                $data['client_id'] = $client->id;
            }
        }

        // ⚠️ Si no hay cliente existente, CREARLO (el lead no puede quedar huérfano):
        // el vehículo que luego se vincule a esta solicitud tendrá cliente siempre.
        if (empty($data['client_id'])) {
            $client = Client::create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'contact_info' => json_encode([
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]),
                'looking_for' => trim(($data['brand'] ?? '').' '.($data['model'] ?? '')),
                'budget_min' => $data['budget_min'] ?? null,
                'budget_max' => $data['budget_max'] ?? null,
                'status' => 'New',
            ]);
            $data['client_id'] = $client->id;
        }

        $carRequest = CarRequest::create($data);

        // Create alert for organization admins
        Alert::create([
            'organization_id' => $organization->id,
            'alert_type' => 'car_request',
            'reference_type' => CarRequest::class,
            'reference_id' => $carRequest->id,
            'message' => "Nueva solicitud de {$carRequest->name}".($carRequest->brand ? " - {$carRequest->brand} {$carRequest->model}" : ''),
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
