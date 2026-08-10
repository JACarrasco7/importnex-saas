<?php

namespace App\Http\Requests;

use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    /**
     * Authenticated users with an organization can create cars.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->organization_id !== null;
    }

    /**
     * Normaliza inputs antes de validar.
     * - year: convierte '2020', '2020-01', '2020-01-01' -> '01/2020' (formato canónico).
     */
    protected function prepareForValidation(): void
    {
        $year = $this->input('year');

        if (is_string($year) && preg_match('/^(\d{4})(-\d{2})?(-\d{2})?$/', $year, $m)) {
            $normalized = '01/'.$m[1];
            $this->merge(['year' => $normalized]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->organization_id;

        return [
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            // Acepta MM/YYYY (canónico), YYYY, YYYY-MM, YYYY-MM-DD. Se normaliza en prepareForValidation().
            'year' => ['required', 'string', 'max:10', 'regex:/^(\d{2}\/\d{4}|\d{4}(-\d{2})?(-\d{2})?)$/'],
            'fuel' => ['required', 'string', 'max:255'],
            'transmission' => ['required', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Car::STATUSES)],
            'traffic_light' => ['required', Rule::in(['green', 'amber', 'red', 'neutral'])],
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('organization_id', $orgId)],

            'new_price' => ['nullable', 'numeric', 'min:0'],
            'manual_tax_base' => ['nullable', 'numeric', 'min:0'],
            'transport' => ['nullable', 'numeric', 'min:0'],
            'itv_fee' => ['nullable', 'numeric', 'min:0'],
            'coc_fee' => ['nullable', 'numeric', 'min:0'],
            'dgt_fees' => ['nullable', 'numeric', 'min:0'],
            'professional_fees' => ['nullable', 'numeric', 'min:0'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'cv' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'displacement' => ['nullable', 'integer', 'min:0'],
            'co2' => ['nullable', 'integer', 'min:0'],
            'consumption' => ['nullable', 'numeric', 'min:0'],
            'doors' => ['nullable', 'integer', 'min:0', 'max:10'],
            'seats' => ['nullable', 'integer', 'min:0', 'max:50'],
            'owners' => ['nullable', 'integer', 'min:0'],

            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],

            'version' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'max:17'],
            'euro_norm' => ['nullable', 'string', 'max:20'],
            'seller' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'url_link' => ['nullable', 'url', 'max:2048'],
            'vat_scenario' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string'],

            'itv_date' => ['nullable', 'date'],

            'boe_confirmed' => ['nullable', 'boolean'],
            'is_marketplace' => ['nullable', 'boolean'],

            'verdict' => ['nullable', 'string', 'max:50'],
            'verdict_confidence' => ['nullable', 'string', 'max:20'],
            'verdict_reasoning' => ['nullable', 'string'],
            'verdict_changes' => ['nullable', 'string'],
            'market_avg' => ['nullable', 'numeric', 'min:0'],
            'market_min' => ['nullable', 'numeric', 'min:0'],
            'market_max' => ['nullable', 'numeric', 'min:0'],
            'estimated_saving' => ['nullable', 'numeric'],

            // Arrays (JSON casts)
            'pros' => ['nullable', 'array'],
            'cons' => ['nullable', 'array'],
            'tips' => ['nullable', 'array'],
            'red_flags' => ['nullable', 'array'],
            'research' => ['nullable', 'array'],
            'equipment' => ['nullable', 'array'],
            'comparables_list' => ['nullable', 'array'],
            'fotos_json' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand' => 'marca',
            'model' => 'modelo',
            'year' => 'año',
            'fuel' => 'combustible',
            'transmission' => 'cambio',
            'purchase_price' => 'precio de compra',
            'traffic_light' => 'semáforo',
            'client_id' => 'cliente',
            'lat' => 'latitud',
            'lng' => 'longitud',
        ];
    }
}
