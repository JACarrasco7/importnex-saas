<?php

namespace App\Http\Requests\ImportOrders;

use App\Enums\PaymentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta/edición de un pago del encargo (el tenant se valida en el controlador). */
class StoreOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'kind' => ['nullable', Rule::enum(PaymentKind::class)],
            'concept' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'amount' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0.01', 'max:1000000'],
            'due_date' => ['nullable', 'date'],
            'stripe_payment_link' => ['nullable', 'url:https', 'max:500', 'regex:#^https://(buy\.stripe\.com|checkout\.stripe\.com|pay\.stripe\.com)/#'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'kind' => 'tipo de pago',
            'concept' => 'concepto',
            'amount' => 'importe',
            'due_date' => 'fecha límite',
            'stripe_payment_link' => 'enlace de pago de Stripe',
            'notes' => 'notas',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'stripe_payment_link.regex' => 'Pega un enlace de pago de Stripe (empieza por https://buy.stripe.com/).',
        ];
    }
}
