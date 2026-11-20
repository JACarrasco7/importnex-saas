<?php

namespace App\Support;

use App\Models\Car;

/**
 * Calculo del desglose "aproximado" que se muestra al cliente en la ficha
 * publica del marketplace (/marketplace/{id} y /p/{token}).
 *
 * Reglas de negocio (decision 12-oct-2026):
 *  - Honorarios JJ Import: 1.000 EUR por defecto, 1.500 EUR si el coche >= 30.000 EUR.
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
     * `paperwork` = gestoría, placas de exportación + seguro del traslado y
     * placas españolas (columna `other_fees`, decision 20-nov-2026). Va en el
     * total para que el cliente vea lo que de verdad le costaría.
     *
     * @return array{car_price: float, transport: float, processing: float, paperwork: float, iedmt: float, honorarios: int, total: float, has_data: bool}
     */
    public static function forCar(Car $car, ?float $iedmt = null): array
    {
        $carPrice = (float) ($car->purchase_price ?? 0);
        $transport = (float) ($car->transport ?? 0);
        $itv = (float) ($car->itv_fee ?? 0);
        $coc = (float) ($car->coc_fee ?? 0);
        $dgt = (float) ($car->dgt_fees ?? 0);
        $paperwork = (float) ($car->other_fees ?? 0);
        $iedmtValue = (float) ($iedmt ?? 0);
        $processing = $itv + $coc + $dgt;
        $honorarios = self::honorariosFor($carPrice);

        return [
            'car_price' => $carPrice,
            'transport' => $transport,
            'processing' => $processing,
            'paperwork' => $paperwork,
            'iedmt' => $iedmtValue,
            'honorarios' => $honorarios,
            'total' => $carPrice + $transport + $processing + $paperwork + $iedmtValue + $honorarios,
            'has_data' => $carPrice > 0,
        ];
    }

    /**
     * La comparativa con el mercado español solo se enseña cuando favorece
     * al cliente: el total aproximado no supera al más barato de España.
     * Si lo supera, el bloque desaparece (decision 20-nov-2026); el
     * cliente no recibe un "esta unidad es más cara" en frío.
     */
    public static function marketComparisonFavoursClient(Car $car, float $total): bool
    {
        $marketMin = (float) ($car->market_min ?? 0);
        $marketMax = (float) ($car->market_max ?? 0);

        return $total > 0 && $marketMin > 0 && $marketMax > $marketMin && $total <= $marketMin;
    }

    public static function honorariosFor(float $carPrice): int
    {
        return $carPrice >= self::HONORARIOS_THRESHOLD
            ? self::HONORARIOS_PREMIUM
            : self::HONORARIOS_BASE;
    }
}
