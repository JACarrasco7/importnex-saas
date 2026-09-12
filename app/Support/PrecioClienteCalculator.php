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
 * FIX doble-precio (auditoría 12-sep-2026): antes, `origen()` caía a
 * `[PRECIO]` de `ficha-publicitaria.txt`, pero ese bloque es el TOTAL
 * puesto en Huelva (con transporte/IEDMT/honorarios ya sumados) según
 * `empaquetar.py`. Al sumarle aquí `gastosEstimados()` otra vez, el cliente
 * veía el coste duplicado. Desde v3.9.0 del skill, `empaquetar.py` emite
 * un bloque `[PRECIO_ORIGEN]` inequívoco (precio del anuncio, sin gastos) y
 * `origen()` SOLO lee ese bloque — nunca `[PRECIO]`.
 *
 * Fuentes de `origen` (por orden de prioridad):
 *  1) `ficha-cliente.json → precio_origen` (string EUR escrita por la skill).
 *  2) `contenido/ficha-publicitaria.txt → [PRECIO_ORIGEN]`.
 *  3) `car->purchase_price` (fuente más débil, último recurso).
 *  4) Si no hay origen → devuelve null y el blade oculta el desglose.
 *
 * El IVA/IEDMT se calcula sobre el origen aplicando tipo general (21%),
 * redondeado al EUR. El transporte se estima por km en camión cerrado
 * desde Alemania hasta Huelva (config `importacion.transporte_eur_km`).
 * La matriculación/gestoría usa una tarifa plana de config.
 *
 * Guardarraíl anti-duplicado: si la skill también declaró un total propio
 * (`ficha['precio']` o `[PRECIO]`) y `origen + gastos` se desvía de ese
 * total más de un 15%, se descarta el cálculo propio de gastos y se confía
 * en el total de la skill (ver `totalDeSkill()` / `desde()`).
 */
class PrecioClienteCalculator
{
    /**
     * Anchos de incertidumbre por componente (decisión usuario, 12-sep-2026:
     * "los gastos siempre aproximados, no es fijo por varias causas").
     *
     * No es un porcentaje global inventado: cada partida tiene su propia
     * horquilla real, y la suma de las horquillas es el ancho de la banda.
     *  - Transporte: varía por ruta, fecha y disponibilidad de camión.
     *  - IEDMT: depende del CO₂ homologado. Si el CO₂ NO está confirmado
     *    (se confirma con el COC), la estimación puede irse mucho, así que
     *    la horquilla de esa partida es enorme a propósito.
     *  - IVA y gestoría: tarifas conocidas, casi sin variación.
     */
    private const BANDA_TRANSPORTE = 0.15;

    private const BANDA_IEDMT_CO2_CONFIRMADO = 0.10;

    private const BANDA_IEDMT_CO2_SIN_CONFIRMAR = 0.60;

    private const BANDA_GESTORIA = 0.05;

    /** Cuando el total viene de la skill (que sí calculó el IEDMT real por CO₂). */
    private const BANDA_TOTAL_DE_SKILL = 0.03;

    /** @return array{
     *     origen: float|null,
     *     gastos: float|null,
     *     total_estimado: float|null,
     *     total_min: float|null,
     *     total_max: float|null,
     *     banda_motivo: string|null,
     *     desglose: array<string, float>,
     *     moneda: string,
     *     fuente: string,
     *     aviso_precio_final: string
     * } */
    public static function desde($car, ?Esqueleto $esqueleto, ?array $ficha): array
    {
        $origen = self::origen($car, $esqueleto, $ficha);

        // 1) Lo mejor: el desglose que la propia skill escribió en el ZIP
        //    (bloques [GASTO]/[FC_GASTO]). Ya trae el IEDMT real calculado
        //    sobre el CO₂ y los honorarios fundidos con la gestoría.
        $desgloseSkill = self::desgloseDeSkill($esqueleto, $ficha);

        if ($desgloseSkill !== []) {
            $gastos = round(array_sum($desgloseSkill), 2);
            $desglose = $origen !== null
                ? ['Precio del coche' => round($origen, 2)] + $desgloseSkill
                : $desgloseSkill;
            $gastosAjustados = false;
        } else {
            // 2) Fallback: estimación propia. Solo para coches cuyo ZIP no
            //    trae el desglose (importados antes del 12-sep-2026).
            $gastos = $origen !== null ? self::gastosEstimados($origen, $car) : null;
            $desglose = self::desglose($origen, $gastos, $car);
            $gastosAjustados = false;
        }

        $total = ($origen !== null && $gastos !== null) ? ($origen + $gastos) : null;

        // Guardarraíl anti-duplicado (auditoría 12-sep-2026): si la skill
        // también publicó un total propio ([PRECIO] / ficha['precio']) y
        // nuestro origen+gastos se desvía de ese total más de un 15%, algo
        // no cuadra (ZIP antiguo sin [PRECIO_ORIGEN], dato a medio migrar,
        // etc.) — confiamos en el total de la skill en vez de mostrar una
        // cifra propia que puede estar duplicando gastos, y recalculamos
        // "gastos" como la diferencia para que el desglose siga sumando
        // exactamente al total mostrado.
        $totalSkill = self::totalDeSkill($esqueleto, $ficha);
        if ($totalSkill !== null && $total !== null && $totalSkill > 0) {
            $desviacion = abs($total - $totalSkill) / $totalSkill;
            if ($desviacion > 0.15) {
                $total = $totalSkill;
                $gastos = $origen !== null ? round($totalSkill - $origen, 2) : null;
                $gastosAjustados = true;
                // El desglose itemizado ya no sumaría lo mismo que $gastos:
                // se colapsa a un único concepto para no mostrar números que
                // no cuadran entre sí.
                $desglose = self::desgloseAjustado($origen, $gastos);
            }
        } elseif ($totalSkill !== null && $total === null) {
            // No teníamos origen/gastos propios pero sí un total de la skill.
            $total = $totalSkill;
        }

        [$totalMin, $totalMax, $bandaMotivo] = self::horquilla($total, $origen, $car, $gastosAjustados);

        return [
            'origen' => $origen,
            'gastos' => $gastos,
            'total_estimado' => $total,
            'total_min' => $totalMin,
            'total_max' => $totalMax,
            'banda_motivo' => $bandaMotivo,
            'desglose' => $desglose,
            'moneda' => 'EUR',
            'fuente' => self::fuente($car, $esqueleto, $ficha),
            // OJO: no repetir aquí lo que ya dice `banda_motivo` (el blade
            // pinta los dos seguidos y quedaba duplicado).
            'aviso_precio_final' => 'La cifra se cierra por escrito antes de que reserves nada.',
        ];
    }

    /**
     * Desglose de gastos tal y como lo escribió la skill en el ZIP.
     *
     * Fuente preferente: bloques `[GASTO] concepto | importe` de
     * `ficha-publicitaria.txt` (o `gastos` en `ficha-cliente.json`). La skill
     * ya aplicó el modelo de costes del negocio (`04-negocio/costes.md`): sin
     * IVA — un usado de la UE no vuelve a tributar IVA en España — con el
     * IEDMT real según CO₂, y con los honorarios fundidos en "Gestión y
     * matriculación" para no romper la regla dura nº3.
     *
     * @return array<string, float>
     */
    private static function desgloseDeSkill(?Esqueleto $esqueleto, ?array $ficha): array
    {
        $filas = [];

        // a) ficha-cliente.json → gastos: [{concepto, importe}, ...]
        if (is_array($ficha) && ! empty($ficha['gastos']) && is_array($ficha['gastos'])) {
            foreach ($ficha['gastos'] as $fila) {
                if (! is_array($fila)) {
                    continue;
                }
                $concepto = trim((string) ($fila['concepto'] ?? ''));
                $importe = self::parseEur((string) ($fila['importe'] ?? ''));
                if ($concepto !== '' && $importe !== null) {
                    $filas[$concepto] = round($importe, 2);
                }
            }
        }

        // b) ficha-publicitaria.txt → [GASTO] concepto | importe
        if ($filas === [] && $esqueleto) {
            foreach ($esqueleto->todos('GASTO') as $linea) {
                $trozos = array_map('trim', explode('|', (string) $linea, 2));
                if (count($trozos) !== 2 || $trozos[0] === '') {
                    continue;
                }
                $importe = self::parseEur($trozos[1]);
                if ($importe !== null) {
                    $filas[$trozos[0]] = round($importe, 2);
                }
            }
        }

        // Red de seguridad: si alguna línea llegase etiquetada como honorarios
        // (no debería — la skill las funde), no se publica.
        foreach (array_keys($filas) as $concepto) {
            if (preg_match('/honorario|margen|comisi(ó|o)n/iu', (string) $concepto) === 1) {
                unset($filas[$concepto]);
            }
        }

        return $filas;
    }

    /**
     * Horquilla del total (decisión usuario 12-sep-2026: el precio del cliente
     * NUNCA es una cifra cerrada — ver también `07-marketing/ficha_cliente.md`
     * §3.5 del skill, que prohíbe "precio final" porque no somos el vendedor).
     *
     * El ancho NO es un porcentaje global: se suma la incertidumbre real de
     * cada partida variable (transporte, IEDMT según CO₂, gestoría). Así, en
     * cuanto el CO₂ está confirmado la horquilla se estrecha sola, sin tener
     * que tocar nada.
     *
     * @return array{0: float|null, 1: float|null, 2: string|null}
     */
    private static function horquilla(?float $total, ?float $origen, $car, bool $gastosAjustados): array
    {
        if ($total === null || $total <= 0) {
            return [null, null, null];
        }

        $co2Confirmado = self::co2Confirmado($car);

        if ($gastosAjustados) {
            // El total lo calculó la skill con el IEDMT real: banda estrecha.
            $margen = $total * self::BANDA_TOTAL_DE_SKILL;
            $motivo = 'Incluye el impuesto de matriculación calculado sobre las emisiones reales del vehículo.';
        } else {
            // Banda calculada partida a partida sobre la estimación propia.
            $partidas = $origen !== null ? self::partidasEstimadas($origen, $car) : [];

            $bandaIedmt = $co2Confirmado
                ? self::BANDA_IEDMT_CO2_CONFIRMADO
                : self::BANDA_IEDMT_CO2_SIN_CONFIRMAR;

            $margen = 0.0;
            foreach ($partidas as $concepto => $importe) {
                $margen += $importe * match (true) {
                    str_contains($concepto, 'IEDMT') => $bandaIedmt,
                    str_contains($concepto, 'Transporte') || str_contains($concepto, 'Traslado') => self::BANDA_TRANSPORTE,
                    default => self::BANDA_GESTORIA,
                };
            }

            $motivo = match (true) {
                // Un coche ya matriculado en España no paga IEDMT: la única
                // variación es el traslado y la transferencia.
                self::esOrigenEspana($car) => 'La horquilla recoge la variación del traslado y de '
                    .'los trámites de transferencia.',
                $co2Confirmado => 'La horquilla recoge la variación del transporte y de las tasas '
                    .'de matriculación.',
                default => 'La horquilla es más amplia porque el impuesto de matriculación depende '
                    .'de las emisiones homologadas, que se confirman con el COC del vehículo.',
            };
        }

        // Redondeo hacia fuera a centenas: una horquilla se lee mejor en
        // números redondos ("48.600 – 49.200 €") que al euro exacto.
        $min = floor(($total - $margen) / 100) * 100;
        $max = ceil(($total + $margen) / 100) * 100;

        // Nunca por debajo del precio del propio coche: sería absurdo.
        if ($origen !== null && $min < $origen) {
            $min = floor($origen / 100) * 100;
        }

        return [(float) max(0, $min), (float) $max, $motivo];
    }

    /**
     * ¿Tenemos el CO₂ homologado confirmado? De eso depende lo fina que pueda
     * ser la estimación del IEDMT.
     */
    private static function co2Confirmado($car): bool
    {
        if (! empty($car->co2_confirmado)) {
            return true;
        }

        return ! empty($car->co2) && (float) $car->co2 > 0;
    }

    private static function origen($car, ?Esqueleto $esqueleto, ?array $ficha): ?float
    {
        // 1) ficha-cliente.json → precio_origen (string EUR, ej. "31.929 €")
        if (is_array($ficha) && ! empty($ficha['precio_origen']) && is_string($ficha['precio_origen'])) {
            $n = self::parseEur($ficha['precio_origen']);
            if ($n !== null && $n > 0) {
                return $n;
            }
        }

        // 2) ficha-publicitaria.txt → [PRECIO_ORIGEN] (precio del anuncio,
        // SIN gastos). OJO: nunca leer [PRECIO] aquí — ese bloque es el
        // TOTAL puesto en Huelva y sumarle gastos encima duplica el coste.
        if ($esqueleto) {
            $bloque = $esqueleto->uno('PRECIO_ORIGEN');
            if ($bloque !== null && ($n = self::parseEur($bloque)) !== null) {
                return $n;
            }
        }

        // 3) purchase_price del coche (decimal 10,2 en BD). Es la fuente más
        // débil: a veces está en EUR, a veces en otra moneda según el flujo.
        // Solo la usamos si es claramente positiva.
        if (! empty($car->purchase_price) && (float) $car->purchase_price > 0) {
            return (float) $car->purchase_price;
        }

        return null;
    }

    /**
     * Total que la propia skill considera "puesto en Huelva" (con gastos y
     * honorarios ya incluidos), usado solo como guardarraíl de coherencia —
     * nunca como fuente de `origen`.
     */
    private static function totalDeSkill(?Esqueleto $esqueleto, ?array $ficha): ?float
    {
        if (is_array($ficha) && ! empty($ficha['precio']) && is_string($ficha['precio'])) {
            $n = self::parseEur($ficha['precio']);
            if ($n !== null && $n > 0) {
                return $n;
            }
        }

        if ($esqueleto) {
            $bloque = $esqueleto->uno('PRECIO');
            if ($bloque !== null && ($n = self::parseEur($bloque)) !== null && $n > 0) {
                return $n;
            }
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
     * Estimación propia de gastos — SOLO fallback para coches cuyo ZIP no
     * trae el desglose de la skill (ver `desgloseDeSkill()`).
     *
     * Modelo: `04-negocio/costes.md` §Fórmula del precio final.
     *
     *   Origen DE → transporte 900 + ausfuhr 114 + ITV import/DGT 115
     *               + IEDMT + honorarios
     *   Origen ES → traslado + gestoría 150 + tarifa de gestión ES ~500
     *               (sin transporte DE, sin ausfuhr, sin ITV de importación
     *               y SIN IEDMT: el coche ya está matriculado en España)
     *
     * ⚠️ Corrección 12-sep-2026: esta función sumaba un **21 % de IVA** sobre
     * el precio del coche. El modelo del negocio no lo contempla y es correcto
     * que no lo haga — un vehículo usado comprado en la UE no vuelve a tributar
     * IVA en España. En el Arteon eso inflaba la estimación ~6.700 €.
     */
    private static function gastosEstimados(float $origen, $car): float
    {
        return round(array_sum(self::partidasEstimadas($origen, $car)), 2);
    }

    /**
     * Las partidas del fallback, ya agrupadas como se le muestran al cliente
     * (gestoría y honorarios en una sola línea: regla dura nº3).
     *
     * @return array<string, float>
     */
    private static function partidasEstimadas(float $origen, $car): array
    {
        if (self::esOrigenEspana($car)) {
            return [
                'Traslado hasta Huelva' => round((float) config('importacion.traslado_es_eur', 250.0), 2),
                'Gestión, transferencia y matriculación' => round(
                    (float) config('importacion.gestoria_es_eur', 150.0)
                    + (float) config('importacion.tarifa_gestion_es_eur', 500.0), 2),
            ];
        }

        $km = (int) config('importacion.transporte_km_alemania_huelva', 2200);
        $eurKm = (float) config('importacion.transporte_eur_km', 0.45);
        $transporte = $km * $eurKm > 0 ? $km * $eurKm : 900.0;

        return [
            'Transporte hasta España' => round($transporte, 2),
            'Trámites de exportación e ITV de importación' => round(
                (float) config('importacion.ausfuhr_eur', 114.0)
                + (float) config('importacion.itv_importacion_eur', 115.0), 2),
            'Impuesto de matriculación (IEDMT)' => round(self::iedmtEstimado($origen, $car), 2),
            'Gestión y matriculación' => round(
                (float) config('importacion.gestoria_eur', 220.0)
                + (float) config('importacion.honorarios_eur', 1500.0), 2),
        ];
    }

    /**
     * IEDMT aproximado cuando el ZIP no lo trae calculado.
     *
     * La fórmula buena (Orden HAC/1501/2025: valor de tabla × coeficiente de
     * antigüedad → minoración art.69 → base × tipo CO₂) la aplica la skill con
     * los datos reales. Aquí solo se necesita un orden de magnitud para que la
     * horquilla no mienta, así que se usa el tipo por tramo de CO₂ sobre el
     * precio del coche. Si no hay CO₂, se asume el tramo medio y la horquilla
     * se ensancha sola (ver `horquilla()`).
     */
    private static function iedmtEstimado(float $origen, $car): float
    {
        $co2 = (float) ($car->co2 ?? 0);

        $tipo = match (true) {
            $co2 <= 0 => 0.0975,   // sin dato: tramo medio, con horquilla ancha
            $co2 <= 120 => 0.0,
            $co2 <= 159 => 0.0475,
            $co2 <= 199 => 0.0975,
            default => 0.1475,
        };

        return $origen * $tipo;
    }

    private static function esOrigenEspana($car): bool
    {
        $pais = strtolower(trim((string) ($car->pais_origen ?? $car->origin_country ?? '')));

        return $pais !== '' && (
            str_contains($pais, 'espa') || $pais === 'es'
        );
    }

    /** @return array<string, float> */
    private static function desglose(?float $origen, ?float $gastos, $car): array
    {
        if ($origen === null || $gastos === null) {
            return [];
        }

        return ['Precio del coche' => round($origen, 2)] + self::partidasEstimadas($origen, $car);
    }

    /** @return array<string, float> */
    private static function desgloseAjustado(?float $origen, ?float $gastos): array
    {
        if ($origen === null || $gastos === null) {
            return [];
        }

        return [
            'Precio del coche' => round($origen, 2),
            'Transporte, impuestos y gestión' => round($gastos, 2),
        ];
    }

    private static function fuente($car, ?Esqueleto $esqueleto, ?array $ficha): string
    {
        if (is_array($ficha) && ! empty($ficha['precio_origen'])) {
            return 'ficha-cliente.json:precio_origen';
        }
        if ($esqueleto && $esqueleto->uno('PRECIO_ORIGEN')) {
            return 'ficha-publicitaria.txt:PRECIO_ORIGEN';
        }
        if (! empty($car->purchase_price ?? null)) {
            return 'coche.purchase_price';
        }

        return 'n/a';
    }

    /**
     * Parsea "31.929 €" / "31.929€" / "31929" / "31.929,00 EUR" / "1.234.567 €" → float.
     *
     * Estrategia (auditoría 09-sep-2026, fix #1): exigir CONTEXTO de moneda
     * (€|EUR) o palabra clave (precio|coste|importe) adyacente al número.
     * Esto evita capturar años (2023, 2024) o fechas (2024-01-15) que aparecen
     * sueltos en el mismo bloque [PRECIO].
     *
     *   [PRECIO]
     *   31.929 €
     *   2023             ← antes capturaba 2023 (mal), ahora lo ignora
     */
    private static function parseEur(string $texto): ?float
    {
        // 1) EUR explícito adyacente: "31.929 €", "31.929,00 EUR", "31929€".
        //    Matchea número (con o sin separadores de miles) seguido de €|EUR.
        //    OJO: NO usamos flag `i` aquí. PCRE 10.x tiene un bug con `\b`
        //    multibyte + case-insensitive que hace que la regex no matchee
        //    strings como "31.929 €" (devuelve null). `€` y `EUR` ya son
        //    case-insensitive al coincidir con el carácter Unicode exacto.
        if (preg_match('/(\d{1,3}(?:[.\s]\d{3})+(?:,\d+)?|\d{4,}(?:[.,]\d+)?|\d{1,3})\s*(?:€|EUR)/u', $texto, $m) === 1) {
            $num = preg_replace('/[.\s]/', '', $m[1]) ?? $m[1];
            $num = str_replace(',', '.', $num);
            $f = (float) $num;
            if ($f > 0) {
                return $f;
            }
        }
        // 2) Palabra clave "precio|coste|importe" antes de un número (algunos
        //    bloques no usan €, e.g. "[PRECIO] 31.929"). Aquí sí podemos
        //    usar `i` porque no hay `\b` contra multibyte.
        if (preg_match('/(?:precio|coste|importe)\D{0,12}(\d{1,3}(?:[.\s]\d{3})+(?:,\d+)?|\d{4,}(?:[.,]\d+)?|\d{1,3})/iu', $texto, $m) === 1) {
            $num = preg_replace('/[.\s]/', '', $m[1]) ?? $m[1];
            $num = str_replace(',', '.', $num);
            $f = (float) $num;
            if ($f > 0) {
                return $f;
            }
        }

        return null;
    }
}
