<?php

namespace Tests\Unit\Support;

use App\Support\FiltroPublico;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * El enlace /c/{token} es público (se reenvía por WhatsApp). Estas pruebas usan
 * las fugas REALES detectadas en producción el 07-sep-2026 en la ficha de un
 * Mercedes Clase A: si alguna vuelve a pasar, el test cae.
 */
class FiltroPublicoTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function fugasReales(): array
    {
        return [
            'hueco de importación' => ['Ningún A 35 AMG 2021 en España baja de 33.959 € — hueco de 8.969 € (26,4%) antes de costes'],
            'competencia en España' => ['Mercado muy estrecho: solo 11 unidades 2021 en toda España'],
            'vendedor de origen' => ['Vendedor profesional 4.5★, acostumbrado a operaciones de exportación'],
            'score de vendibilidad' => ['Vendibilidad alta (84/100) pese al kilometraje'],
            'veredicto interno' => ['Excelente compra. Un A 35 AMG muy bien equipado'],
            'ahorro estimado' => ['Ahorro estimado de 4.214 € frente al mercado español'],
            'margen' => ['El margen nos permite dejarlo a este precio'],
            'anuncio de origen' => ['Ver el anuncio original en mobile.de'],
            'importe de honorarios' => ['Honorarios de 1.500 € por la gestión'],
        ];
    }

    /**
     * @dataProvider fugasReales
     */
    #[DataProvider('fugasReales')]
    public function test_bloquea_las_fugas_detectadas_en_produccion(string $linea): void
    {
        $this->assertFalse(
            FiltroPublico::permitida($linea),
            "Esta línea NO puede publicarse en la ficha del cliente: {$linea}"
        );
        $this->assertNotEmpty(FiltroPublico::motivos($linea));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lenguajeDeVendedor(): array
    {
        return [
            'vendemos' => ['Vendemos este coche con todas las garantías'],
            'se vende' => ['Se vende Mercedes Clase A en perfecto estado'],
            'nuestro coche' => ['Nuestro coche está listo para entrega'],
            'concesionario' => ['Ven a verlo a nuestro concesionario'],
            'garantía propia' => ['El coche va con garantía de 12 meses'],
            'garantizamos' => ['Garantizamos el estado mecánico del vehículo'],
        ];
    }

    /**
     * @dataProvider lenguajeDeVendedor
     */
    #[DataProvider('lenguajeDeVendedor')]
    public function test_bloquea_el_lenguaje_de_vendedor_y_de_garantia(string $linea): void
    {
        $this->assertFalse(FiltroPublico::permitida($linea), "A31: {$linea}");
    }

    public function test_permite_el_texto_que_si_va_al_cliente(): void
    {
        $permitidas = [
            'Techo panorámico, sonido Burmester y MBUX con realidad aumentada',
            'Tracción integral 4MATIC: utilizable todo el año',
            'Origen y kilometraje confirmados',
            'Libro de mantenimiento sellado y revisiones al día',
            'No vendemos coches: JJ Import Motors no es el vendedor del vehículo',
            'JJ Import Motors no ofrece garantía sobre el vehículo',
            'Neumáticos al 50 %: se sustituyen antes de la entrega',
        ];

        foreach ($permitidas as $linea) {
            $this->assertTrue(FiltroPublico::permitida($linea), "Debería publicarse: {$linea}");
        }
    }

    public function test_filtra_listas_y_conserva_el_orden(): void
    {
        $entrada = [
            'Equipamiento por encima de la media',
            'Vendibilidad alta (84/100)',
            'Tracción integral',
            'Hueco de 8.969 € antes de costes',
        ];

        $this->assertSame(
            ['Equipamiento por encima de la media', 'Tracción integral'],
            FiltroPublico::lista($entrada)
        );
    }

    public function test_texto_devuelve_null_cuando_no_es_publicable(): void
    {
        $this->assertNull(FiltroPublico::texto('Vendibilidad 84/100'));
        $this->assertSame('Etiqueta C', FiltroPublico::texto('  Etiqueta C  '));
    }

    /**
     * Fugas adicionales detectadas en la auditoría 09-sep-2026 sobre la
     * ficha del Arteon: cifras en euros del mercado alemán, banda/cuartil
     * de origen, "mercado español muy estrecho" tolerante a palabras
     * intermedias, escasez, y referencias a la banda de compra.
     *
     * @return array<string, array{0: string}>
     */
    public static function fugasAuditoria09Sep(): array
    {
        return [
            'cifra en euros (precio aleman)' => ['Precio en cuartil bajo de la banda alemana (31.929 € vs 31.000-34.685 €)'],
            'banda alemana' => ['En banda baja del mercado alemán para Shooting Brake R'],
            'cuartil bajo' => ['Cuartil bajo del comparador alemán'],
            'mercado aleman' => ['Mercado alemán: 28 unidades similares en stock'],
            'mercado espanol muy estrecho' => ['Mercado español muy estrecho (solo 7 unidades) → alta escasez'],
            'mercado segunda mano estrecho' => ['Mercado de segunda mano muy estrecho en 2023'],
            'alta escasez' => ['Alta escasez de esta motorización en España'],
            'cifra EUR suelta' => ['Coste en origen: 31.929 €'],
        ];
    }

    /**
     * @dataProvider fugasAuditoria09Sep
     */
    #[DataProvider('fugasAuditoria09Sep')]
    public function test_bloquea_fugas_adicionales_de_la_auditoria_09_sep(string $linea): void
    {
        $this->assertFalse(
            FiltroPublico::permitida($linea),
            "Auditoría 09-sep-2026: esta línea NO puede publicarse: {$linea}"
        );
        $this->assertNotEmpty(FiltroPublico::motivos($linea));
    }
}
