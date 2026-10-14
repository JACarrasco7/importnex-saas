<?php

namespace Tests\Unit;

use App\Models\Car;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalizacion de color a la paleta base para el filtro publico del
 * marketplace. La BD guarda el nombre comercial aleman completo
 * ("Azul Lapislazuli metalizado (Lapis-Blau)"), que como <option> de un
 * <select> es inservible: el cliente busca "Azul".
 */
class CarBaseColorTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function colorProvider(): array
    {
        return [
            // Casos reales del catalogo (vistos en produccion 14-oct-2026).
            'azul con nombre comercial' => ['Azul Lapislázuli metalizado (Lapis-Blau)', 'Azul'],
            'azul metalico' => ['Azul Metálico', 'Azul'],
            'blanco con nombre comercial' => ['Blanco Oryx metalizado (ORYXWEISS)', 'Blanco'],
            'blanco puro' => ['Blanco (Pure White liso)', 'Blanco'],
            'negro nacarado' => ['Negro Deep Black nacarado', 'Negro'],
            'negro grenadilla' => ['Negro Grenadilla metalizado', 'Negro'],
            'gris con codigo' => ['Gris (GREY)', 'Gris'],
            'gris montana' => ['Gris Montaña metalizado', 'Gris'],
            'naranja tropical' => ['Naranja metálico (Tropical Orange)', 'Naranja'],
            'verde kyalami' => ['Verde Kyalami', 'Verde'],

            // Formas simples.
            'ya es base' => ['Negro', 'Negro'],
            'minusculas' => ['negro', 'Negro'],
            'con espacios extra' => ['  Gris  ', 'Gris'],
            'marron sin acento' => ['Marron', 'Marrón'],
            'marron con acento' => ['Marrón', 'Marrón'],

            // No debe colar nada raro.
            'vacio' => ['', null],
            'null' => [null, null],
            'desconocido' => ['Cobre satinado', null],
            'texto libre' => ['Sin especificar', null],
        ];
    }

    #[DataProvider('colorProvider')]
    public function test_base_color_normalizes_to_palette(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Car::baseColor($input));
    }

    public function test_palette_has_no_duplicates(): void
    {
        $this->assertSame(
            Car::COLOR_PALETTE,
            array_values(array_unique(Car::COLOR_PALETTE)),
            'La paleta no debe tener colores repetidos'
        );
    }

    public function test_every_palette_entry_maps_to_itself(): void
    {
        foreach (Car::COLOR_PALETTE as $color) {
            $this->assertSame(
                $color,
                Car::baseColor($color),
                "El color base {$color} debe normalizarse a si mismo"
            );
        }
    }
}
