<?php

namespace App\Enums;

enum PaymentKind: string
{
    case FeeDeposit = 'fee_deposit';
    case FeeFinal = 'fee_final';
    case ExtraCost = 'extra_cost';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FeeDeposit => 'Señal (50 % honorarios)',
            self::FeeFinal => 'Resto de honorarios (50 %)',
            self::ExtraCost => 'Coste adicional aprobado',
            self::Other => 'Otro pago',
        };
    }
}
