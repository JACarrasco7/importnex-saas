<?php

namespace App\Http\Requests\Portal;

use App\Support\SpanishId;
use Illuminate\Foundation\Http\FormRequest;

/** Firma del contrato en el portal: nombre, DNI/NIE, código y casilla. */
class SignContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso lo da el token del portal (comprobado en el controlador).
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:191'],
            'dni' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! SpanishId::isValid((string) $value)) {
                    $fail('Escribe un DNI (12345678Z) o NIE (X1234567L) válido.');
                }
            }],
            'code' => ['required', 'string', 'max:12'],
            'accept' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nombre y apellidos',
            'dni' => 'DNI/NIE',
            'code' => 'código de verificación',
            'accept' => 'aceptación del contrato',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'accept.accepted' => 'Marca la casilla para aceptar el contrato.',
        ];
    }
}
