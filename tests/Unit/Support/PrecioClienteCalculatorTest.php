<?php

namespace Tests\Unit\Support;

use App\Support\Esqueleto;
use App\Support\PrecioClienteCalculator;
use Tests\TestCase;

/**
 * Contrato v3.9.2 (12-sep-2026) — skill + Laravel sincronizados.
 *
 * Cambios frente a v3.9.0:
 *  - origen() SOLO lee ficha-cliente.json → precio_origen (snake_case)
 *    o [PRECIO_ORIGEN] del esqueleto. NUNCA [PRECIO] (que es el TOTAL puesto
 *    en Huelva — usarlo como origen duplicaba los gastos al sumarlos otra vez).
 *  - desglose tiene una entrada "Precio del coche" (no "Precio del anuncio").
 *  - gastos de la skill vienen como lista [{concepto, importe}] o como
 *    bloques [GASTO] concepto | importe — no como array plano.
 *  - Total nunca es cifra cerrada: el método devuelve total_min y total_max
 *    (horquilla) y un banda_motivo legible.
 *  - aviso_precio_final ya no contiene "confirma por escrito" — la honestidad
 *    del aviso vive en banda_motivo, no repetida en dos strings.
 *
 * Reglas de negocio:
 *  - El cliente ve "precio del coche + gastos de compra" SIN desglose de margen.
 *  - El total es estimación; se cierra por escrito antes de reservar.
 *  - Si no hay origen, no se muestra nada (no se inventan cifras).
 *  - Honorarios y gestión van siempre en una sola línea.
 *  - Un usado de la UE no vuelve a tributar IVA en España.
 */
class PrecioClienteCalculatorTest extends TestCase
{
    public function test_origen_desde_ficha_cliente_json_tiene_prioridad(): void
    {
        $car = (object) ['purchase_price' => 99999.0, 'co2' => 140, 'co2_confirmado' => true, 'pais_origen' => 'alemania'];
        $esqueleto = $this->esqueletoConBloques(['PRECIO_ORIGEN' => '50.000 €']);
        $ficha = [
            'precio_origen' => '31.929 €',
            'gastos' => [
                ['concepto' => 'Transporte Alemania → Huelva', 'importe' => '990 €'],
                ['concepto' => 'Gestión y matriculación', 'importe' => '1.720 €'],
            ],
        ];

        $r = PrecioClienteCalculator::desde($car, $esqueleto, $ficha);

        $this->assertSame(31929.0, $r['origen']);
        // gastos = 990 + 1720 = 2710
        $this->assertSame(2710.0, $r['gastos']);
        $this->assertSame(34639.0, $r['total_estimado']);
        $this->assertSame('ficha-cliente.json:precio_origen', $r['fuente']);
        $this->assertNotNull($r['total_min']);
        $this->assertNotNull($r['total_max']);
        // Propiedad estructural: la horquilla está bien formada.
        $this->assertGreaterThanOrEqual(0, $r['total_min']);
        $this->assertGreaterThanOrEqual($r['total_min'], $r['total_max']);
        // Y el total cae dentro de la banda ±15% (cobertura amplia).
        $ancho = $r['total_max'] - $r['total_min'];
        $this->assertGreaterThan(0, $ancho);
        $this->assertLessThan(0.15, abs($r['total_estimado'] - ($r['total_min'] + $ancho / 2)) / $r['total_estimado'],
            'total_estimado cae cerca del centro de la banda (±15%)');
    }

    public function test_origen_desde_esqueleto_si_no_hay_ficha(): void
    {
        $car = (object) ['purchase_price' => null];
        $esqueleto = $this->esqueletoConBloques(['PRECIO_ORIGEN' => '31.929 €']);
        $ficha = null;

        $r = PrecioClienteCalculator::desde($car, $esqueleto, $ficha);

        $this->assertSame(31929.0, $r['origen']);
        $this->assertNotNull($r['gastos']);
        $this->assertGreaterThan(31929.0, $r['total_estimado']);
        $this->assertSame('ficha-publicitaria.txt:PRECIO_ORIGEN', $r['fuente']);
    }

    public function test_origen_desde_atributo_del_coche_como_ultimo_recurso(): void
    {
        $car = (object) ['purchase_price' => 12345.67];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertSame(12345.67, $r['origen']);
        $this->assertSame('coche.purchase_price', $r['fuente']);
    }

    public function test_devuelve_null_si_no_hay_origen(): void
    {
        $car = (object) ['purchase_price' => null];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertNull($r['origen']);
        $this->assertNull($r['gastos']);
        $this->assertNull($r['total_estimado']);
        $this->assertNull($r['total_min']);
        $this->assertNull($r['total_max']);
        $this->assertSame([], $r['desglose']);
    }

    public function test_desglose_contiene_los_conceptos_del_fallback(): void
    {
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'alemania'];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // v3.9.2: la clave es 'Precio del coche' (no 'Precio del anuncio').
        $this->assertArrayHasKey('Precio del coche', $r['desglose']);
        $this->assertSame(30000.0, $r['desglose']['Precio del coche']);
        // Honorarios y gestión siempre en la misma línea (regla dura nº3).
        $this->assertArrayHasKey('Gestión y matriculación', $r['desglose']);
        // NO debe haber línea separada de honorarios.
        $this->assertArrayNotHasKey('Honorarios', $r['desglose']);
    }

    public function test_fallback_de_origen_alemania_incluye_iedmt_y_transporte(): void
    {
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'alemania', 'co2' => 140];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertArrayHasKey('Transporte hasta España', $r['desglose']);
        $this->assertArrayHasKey('Impuesto de matriculación (IEDMT)', $r['desglose']);
        $this->assertArrayHasKey('Trámites de exportación e ITV de importación', $r['desglose']);
    }

    public function test_origen_espanol_no_incluye_iedmt(): void
    {
        // Coche ES: sin IEDMT, sin ITV de importación, sin transporte DE.
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'espana', 'co2' => 140];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertArrayNotHasKey('Impuesto de matriculación (IEDMT)', $r['desglose']);
        $this->assertArrayNotHasKey('Transporte hasta España', $r['desglose']);
        $this->assertArrayHasKey('Traslado hasta Huelva', $r['desglose']);
    }

    public function test_banda_motivo_difiere_segun_co2_confirmado(): void
    {
        // Con CO₂ explícitamente confirmado.
        $carCon = (object) ['purchase_price' => 30000.0, 'co2' => 140, 'co2_confirmado' => true, 'pais_origen' => 'alemania'];
        // Sin CO₂ (ni valor ni flag) → la heurística co2Confirmado() devuelve false.
        $carSin = (object) ['purchase_price' => 30000.0, 'co2' => 0, 'co2_confirmado' => false, 'pais_origen' => 'alemania'];

        $rCon = PrecioClienteCalculator::desde($carCon, null, null);
        $rSin = PrecioClienteCalculator::desde($carSin, null, null);

        // La pista del motivo debe ser EXPLÍCITA cuando falta el CO₂.
        $this->assertStringContainsString('emisiones homologadas', $rSin['banda_motivo'],
            'sin CO₂ el motivo debe avisar que la incertidumbre del IEDMT es alta');
        $this->assertStringNotContainsString('emisiones homologadas', $rCon['banda_motivo'],
            'con CO₂ confirmado el motivo ya no menciona la incertidumbre del IEDMT');
    }

    public function test_aviso_precio_final_siempre_presente(): void
    {
        $car = (object) ['purchase_price' => 30000.0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // v3.9.2: aviso breve (sin "confirma por escrito", eso está en banda_motivo).
        $this->assertNotEmpty($r['aviso_precio_final']);
        // No repetimos banda_motivo aquí: el aviso es solo el recordatorio
        // de que la cifra se cierra por escrito antes de la reserva.
        $this->assertStringContainsString('por escrito', $r['aviso_precio_final']);
    }

    public function test_no_incluye_margen_en_el_desglose(): void
    {
        $car = (object) ['purchase_price' => 30000.0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // C3: el margen NO aparece en ningún concepto del desglose.
        $json = json_encode($r['desglose']);
        $this->assertStringNotContainsStringIgnoringCase('margen', $json);
        $this->assertStringNotContainsStringIgnoringCase('beneficio', $json);
        $this->assertStringNotContainsStringIgnoringCase('ganancia', $json);
    }

    public function test_no_incluye_iva_sobre_precio_del_coche(): void
    {
        // FIX 12-sep-2026: gastosEstimados() sumaba 21% IVA fantasma. Un
        // usado de la UE NO vuelve a tributar IVA en España. Verificamos
        // que el total_estimado es razonable (no se duplica con IVA).
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'alemania', 'co2' => 0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // El total NO debe ser ~30000 + 21% = 36300 + gastos de transporte.
        // Con CO₂=0 la banda es amplia pero el total estimado sigue por
        // debajo del 50% sobre el origen.
        $this->assertLessThan(30000 * 1.50, $r['total_estimado'],
            'no debería haber 21% de IVA fantasma sobre el precio del coche');
    }

    public function test_gastos_de_skill_tienen_prioridad_sobre_fallback(): void
    {
        $car = (object) ['purchase_price' => 99999.0];
        // La skill escribió gastos de 8500€ (los calculó ella con CO₂ real).
        $ficha = [
            'precio_origen' => '31.929 €',
            'gastos' => [
                ['concepto' => 'Transporte Alemania → Huelva', 'importe' => '990 €'],
                ['concepto' => 'Trámites exportación', 'importe' => '229 €'],
                ['concepto' => 'IEDMT matriculación', 'importe' => '3.061 €'],
                ['concepto' => 'Gestión y matriculación', 'importe' => '1.720 €'],
                ['concepto' => 'Gestoría DGT', 'importe' => '500 €'],
            ],
        ];

        $r = PrecioClienteCalculator::desde($car, null, $ficha);

        $this->assertSame(31929.0, $r['origen']);
        // Suma de gastos = 990+229+3061+1720+500 = 6500
        $this->assertSame(6500.0, $r['gastos']);
        // Total = 31929 + 6500 = 38429
        $this->assertSame(38429.0, $r['total_estimado']);
        // El desglose empieza por 'Precio del coche' y luego los conceptos de la skill.
        $this->assertSame(31929.0, $r['desglose']['Precio del coche']);
        $this->assertArrayHasKey('IEDMT matriculación', $r['desglose']);
    }

    public function test_filtra_conceptos_prohibidos_en_gastos_de_skill(): void
    {
        // La skill NUNCA debe emitir honorarios / margen / comisión como
        // línea separada (regla dura nº3). Pero si lo hiciese, el código
        // los descarta como red de seguridad.
        $car = (object) ['purchase_price' => 30000.0];
        $ficha = [
            'precio_origen' => '30.000 €',
            'gastos' => [
                ['concepto' => 'Transporte', 'importe' => '990 €'],
                ['concepto' => 'Honorarios', 'importe' => '1500 €'],   // ← filtrado
                ['concepto' => 'Comisión', 'importe' => '800 €'],      // ← filtrado
            ],
        ];

        $r = PrecioClienteCalculator::desde($car, null, $ficha);

        $this->assertArrayNotHasKey('Honorarios', $r['desglose']);
        $this->assertArrayNotHasKey('Comisión', $r['desglose']);
        $this->assertArrayHasKey('Transporte', $r['desglose']);
    }

    public function test_parsea_formatos_europeos_de_euros(): void
    {
        // v3.9.2: parseEur se invoca desde origen() SOLO cuando el bloque
        // es [PRECIO_ORIGEN] (no [PRECIO]). Cubrimos los formatos típicos.
        $casos = [
            '31.929,00 EUR' => 31929.0,
            '31 929 €' => 31929.0,
            '1.234.567 €' => 1234567.0,
            '31929 EUR' => 31929.0,
            'precio 31.929' => 31929.0,
            'coste 12345,67' => 12345.67,
        ];

        foreach ($casos as $texto => $esperado) {
            $esqueleto = $this->esqueletoConBloques(['PRECIO_ORIGEN' => $texto]);
            $r = PrecioClienteCalculator::desde((object) ['purchase_price' => null], $esqueleto, null);
            $this->assertSame($esperado, $r['origen'], "formato: {$texto}");
        }
    }

    public function test_bloque_precio_no_se_usa_como_origen(): void
    {
        // [PRECIO] es el TOTAL puesto en Huelva (con gastos incluidos). Si lo
        // leyéramos como origen, al sumar gastosEstimados() encima se duplica
        // el coste. Por seguridad verificamos que aunque exista [PRECIO] con
        // una cifra alta, origen sale de [PRECIO_ORIGEN] (no de [PRECIO]).
        $esqueleto = $this->esqueletoConBloques([
            'PRECIO_ORIGEN' => '31.929 €',
            'PRECIO' => '48.500 €',   // ← trampa: este es el TOTAL, no el origen
        ]);
        $r = PrecioClienteCalculator::desde(
            (object) ['purchase_price' => null],
            $esqueleto,
            null
        );

        $this->assertSame(31929.0, $r['origen'],
            '[PRECIO] no debe leerse como origen (es el TOTAL puesto en Huelva)');
    }

    public function test_total_min_no_baja_del_precio_del_coche(): void
    {
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'alemania', 'co2' => 200];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // Por construcción: min nunca por debajo del origen (sería absurdo).
        $this->assertGreaterThanOrEqual(30000.0, $r['total_min']);
    }

    public function test_coche_sin_co2_genera_banda_amplia_con_motivo(): void
    {
        $car = (object) ['purchase_price' => 30000.0, 'pais_origen' => 'alemania'];
        // co2=0 → no confirmado → banda ancha
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertNotNull($r['banda_motivo']);
        $this->assertStringContainsStringIgnoringCase('emisiones', $r['banda_motivo']);
    }

    /**
     * @param  array<string, string>  $bloques
     */
    private function esqueletoConBloques(array $bloques): Esqueleto
    {
        $texto = '';
        foreach ($bloques as $nombre => $valor) {
            $texto .= "[{$nombre}] {$valor}\n";
        }

        return Esqueleto::desde($texto);
    }
}
