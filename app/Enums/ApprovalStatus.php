<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Esperando respuesta',
            self::Approved => 'Aprobado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Sin respuesta (caducado)',
            self::Withdrawn => 'Retirado',
        };
    }
}
