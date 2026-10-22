<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Cash = 'cash';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transferencia',
            self::Cash => 'Efectivo',
            self::Stripe => 'Tarjeta (Stripe)',
        };
    }
}
