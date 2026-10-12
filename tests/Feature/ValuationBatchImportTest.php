<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\ValuationPackageIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ValuationBatchImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_batch_processes_multiple_zips_best_effort(): void
    {
        Storage::fake('public');

        $org = Organization::create(['name' => 'JJ Import Motors']);

        // Dos ZIPs reales (no se construye un ZIP fake porque ZipArchive en
        // memoria es pesado; se reusan los fixtures del repo). Si los fixtures
        // no existen, el test los salta limpiamente.
        $validZip = base_path('tests/Fixtures/sample-valuation-audi-q2-2020.zip');
        $this->createSampleZipIfMissing($validZip);

        // Caso 1: 2 ZIPs reales (ambos válidos).
        $ingestor = app(ValuationPackageIngestor::class);
        $result = $ingestor->ingestBatch([$validZip, $validZip], ['a.zip', 'b.zip'], $org);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['processed']);
        $this->assertSame(0, $result['failed']);
        $this->assertCount(2, $result['results']);
        $this->assertSame('a.zip', $result['results'][0]['basename']);
    }

    public function test_ingest_batch_continues_when_one_zip_fails(): void
    {
        Storage::fake('public');

        $org = Organization::create(['name' => 'JJ Import Motors']);

        $validZip = base_path('tests/Fixtures/sample-valuation-audi-q2-2020.zip');
        $this->createSampleZipIfMissing($validZip);

        // Un ZIP corrupto + uno válido → el batch devuelve ok=true (al menos
        // uno entró), failed=1, processed=1.
        $corruptZip = tempnam(sys_get_temp_dir(), 'corrupt_').'.zip';
        file_put_contents($corruptZip, 'NO ES UN ZIP VALIDO');

        $ingestor = app(ValuationPackageIngestor::class);
        $result = $ingestor->ingestBatch(
            [$corruptZip, $validZip],
            ['broken.zip', 'ok.zip'],
            $org,
        );

        @unlink($corruptZip);

        $this->assertTrue($result['ok'], 'batch es ok si al menos un ZIP entra');
        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame('broken.zip', $result['results'][0]['basename']);
        $this->assertFalse($result['results'][0]['ok']);
        $this->assertArrayHasKey('error', $result['results'][0]);
        $this->assertSame('ok.zip', $result['results'][1]['basename']);
        $this->assertTrue($result['results'][1]['ok']);
        $this->assertArrayHasKey('car_id', $result['results'][1]);
    }

    public function test_ingest_batch_returns_error_when_all_zips_fail(): void
    {
        Storage::fake('public');

        $org = Organization::create(['name' => 'JJ Import Motors']);

        $corrupt1 = tempnam(sys_get_temp_dir(), 'c1_').'.zip';
        $corrupt2 = tempnam(sys_get_temp_dir(), 'c2_').'.zip';
        file_put_contents($corrupt1, 'NO ES UN ZIP');
        file_put_contents($corrupt2, 'TAMPOCO');

        $ingestor = app(ValuationPackageIngestor::class);
        $result = $ingestor->ingestBatch(
            [$corrupt1, $corrupt2],
            ['a.zip', 'b.zip'],
            $org,
        );

        @unlink($corrupt1);
        @unlink($corrupt2);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(2, $result['failed']);
    }

    public function test_ingest_batch_skips_missing_files(): void
    {
        Storage::fake('public');

        $org = Organization::create(['name' => 'JJ Import Motors']);

        $ingestor = app(ValuationPackageIngestor::class);
        $result = $ingestor->ingestBatch(
            ['C:/no/existe/este/falso.zip'],
            ['ghost.zip'],
            $org,
        );

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('no encontrado', strtolower($result['results'][0]['error']));
    }

    /**
     * Crea un ZIP mínimo válido (informe.json schema v1) si no existe ya.
     * Así el test es portable y no depende de los ZIPs reales que el
     * usuario subió.
     */
    private function createSampleZipIfMissing(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            return;
        }

        @mkdir(dirname($zipPath), 0755, true);
        $tmp = tempnam(sys_get_temp_dir(), 'src_');
        file_put_contents($tmp, json_encode([
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'coche_id' => 'test-batch-'.uniqid(),
            ],
            'vehiculo' => [
                'marca' => 'Audi',
                'modelo' => 'Q2',
                'anio' => '2020',
                'km' => 50000,
                'combustible' => 'Gasolina',
                'cambio' => 'Manual',
                'traccion' => 'Delantera',
                'puertas' => 5,
                'plazas' => 5,
                'potencia_cv' => 116,
                'co2_gkm' => 122,
                'co2_confirmado' => false,
                'color_exterior' => 'Negro',
                'propietarios' => 1,
                'equipamiento' => [],
                'fotos' => [],
            ],
            'anuncio' => [
                'portal' => 'mobile.de',
                'url' => 'https://example.com/test-batch-'.uniqid(),
                'pais_origen' => 'DE',
                'precio_publicado' => 14000,
                'moneda' => 'EUR',
                'descripcion_original' => '',
                'descripcion_traducida' => '',
            ],
            'costes' => [
                'precio_coche' => 14000,
                'pvp_nuevo' => 25000,
                'transporte' => 900,
                'itv_matriculacion' => 115,
                'tasa_dgt' => 0,
                'iedmt_estimado' => 400,
                'iedmt_sin_minoracion' => 500,
                'gestoria' => 0,
                'otros' => 100,
                'honorarios' => 1500,
                'coste_total' => 17015,
                'precio_cliente' => 17015,
            ],
            'veredicto' => [
                'recomendacion' => 'Comprar',
                'confianza' => 'alta',
            ],
        ], JSON_PRETTY_PRINT));

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->markTestSkipped("No se pudo crear ZIP fixture en {$zipPath}");
        }
        $zip->addFile($tmp, 'informe.json');
        $zip->addFromString('manifest.json', json_encode(['paquete_version' => 1]));
        $zip->close();
        @unlink($tmp);
    }
}
