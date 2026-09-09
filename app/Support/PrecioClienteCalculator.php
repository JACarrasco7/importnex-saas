<?php

namespace App\Support;

/**
 * C3 auditoría 09-sep-2026: precio que ve el cliente en /c/{token}.
 *
 * El cliente ve "precio del anuncio + gastos de compra" SIN desglose de
 * margen. La regla de negocio (usuario, 09-sep-2026): "somos transparentes,
 * enseñamos el precio real del anuncio + gastos de compra calculados para
 * no amarrarnos a un presupuesto ajustado; después se le comunicará el
 * aprox al cliente siempre antes de nada."
 *
 * Fuentes (por orden de prioridad):
 *  1) `ficha-cliente.json → precio.origen` y `precio.gastos`
 *     (lo que escribe la skill, en EUR).
 *  2) `contenido/ficha-publicitaria.txt → [PRECIO]` y `[PRECIO_ORIGEN]`.
 *  3) Cálculo de gastos estimados sobre el origen (config).
 *  4) Si no hay origen → devuelve null y el blade oculta el bloque.
 *
 * El IVA/IEDMT se calcula sobre el origen aplicando tipo general (21%),
 * redondeado al EUR. El transporte seestima por km en camión cerrado
 * desde Alemania hasta Huelva (config `importacion.transporte_eur_km`).
 * La matriculación/gestoría usa una tarifa plana de config.
 */
class PrecioClienteCalculator
{
    /** @return array{
     *     origen: float|null,
     *     gastos: float|null,
     *     total_estimado: float|null,
     *     desglose: array<string, float>,
     *     moneda: string,
     *     fuente: string,
     *     aviso_precio_final: string
     * } */
    public static function desde($car, ?Esqueleto $esqueleto, ?array $ficha): array
    {
        $origen = self::origen($car, $esqueleto, $ficha);
        $gastosCalc = $origen !== null ? self::gastosEstimados($origen, $car) : null;

        // Si la ficha-cliente.json trae gastos explícitos (la skill los calculó
        // con más contexto), tienen prioridad sobre el cálculo genérico.
        $gastosFicha = self::gastosDeFicha($ficha);
        $gastos = $gastosCalc !== null ? ($gastosFicha ?? $gastosCalc) : $gastosFicha;

        $total = ($origen !== null && $gastos !== null) ? ($origen + $gastos) : null;

        return [
            'origen' => $origen,
            'gastos' => $gastos,
            'total_estimado' => $total,
            'desglose' => self::desglose($origen, $gastos, $car),
            'moneda' => 'EUR',
            'fuente' => self::fuente($esqueleto, $ficha),
            'aviso_precio_final' => 'El precio final se confirma por escrito antes de la reserva. '
                .'Este cálculo es una estimación basada en el precio del anuncio y los '
                .'costes habituales de importación desde Alemania; puede variar según '
                .'el transporte, los impuestos aplicables y la gestoría.',
        ];
    }

    private static function origen($car, ?Esqueleto $esqueleto, ?array $ficha): ?float
    {
        // 1) ficha-cliente.json → precio.origen (en EUR)
        if (is_array($ficha) && isset($ficha['precio']['origen'])) {
            $v = (float) $ficha['precio']['origen'];
            if ($v > 0) {
                return $v;
            }
        }

        // 2) ficha-publicitaria.txt → [PRECIO] (en EUR, número limpio)
        if ($esqueleto) {
            $bloque = $esqueleto->uno('PRECIO');
            if ($bloque !== null && ($n = self::parseEur($bloque)) !== null) {
                return $n;
            }
            $bloqueOrigen = $esqueleto->uno('PRECIO_ORIGEN');
            if ($bloqueOrigen !== null && ($n = self::parseEur($bloqueOrigen)) !== null) {
                return $n;
            }
        }

        // 3) Atributos del coche (puede venir del anuncio en la BD)
        if (! empty($car->purchase_price_eur) && (float) $car->purchase_price_eur > 0) {
            return (float) $car->purchase_price_eur;
        }

        return null;
    }

    private static function gastosDeFicha(?array $ficha): ?float
    {
        if (! is_array($ficha) || ! isset($ficha['precio']['gastos'])) {
            return null;
        }
        $v = (float) $ficha['precio']['gastos'];

        return $v > 0 ? $v : null;
    }

    /**
     * Estimación de gastos de compra desde Alemania hasta Huelva:
     *  - Transporte por km en camión cerrado
     *  - IVA sobre el origen (21%, pero exento si el coche es >6 meses y
     *    >6000 km; ese caso se ajusta con un factor configurable)
     *  - IEDMT (impuesto matriculación, depende de emisiones CO2; estimación
     *    conservadora plana)
     *  - Gestoría + matriculación (tarifa plana)
     *
     * El cálculo es transparente y aparece en el desglose; el cliente ve
     * "Transporte + IVA + IEDMT + gestoría = X €" sin que se muestre margen.
     */
    private static function gastosEstimados(float $origen, $car): float
    {
        $km = (int) config('importacion.transporte_km_alemania_huelva', 2200);
        $eurKm = (float) config('importacion.transporte_eur_km', 0.45);
        $transporte = $km * $eurKm;

        // IVA: si el coche es matriculable como usado (>6 meses y >6.000 km
        // desde primera matriculación), tributa en destino a 21% sobre la
        // base imponible ajustada (base = valor en aduana). Aquí usamos una
        // estimación conservadora aplicando 21% sobre el origen.
        $ivaPct = (float) config('importacion.iva_pct', 0.21);
        $iva = $origen * $ivaPct;

        // IEDMT: depende del CO2; estimación conservadora plana configurable.
        $iedmt = (float) config('importacion.iedmt_estimado_eur', 250.0);

        // Gestoría + matriculación.
        $gestoria = (float) config('importacion.gestoria_eur', 220.0);

        return round($transporte + $iva + $iedmt + $gestoria, 2);
    }

    /** @return array<string, float> */
    private static function desglose(?float $origen, ?float $gastos, $car): array
    {
        if ($origen === null || $gastos === null) {
            return [];
        }
        $km = (int) config('importacion.transporte_km_alemania_huelva', 2200);
        $eurKm = (float) config('importacion.transporte_eur_km', 0.45);
        $ivaPct = (float) config('importacion.iva_pct', 0.21);

        return [
            'Precio del anuncio' => round($origen, 2),
            'Transporte Alemania → Huelva' => round($km * $eurKm, 2),
            'IVA importación (estimado)' => round($origen * $ivaPct, 2),
            'IEDMT matriculación (estimado)' => round((float) config('importacion.iedmt_estimado_eur', 250.0), 2),
            'Gestoría + matriculación' => round((float) config('importacion.gestoria_eur', 220.0), 2),
        ];
    }

    private static function fuente(?Esqueleto $esqueleto, ?array $ficha): string
    {
        if (is_array($ficha) && isset($ficha['precio']['origen'])) {
            return 'ficha-cliente.json';
        }
        if ($esqueleto && $esqueleto->uno('PRECIO')) {
            return 'ficha-publicitaria.txt';
        }
        if (! empty($car->purchase_price_eur ?? null)) {
            return 'coche.purchase_price_eur';
        }

        return 'n/a';
    }

    /** Parsea "31.929 €" / "31.929€" / "31929" / "31.929,00 EUR" → float. */
    private static function parseEur(string $texto): ?float
    {
        if (preg_match('/(\d{1,3}(?:[.\s]\d{3})*(?:,\d+)?|\d+(?:[.,]\d+)?)/', $texto, $m) === 1) {
            $num = $m[1];
            $num = str_replace(['.', ' '], ['', ''], $num);
            $num = str_replace(',', '.', $num);
            $f = (float) $num;

            return $f > 0 ? $f : null;
        }

        return null;
    }
}
