<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Voided = 'voided';

    /** Se puede firmar (si además no ha caducado). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Sent, self::Viewed], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Viewed => 'Visto por el cliente',
            self::Signed => 'Firmado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Caducado',
            self::Voided => 'Anulado',
        };
    }
}
