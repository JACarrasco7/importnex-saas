<?php

namespace App\Enums;

/**
 * Las 6 fases que ve el cliente en su portal. Agrupan los estados internos
 * del encargo en algo que se entiende sin conocer el proceso.
 */
enum ClientPhase: string
{
    case Signature = 'signature';
    case Inspection = 'inspection';
    case Purchase = 'purchase';
    case Transport = 'transport';
    case Paperwork = 'paperwork';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Signature => 'Firma',
            self::Inspection => 'Inspección',
            self::Purchase => 'Compra',
            self::Transport => 'Viaje',
            self::Paperwork => 'Papeles',
            self::Delivery => 'Entrega',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Signature => 'Firmas el contrato de servicio y abonas la señal de los honorarios.',
            self::Inspection => 'Revisamos el coche a fondo y te enseñamos lo que hemos visto.',
            self::Purchase => 'Cerramos la compra con el vendedor en tu nombre.',
            self::Transport => 'El coche viaja hasta España.',
            self::Paperwork => 'COC, ITV de importación, impuesto de matriculación y DGT.',
            self::Delivery => 'Te entregamos el coche matriculado a tu nombre.',
        };
    }

    public function position(): int
    {
        return array_search($this, self::cases(), true);
    }
}
