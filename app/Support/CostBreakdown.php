<?php

namespace App\Support;

use App\Models\Car;

/**
 * Calculo del desglose "aproximado" que se muestra al cliente en la ficha
 * publica del marketplace (/marketplace/{id} y /p/{token}).
 *
 * Reglas de negocio (decision 12-oct-2026):
 *  - Honorarios de la empresa del coche (Ajustes → Datos de empresa). Por
 *    defecto 1.000 EUR, 1.500 EUR si el coche >= 30.000 EUR.
 *    Son fijos, no un porcentaje. No se muestran al cliente como coste separado
 *    de la BD (professional_fees) sino como linea propia del desglose.
 *  - Todos los importes son APROXIMADOS. El cliente ve disclaimer explicito.
 *  - Confirmamos por escrito antes de la reserva (no son "costes finales").
 */
class CostBreakdown
{
    public const HONORARIOS_BASE = 1000;

    public const HONORARIOS_PREMIUM = 1500;

    public const HONORARIOS_THRESHOLD = 30000;

    /**
     * @return array{car_price: float, transport: float, processing: float, iedmt: float, honorarios: int, total: float, has_data: bool}
     */
    public static function forCar(Car $car, ?float $iedmt = null): array
    {
        $carPrice = (float) ($car->purchase_price ?? 0);
        $transport = (float) ($car->transport ?? 0);
        $itv = (float) ($car->itv_fee ?? 0);
        $coc = (float) ($car->coc_fee ?? 0);
        $dgt = (float) ($car->dgt_fees ?? 0);
        $iedmtValue = (float) ($iedmt ?? 0);
        $processing = $itv + $coc + $dgt;
        // Las fichas públicas cargan la organización; sin ella, escala por defecto.
        $fees = $car->relationLoaded('organization') ? $car->organization?->brand()->fees() : null;
        $honorarios = self::honorariosFor($carPrice, $fees);

        return [
            'car_price' => $carPrice,
            'transport' => $transport,
            'processing' => $processing,
            'iedmt' => $iedmtValue,
            'honorarios' => $honorarios,
            'total' => $carPrice + $transport + $processing + $iedmtValue + $honorarios,
            'has_data' => $carPrice > 0,
        ];
    }

    /**
     * @param  array{base: int, premium: int, threshold: int}|null  $fees
     */
    public static function honorariosFor(float $carPrice, ?array $fees = null): int
    {
        $fees ??= ['base' => self::HONORARIOS_BASE, 'premium' => self::HONORARIOS_PREMIUM, 'threshold' => self::HONORARIOS_THRESHOLD];

        return $carPrice >= $fees['threshold'] ? $fees['premium'] : $fees['base'];
    }
}
