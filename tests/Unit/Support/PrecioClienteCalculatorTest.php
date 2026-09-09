<?php

namespace Tests\Unit\Support;

use App\Support\Esqueleto;
use App\Support\PrecioClienteCalculator;
use Tests\TestCase;

/**
 * C3 auditoría 09-sep-2026: precio que ve el cliente en /c/{token}.
 *
 * Reglas:
 *  - El cliente ve "precio del anuncio + gastos de compra" SIN desglose de margen.
 *  - El total es estimación; se confirma por escrito antes de la reserva.
 *  - Si no hay origen, no se muestra nada (no se inventan cifras).
 *  - El desglose de gastos es transparente: transporte + IVA + IEDMT + gestoría.
 */
class PrecioClienteCalculatorTest extends TestCase
{
    public function test_origen_desde_ficha_cliente_json_tiene_prioridad(): void
    {
        $car = (object) ['purchase_price_eur' => 99999.0];
        $esqueleto = $this->esqueletoConBloques(['PRECIO' => '50.000 €']);
        $ficha = ['precio' => ['origen' => 31929.0, 'gastos' => 8500.0]];

        $r = PrecioClienteCalculator::desde($car, $esqueleto, $ficha);

        $this->assertSame(31929.0, $r['origen']);
        $this->assertSame(8500.0, $r['gastos']);
        $this->assertSame(40429.0, $r['total_estimado']);
        $this->assertSame('ficha-cliente.json', $r['fuente']);
    }

    public function test_origen_desde_esqueleto_si_no_hay_ficha(): void
    {
        $car = (object) ['purchase_price_eur' => null];
        $esqueleto = $this->esqueletoConBloques(['PRECIO' => '31.929 €']);
        $ficha = null;

        $r = PrecioClienteCalculator::desde($car, $esqueleto, $ficha);

        $this->assertSame(31929.0, $r['origen']);
        $this->assertNotNull($r['gastos']);
        $this->assertGreaterThan(31929.0, $r['total_estimado']);
    }

    public function test_origen_desde_atributo_del_coche_como_ultimo_recurso(): void
    {
        $car = (object) ['purchase_price_eur' => 12345.67];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertSame(12345.67, $r['origen']);
    }

    public function test_devuelve_null_si_no_hay_origen(): void
    {
        $car = (object) ['purchase_price_eur' => null];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertNull($r['origen']);
        $this->assertNull($r['gastos']);
        $this->assertNull($r['total_estimado']);
        $this->assertSame([], $r['desglose']);
    }

    public function test_desglose_contiene_los_conceptos_esperados(): void
    {
        $car = (object) ['purchase_price_eur' => 30000.0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertArrayHasKey('Precio del anuncio', $r['desglose']);
        $this->assertArrayHasKey('Transporte Alemania → Huelva', $r['desglose']);
        $this->assertArrayHasKey('IVA importación (estimado)', $r['desglose']);
        $this->assertArrayHasKey('IEDMT matriculación (estimado)', $r['desglose']);
        $this->assertArrayHasKey('Gestoría + matriculación', $r['desglose']);
        $this->assertSame(30000.0, $r['desglose']['Precio del anuncio']);
    }

    public function test_aviso_precio_final_siempre_presente(): void
    {
        $car = (object) ['purchase_price_eur' => 30000.0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        $this->assertStringContainsString('confirma por escrito', $r['aviso_precio_final']);
        $this->assertStringContainsString('estimación', $r['aviso_precio_final']);
    }

    public function test_no_incluye_margen_en_el_desglose(): void
    {
        $car = (object) ['purchase_price_eur' => 30000.0];
        $r = PrecioClienteCalculator::desde($car, null, null);

        // C3: el margen NO aparece en ningún concepto del desglose.
        $this->assertStringNotContainsStringIgnoringCase('margen', json_encode($r['desglose']));
        $this->assertStringNotContainsStringIgnoringCase('beneficio', json_encode($r['desglose']));
        $this->assertStringNotContainsStringIgnoringCase('ganancia', json_encode($r['desglose']));
    }

    public function test_parsea_formatos_europeos_de_euros(): void
    {
        $car = (object) ['purchase_price_eur' => null];
        $esqueleto = $this->esqueletoConBloques(['PRECIO' => '31.929,00 EUR']);
        $r = PrecioClienteCalculator::desde($car, $esqueleto, null);
        $this->assertSame(31929.0, $r['origen']);

        $esqueleto = $this->esqueletoConBloques(['PRECIO' => '31 929 €']);
        $r = PrecioClienteCalculator::desde($car, $esqueleto, null);
        $this->assertSame(31929.0, $r['origen']);
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
