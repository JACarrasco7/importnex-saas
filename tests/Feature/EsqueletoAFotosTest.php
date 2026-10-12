<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * FIX 3.9.12 (23-sep-2026): `esqueleto_a_json.py` ahora incluye `FC_FOTOS`
 * en `LISTAS` para que la ficha-cliente.json lleve el array de fotos.
 */
class EsqueletoAFotosTest extends TestCase
{
    public function test_fc_fotos_aparece_en_el_json_de_ficha_cliente(): void
    {
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'esq_test_'.uniqid();
        @mkdir($tmp, 0755, true);
        $txtPath = $tmp.DIRECTORY_SEPARATOR.'ficha-cliente.txt';

        file_put_contents($txtPath,
            "[FC_TITULO] Test Coche\n".
            "[FC_FOTOS] 001.jpg\n".
            "[FC_FOTOS] 002.jpg\n".
            "[FC_FOTOS] 003.jpg\n"
        );

        $script = base_path('.claude/skills/importacion-vehiculos/scripts/esqueleto_a_json.py');
        $this->assertFileExists($script);

        $output = [];
        $rc = 0;
        $python = 'C:\\Users\\jacar\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
        if (! file_exists($python)) {
            $this->markTestSkipped('Python no encontrado en '.$python);
        }
        exec(escapeshellarg($python).' '.escapeshellarg($script).' '.escapeshellarg($txtPath).' 2>&1', $output, $rc);

        $jsonGenerado = $tmp.DIRECTORY_SEPARATOR.'ficha-cliente.json';
        $this->assertFileExists(
            $jsonGenerado,
            'Script no generó JSON. Output: '.implode("\n", $output)."\nrc=$rc"
        );

        $data = json_decode(file_get_contents($jsonGenerado), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('fotos', $data['ficha'], 'ficha.fotos debe existir (FIX v3.9.12)');
        $this->assertCount(3, $data['ficha']['fotos']);
        $this->assertSame(['001.jpg', '002.jpg', '003.jpg'], $data['ficha']['fotos']);

        @unlink($txtPath);
        @unlink($jsonGenerado);
        @rmdir($tmp);
    }
}
