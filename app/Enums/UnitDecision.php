<?php

namespace App\Enums;

/** Opciones del cliente cuando la inspección encuentra algo. */
enum UnitDecision: string
{
    case Continue = 'continue';
    case Replace = 'replace';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::Continue => 'Seguir con este coche',
            self::Replace => 'Buscar otro coche',
            self::Cancel => 'Cancelar el encargo',
        };
    }

    public function targetState(): ImportOrderState
    {
        return match ($this) {
            self::Continue => ImportOrderState::Purchasing,
            self::Replace => ImportOrderState::SearchingReplacement,
            self::Cancel => ImportOrderState::Cancelled,
        };
    }
}
