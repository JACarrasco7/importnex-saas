<?php

namespace Tests\Feature;

use App\Services\ValuationPackageIngestor;
use ReflectionClass;
use Tests\TestCase;

/**
 * B4 auditoría 09-sep-2026: con la BD Forge ya en utf8mb4, el sanitizador
 * ya no debe eliminar emojis 4-byte (🗓️🛣️🐎🏷️) que forman parte del
 * vocabulario canónico de la skill (`contenido/json/redes-sociales.json →
 * canales.instagram_feed.ficha`).
 *
 * Sí mantiene la limpieza de: invisibles (zero-width), controles ASCII,
 * checkmarks ✓ ✅ ✔ ✍ → viñeta "•", y colapso de espacios/puntuación.
 */
class SanitizeForMysqlUtf8Test extends TestCase
{
    public function test_conserva_emojis_4_byte_del_vocabulario_v2(): void
    {
        $method = $this->metodoPrivado();

        // Caso real del JSON v2 del Arteon.
        $entrada = ['ficha' => "🗓️ 2023\n🛣️ 49.420 km\n🐎 320 CV\n🏷️ Etiqueta C\n💶 31.929 €"];
        $salida = $method->invokeArgs($this->instancia(), [$entrada]);

        // Todos los emojis 4-byte deben sobrevivir.
        $this->assertStringContainsString('🗓️', $salida['ficha'], 'B4: 🗓️ debe sobrevivir');
        $this->assertStringContainsString('🛣️', $salida['ficha'], 'B4: 🛣️ debe sobrevivir');
        $this->assertStringContainsString('🐎', $salida['ficha'], 'B4: 🐎 debe sobrevivir');
        $this->assertStringContainsString('🏷️', $salida['ficha'], 'B4: 🏷️ debe sobrevivir');
        $this->assertStringContainsString('💶', $salida['ficha'], 'B4: 💶 debe sobrevivir');
    }

    public function test_conserva_banderas_regionales_y_emojis_pictograficos(): void
    {
        $method = $this->metodoPrivado();

        $salida = $method->invokeArgs($this->instancia(), [['pais' => '🇩🇪 Origen alemán · 🔥 Top ventas · 📍 Huelva']]);
        $this->assertStringContainsString('🇩🇪', $salida['pais'], 'B4: banderas regionales sobreviven');
        $this->assertStringContainsString('🔥', $salida['pais'], 'B4: emojis pictográficos sobreviven');
        $this->assertStringContainsString('📍', $salida['pais'], 'B4: pin location sobrevive');
    }

    public function test_sigue_convirtiendo_checkmarks_a_vinetas(): void
    {
        $method = $this->metodoPrivado();

        $salida = $method->invokeArgs($this->instancia(), [['desc' => '✅ Tracción total · ✓ Buen estado · ✔ Motor revisado']]);
        $this->assertStringContainsString('• Tracción total', $salida['desc']);
        $this->assertStringContainsString('• Buen estado', $salida['desc']);
        $this->assertStringContainsString('• Motor revisado', $salida['desc']);
    }

    public function test_sigue_eliminando_invisibles_y_controles(): void
    {
        $method = $this->metodoPrivado();

        // Zero-width spaces + control chars. El cero-width es invisible y se
        // borra dejando el texto limpio (sin doble espacio). Comprobamos que
        // los caracteres invisibles desaparecen.
        $entrada = ['texto' => "Texto\u{200B} con invisibles\u{FEFF} y controles\u{0001} mezclados"];
        $salida = $method->invokeArgs($this->instancia(), [$entrada]);

        $this->assertStringNotContainsString("\u{200B}", $salida['texto']);
        $this->assertStringNotContainsString("\u{FEFF}", $salida['texto']);
        $this->assertStringNotContainsString("\u{0001}", $salida['texto']);
        // El texto base (sin invisibles) se mantiene.
        $this->assertStringContainsString('Texto', $salida['texto']);
        $this->assertStringContainsString('con invisibles', $salida['texto']);
        $this->assertStringContainsString('mezclados', $salida['texto']);
    }

    public function test_colapsa_espacios_dobles_y_limpia_puntuacion(): void
    {
        $method = $this->metodoPrivado();

        $entrada = ['desc' => 'Texto  con   espacios . doble y espacio , antes de puntuación'];
        $salida = $method->invokeArgs($this->instancia(), [$entrada]);

        $this->assertStringContainsString('Texto con espacios.', $salida['desc']);
        $this->assertStringContainsString('doble y espacio, antes', $salida['desc']);
    }

    private function metodoPrivado(): \ReflectionMethod
    {
        $class = new ReflectionClass(ValuationPackageIngestor::class);
        $method = $class->getMethod('sanitizeForMysql');
        $method->setAccessible(true);

        return $method;
    }

    private function instancia(): ValuationPackageIngestor
    {
        // sanitizeForMysql solo opera sobre arrays, no toca dependencias.
        return app(ValuationPackageIngestor::class);
    }
}
