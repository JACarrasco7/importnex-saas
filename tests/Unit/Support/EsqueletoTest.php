<?php

namespace Tests\Unit\Support;

use App\Support\Esqueleto;
use PHPUnit\Framework\TestCase;

/**
 * Regresión 06-sep-2026: preg_split('/\R/', ...) SIN el modificador /u trata
 * la cadena como bytes crudos. \R (sin /u) coincide con el byte 0x85 (NEL)
 * suelto, que es justo el 3er byte de la codificación UTF-8 de ✅ (E2 9C 85)
 * y de muchos otros caracteres multibyte. Sin /u, el split parte el
 * caracter por la mitad y deja bytes huérfanos que ya no son UTF-8 válido.
 */
class EsqueletoTest extends TestCase
{
    public function test_desde_no_corrompe_caracteres_multibyte_con_byte_0x85(): void
    {
        // ✅ (U+2705) se codifica en UTF-8 como E2 9C 85. El byte final 0x85
        // es exactamente el que \R sin /u interpreta como salto de línea NEL.
        $e = Esqueleto::desde("[FACEBOOK_POST_1] ✅ 306 CV\n✅ Cambio automático");

        $texto = $e->uno('FACEBOOK_POST_1');

        $this->assertNotNull($texto);
        $this->assertTrue(mb_check_encoding($texto, 'UTF-8'), 'El texto parseado debe ser UTF-8 válido');
        $this->assertSame("✅ 306 CV\n✅ Cambio automático", $texto);
    }

    public function test_lista_no_corrompe_caracteres_multibyte_con_byte_0x85(): void
    {
        $e = Esqueleto::desde("[HASHTAGS] ✅tag1\n✅tag2");

        $items = $e->lista('HASHTAGS');

        foreach ($items as $item) {
            $this->assertTrue(mb_check_encoding($item, 'UTF-8'), "Item corrupto: {$item}");
        }
        $this->assertSame(['✅tag1', '✅tag2'], $items);
    }

    public function test_desde_parsea_bloques_multilinea_normales(): void
    {
        $e = Esqueleto::desde("[TITULO] Coche de prueba\n[DESCRIPCION] Línea 1\nLínea 2\nLínea 3");

        $this->assertSame('Coche de prueba', $e->uno('TITULO'));
        $this->assertSame("Línea 1\nLínea 2\nLínea 3", $e->uno('DESCRIPCION'));
    }

    public function test_desde_ignora_comentarios(): void
    {
        $e = Esqueleto::desde("# Esto es un comentario\n[TITULO] Valor real");

        $this->assertSame('Valor real', $e->uno('TITULO'));
    }
}
