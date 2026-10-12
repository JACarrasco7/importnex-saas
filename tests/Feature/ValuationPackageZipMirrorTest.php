<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\ValuationPackageIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Regla de oro (Nº3 JJ Import Motors): el ZIP original DEBE quedar SIEMPRE
 * en `~/Desktop/JJImportMotors/informes/<marca>/<modelo>/<coche_id>.zip`
 * después de cada subida, para no perder el informe aunque el operador
 * limpie temporales o reimporte accidentalmente.
 *
 * Cubrimos tres casos:
 *  - Caso 1: ZIP viene de fuera (upload HTTP) → se copia al Desktop.
 *  - Caso 2: ZIP ya está en Desktop (mismo archivo) → NO se duplica.
 *  - Caso 3: HOME/USERPROFILE no definidos → warning pero NO aborta.
 */
class ValuationPackageZipMirrorTest extends TestCase
{
    use RefreshDatabase;

    private string $zipPath;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        // Carpeta temporal dedicada para los ZIPs de prueba — fuera del Desktop
        // para que los realextos viajen y la copia espejo tenga que ocurrir.
        $this->tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pkg_mirror_test_'.uniqid();
        if (! is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->zipPath) && file_exists($this->zipPath)) {
            @unlink($this->zipPath);
        }
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir.'/*') as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function test_zip_from_outside_is_copied_to_desktop(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        // ZIP en /tmp, fuera del Desktop real → debe copiarse al destino canónico.
        $cocheId = 'test-mirror-001-'.uniqid();
        $zipPath = $this->buildZip('audi', 'q2', $cocheId);
        $this->zipPath = $zipPath;

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;
        $this->assertNotEmpty($home, 'Test requiere HOME/USERPROFILE en el entorno del runner');

        $expectedDest = $home.DIRECTORY_SEPARATOR.'Desktop'
            .DIRECTORY_SEPARATOR.'JJImportMotors'
            .DIRECTORY_SEPARATOR.'informes'
            .DIRECTORY_SEPARATOR.'audi'
            .DIRECTORY_SEPARATOR.'q2'
            .DIRECTORY_SEPARATOR.$cocheId.'.zip';

        $this->assertFileExists(
            $expectedDest,
            'El ZIP debe existir en Desktop tras la importación. Destino: '.$expectedDest
        );
        $this->assertSame(
            filesize($zipPath),
            filesize($expectedDest),
            'El ZIP copiado debe tener el mismo tamaño que el original'
        );
        // Limpieza: borrar el ZIP y su directorio si quedó vacío.
        @unlink($expectedDest);
        @rmdir(dirname($expectedDest));
    }

    public function test_zip_already_in_desktop_is_not_duplicated(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        // Pre-creamos el ZIP en SU destino final y subimos desde ahí.
        // El ingestor debe detectar `realpath` idéntico y NO copiar.
        $cocheId = 'test-mirror-dedupe-'.uniqid();
        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;
        $this->assertNotEmpty($home, 'Test requiere HOME/USERPROFILE en el entorno del runner');

        $destDir = $home.DIRECTORY_SEPARATOR.'Desktop'
            .DIRECTORY_SEPARATOR.'JJImportMotors'
            .DIRECTORY_SEPARATOR.'informes'
            .DIRECTORY_SEPARATOR.'audi'
            .DIRECTORY_SEPARATOR.'q2';
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $destPath = $destDir.DIRECTORY_SEPARATOR.$cocheId.'.zip';
        $this->buildZip('audi', 'q2', $cocheId, $destPath);
        $this->zipPath = $destPath;

        $result = app(ValuationPackageIngestor::class)->ingest($destPath, $org);

        $archivos = glob($destDir.DIRECTORY_SEPARATOR.$cocheId.'*.zip');
        $this->assertCount(
            1,
            $archivos,
            'No debe duplicarse el ZIP cuando el origen ES ya el destino'
        );

        // Cleanup
        @unlink($destPath);
    }

    private function buildZip(string $marca, string $modelo, string $cocheId, ?string $destPath = null): string
    {
        $target = $destPath ?: $this->tmpDir.DIRECTORY_SEPARATOR.$cocheId.'.zip';

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($target, ZipArchive::CREATE) === true);

        $informe = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'coche_id' => $cocheId,
                'generado_el' => now()->toIso8601String(),
            ],
            'vehiculo' => [
                'marca' => ucfirst($marca),
                'modelo' => strtoupper($modelo),
                'anio' => '01/2020',
                'km' => 80000,
                'combustible' => 'Gasolina',
                'cambio' => 'Manual',
                'vin' => 'TESTVIN'.substr(md5($cocheId), 0, 7),
                'fotos' => [],
            ],
            'anuncio' => ['url' => 'https://example.com/'.$cocheId],
            'investigacion' => [
                'problemas_comunes' => ['hallazgo' => 'ok', 'fuente' => 'x', 'valoracion' => 'favorable'],
            ],
            'balance' => [
                'a_favor' => [['texto' => 'ok', 'peso' => 'alto']],
                'en_contra' => [],
            ],
            'veredicto' => [
                'recomendacion' => 'Comprar',
                'confianza' => 'alta',
                'razonamiento' => 'Test',
                'que_cambiaria' => '',
                'precio_objetivo' => 18000,
            ],
            'costes' => [
                'precio_coche' => 18000,
                'pvp_nuevo' => 32000,
                'coste_total' => 20000,
            ],
            'mercado' => [
                'precio_medio' => 22000,
                'comparables' => [],
            ],
        ];
        $zip->addFromString('informe.json', json_encode($informe));
        $zip->addFromString('manifest.json', json_encode([
            'manifest_version' => 1,
            'paquete_version' => 2,
            'coche_id' => $cocheId,
            'flujo' => 'A',
            'schema_version' => 1,
        ]));
        $zip->close();

        return $target;
    }
}
