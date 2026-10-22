<?php

namespace App\Http\Requests\ImportOrders;

use App\Enums\ApprovalKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Petición de aprobación al cliente (coste extra o decisión sobre el coche). */
class StoreOrderApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $maxKb = (int) config('import_orders.media_max_kb', 15360);

        return [
            'kind' => ['required', Rule::enum(ApprovalKind::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'amount' => ['nullable', 'required_if:kind,extra_cost', 'numeric', 'min:0', 'max:1000000'],
            'due_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['file', "max:{$maxKb}", 'mimes:jpg,jpeg,png,webp,heic,pdf'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'kind' => 'tipo',
            'title' => 'título',
            'description' => 'explicación',
            'amount' => 'importe',
            'due_hours' => 'plazo de respuesta',
            'photos' => 'fotos',
            'photos.*' => 'foto',
        ];
    }
}
