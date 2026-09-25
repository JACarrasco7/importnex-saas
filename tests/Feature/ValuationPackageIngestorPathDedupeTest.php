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
 * FIX 23-sep-2026 — fotos duplicadas por mismatch de path separator.
 *
 * En Windows, `realpath()` devuelve rutas con backslashes (`C:\...`), mientras
 * que `Symfony\Finder` (que usa Laravel `File::allFiles()`) puede devolver
 * rutas con separadores mixtos (`C:\Users\.../fotos/001.jpg`). Antes del fix,
 * `collectPhotos()` indexaba el array de fotos por path sin normalizar, así que
 * las 8 fotos del manifest se añadían una segunda vez desde el escaneo del
 * filesystem: 16 fotos en BD, 16 archivos en disco, doble tiempo de subida.
 *
 * El test crea un ZIP con 8 fotos en `fotos/` y verifica que la subida crea
 * exactamente 8 filas en `car_photos` (no 16).
 */
class ValuationPackageIngestorPathDedupeTest extends TestCase
{
    use RefreshDatabase;

    private string $zipPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        if (isset($this->zipPath) && file_exists($this->zipPath)) {
            @unlink($this->zipPath);
        }
        parent::tearDown();
    }

    public function test_zip_with_photos_in_fotos_folder_creates_exactly_one_row_per_file(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZipWithPhotos(8);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $car = $result['car'];

        // El ZIP trae 8 fotos: deben ser 8 en BD, no 16 (era el bug).
        $this->assertSame(8, $result['photos'], 'Result.photos debe ser 8');
        $this->assertSame(8, $car->photos()->count(), 'car_photos debe tener 8 filas');

        // Y los sort_orders deben ser 1..8 sin huecos ni duplicados.
        $sortOrders = $car->photos()->orderBy('sort_order')->pluck('sort_order')->all();
        $this->assertSame(range(1, 8), $sortOrders);
    }

    private function buildZipWithPhotos(int $photoCount, string $cocheId = 'test-dedupe'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pkg_dedupe_');
        @unlink($tmp);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp, ZipArchive::CREATE) === true);

        $informe = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'coche_id' => $cocheId,
                'generado_el' => now()->toIso8601String(),
            ],
            'vehiculo' => [
                'marca' => 'BMW', 'modelo' => '320d',
                'anio' => '07/2020', 'km' => 80000,
                'combustible' => 'Diesel', 'cambio' => 'Automatic',
                'vin' => strtoupper('TESTDEDUPE'.substr(md5($cocheId), 0, 6)),
                // URLs vacías para que savePhotos() (que solo se llamaría con
                // skipRemotePhotos=false) no intente descargar nada. Aquí la
                // flag va a true porque el ZIP trae fotos en local, así que
                // savePhotos() ni siquiera debería invocarse.
                'fotos' => [],
            ],
            'anuncio' => ['url' => 'https://example.com/'.$cocheId],
            'investigacion' => [
                'problemas_comunes' => ['hallazgo' => 'Sin issues', 'fuente' => 'x', 'valoracion' => 'favorable'],
            ],
            'balance' => [
                'a_favor' => [['texto' => 'Buen precio', 'peso' => 'alto']],
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
                'transporte' => 900,
                'coste_total' => 20000,
            ],
            'mercado' => [
                'precio_medio' => 22000,
                'precio_min' => 20000,
                'precio_max' => 24000,
                'comparables' => [],
            ],
        ];
        $zip->addFromString('informe.json', json_encode($informe));

        $manifest = [
            'manifest_version' => 1,
            'paquete_version' => 2,
            'coche_id' => $cocheId,
            'flujo' => 'A',
            'schema_version' => 1,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest));

        for ($i = 1; $i <= $photoCount; $i++) {
            // Bytes JPEG triviales (1x1 negro). Lo importante es que el archivo
            // exista en fotos/ — el ingestor no valida el contenido, solo el path.
            $jpegBytes = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AAAAAAAAH//Z'
            );
            $zip->addFromString(sprintf('fotos/%03d.jpg', $i), $jpegBytes);
        }

        $zip->close();
        $this->zipPath = $tmp;

        return $tmp;
    }
}
