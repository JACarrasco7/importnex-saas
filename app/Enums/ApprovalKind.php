<?php

namespace App\Enums;

enum ApprovalKind: string
{
    /** Coste extra (reparación, COC caro, transporte especial...). */
    case ExtraCost = 'extra_cost';

    /** Decisión tras la inspección: seguir, buscar otro coche o cancelar. */
    case UnitDecision = 'unit_decision';

    public function label(): string
    {
        return match ($this) {
            self::ExtraCost => 'Coste adicional',
            self::UnitDecision => 'Decisión sobre el coche',
        };
    }
}
