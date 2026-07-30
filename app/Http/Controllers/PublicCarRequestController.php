<?php

namespace App\Http\Controllers;

use App\Models\CarRequest;
use App\Models\Client;
use App\Models\Organization;
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

        return inertia('Public/CarRequestForm', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'logo' => $organization->logo,
            ],
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
            'phone' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year_min' => 'nullable|integer|min:1990|max:2027',
            'year_max' => 'nullable|integer|min:1990|max:2027|gte:year_min',
            'budget_min' => 'nullable|integer|min:0',
            'budget_max' => 'nullable|integer|min:0|gte:budget_min',
            'mileage_max' => 'nullable|integer|min:0',
            'fuel' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'body_type' => 'nullable|string|max:50',
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

        // Try to link to existing client by email
        if ($data['email']) {
            $client = Client::where('email', $data['email'])
                ->where('organization_id', $organization->id)
                ->first();

            if ($client) {
                $data['client_id'] = $client->id;
            }
        }

        $carRequest = CarRequest::create($data);

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