<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests del módulo de marketing multicanal (Flujo M, 06-sep-2026).
 *
 * Esta clase NO ejecuta el validador Python (es externo a PHP). Verifica
 * en cambio que la infraestructura de marketing está en su sitio:
 *
 *  - El módulo `07-marketing/` existe con sus 5 archivos principales.
 *  - El validador `scripts/check_marketing.py` existe y es ejecutable.
 *  - Las plantillas v2 están presentes y tienen los placeholders correctos.
 *  - El ejemplo relleno está presente.
 *  - La skill importa el módulo en su SKILL.md, anti_patrones.md y CHANGELOG.md.
 *
 * Para verificar la corrección de los 30 checks del validador, ejecuta
 * manualmente:
 *
 *     py .claude/skills/importacion-vehiculos/scripts/check_marketing.py ^
 *        .claude/skills/importacion-vehiculos/07-marketing/plantillas/ejemplo/redes-sociales-ejemplo.txt ^
 *        .claude/skills/importacion-vehiculos/07-marketing/plantillas/ejemplo/anuncio-portales-ejemplo.txt
 *
 * Resultado esperado: 🔴 0 🟠 0 🟡 0 (EXIT 0).
 */
class MarketingValidatorTest extends TestCase
{
    private string $skillRoot = '.claude/skills/importacion-vehiculos';

    public function test_modulo_07_marketing_existe_con_los_5_archivos_principales(): void
    {
        $archivosEsperados = [
            '07-marketing/README.md',
            '07-marketing/copy_engine.md',
            '07-marketing/redes_sociales.md',
            '07-marketing/portales_anuncio.md',
            '07-marketing/biblioteca_ganchos.md',
            '07-marketing/fuentes_y_evidencia.md',
        ];

        foreach ($archivosEsperados as $relativo) {
            $absoluto = base_path("{$this->skillRoot}/{$relativo}");
            $this->assertFileExists(
                $absoluto,
                "Falta archivo de marketing: {$relativo}",
            );
        }
    }

    public function test_validador_python_existe_y_es_ejecutable(): void
    {
        $absoluto = base_path("{$this->skillRoot}/scripts/check_marketing.py");
        $this->assertFileExists(
            $absoluto,
            'Falta el validador scripts/check_marketing.py',
        );

        // El script debe empezar por shebang para que sea ejecutable en
        // sistemas *nix; en Windows se invoca con `py`.
        $primerasLineas = array_slice(file($absoluto), 0, 3);
        $this->assertStringContainsString(
            'python3',
            implode("\n", $primerasLineas),
            'check_marketing.py debe empezar por shebang #!/usr/bin/env python3',
        );
    }

    public function test_plantillas_v2_tienen_los_bloques_esperados(): void
    {
        $redes = file_get_contents(base_path("{$this->skillRoot}/07-marketing/plantillas/redes-sociales.txt"));
        $portales = file_get_contents(base_path("{$this->skillRoot}/07-marketing/plantillas/anuncio-portales.txt"));

        $this->assertNotFalse($redes, 'No se pudo leer plantillas/redes-sociales.txt');
        $this->assertNotFalse($portales, 'No se pudo leer plantillas/anuncio-portales.txt');

        // Bloques IG obligatorios
        foreach (['IG_GANCHO', 'IG_FICHA', 'IG_PEGA', 'IG_CTA', 'IG_HASHTAGS'] as $bloque) {
            $this->assertStringContainsString(
                "[{$bloque}]",
                $redes,
                "Plantilla redes-sociales.txt debe contener bloque [{$bloque}]",
            );
        }

        // Bloques PT obligatorios
        foreach (['PT_RESUMEN', 'PT_FICHA', 'PT_ESTADO', 'PT_AVISO', 'PT_TITULO_A'] as $bloque) {
            $this->assertStringContainsString(
                "[{$bloque}]",
                $portales,
                "Plantilla anuncio-portales.txt debe contener bloque [{$bloque}]",
            );
        }

        // Aviso legal: A26 obliga a llevar las 6 palabras clave
        $this->assertStringContainsString('JJ Import Motors', $portales);
        $this->assertStringContainsString('Identidad del empresario', $portales);
        $this->assertStringContainsString('NO es propiedad del establecimiento', $portales);
        $this->assertStringContainsString('Precio total cliente', $portales);
        $this->assertStringContainsString('No incluye:', $portales);
        $this->assertStringContainsString('Fecha de primera matriculación', $portales);
    }

    public function test_plantillas_contienen_placeholders_sin_rellenar(): void
    {
        // Las plantillas son plantillas vacías: deben llevar <PLACEHOLDERS>.
        $redes = file_get_contents(base_path("{$this->skillRoot}/07-marketing/plantillas/redes-sociales.txt"));
        $portales = file_get_contents(base_path("{$this->skillRoot}/07-marketing/plantillas/anuncio-portales.txt"));

        $this->assertMatchesRegularExpression('/<[A-Z][A-Z0-9 _\/\\-]+>/', $redes);
        $this->assertMatchesRegularExpression('/<[A-Z][A-Z0-9 _\/\\-]+>/', $portales);
    }

    public function test_ejemplo_relleno_existe(): void
    {
        $this->assertFileExists(base_path("{$this->skillRoot}/07-marketing/plantillas/ejemplo/redes-sociales-ejemplo.txt"));
        $this->assertFileExists(base_path("{$this->skillRoot}/07-marketing/plantillas/ejemplo/anuncio-portales-ejemplo.txt"));
    }

    public function test_skill_md_referencia_el_flujo_m_y_los_companeros_de_marketing(): void
    {
        $skill = file_get_contents(base_path("{$this->skillRoot}/SKILL.md"));

        $this->assertStringContainsString('07-marketing', $skill);
        $this->assertStringContainsString('Flujo M', $skill);
        $this->assertStringContainsString('MARKETING', $skill);
        $this->assertStringContainsString('check_marketing.py', $skill);
    }

    public function test_anti_patrones_md_lleva_las_reglas_a23_a_a30(): void
    {
        $anti = file_get_contents(base_path("{$this->skillRoot}/06-reglas/anti_patrones.md"));

        foreach (range(23, 30) as $n) {
            $this->assertStringContainsString(
                "| **A{$n}**",
                $anti,
                "anti_patrones.md debe contener la regla | **A{$n}** |",
            );
        }
    }

    public function test_changelog_tiene_entrada_3_7_1_con_flujo_m(): void
    {
        $changelog = file_get_contents(base_path("{$this->skillRoot}/CHANGELOG.md"));

        $this->assertStringContainsString('[3.7.1]', $changelog);
        $this->assertStringContainsString('Flujo M', $changelog);
        $this->assertStringContainsString('marketing', $changelog);
    }

    public function test_canonical_plan_en_docs_referenciado_desde_skills_md(): void
    {
        $plan = base_path('docs/PLAN_MARKETING_MULTICANAL_2026-09-06.md');
        $this->assertFileExists($plan);

        $skills = file_get_contents(base_path('docs/SKILLS.md'));
        $this->assertStringContainsString('PLAN_MARKETING_MULTICANAL_2026-09-06', $skills);
    }

    public function test_regla_de_oro_enlaces_en_fuentes_y_evidencia(): void
    {
        $fuentes = file_get_contents(base_path("{$this->skillRoot}/07-marketing/fuentes_y_evidencia.md"));

        // La regla de oro: sin enlace = no cuenta. El documento debe reflejarlo.
        $this->assertStringContainsString('sin enlace', $fuentes);
        $this->assertStringContainsString('mobile.de', $fuentes);
        $this->assertStringContainsString('autoscout24', $fuentes);
        $this->assertStringContainsString('wallapop', $fuentes);
    }
}
