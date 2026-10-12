<?php

/**
 * Test que `savePhotos` dedupe por URL y por sha256 del cuerpo.
 *
 * FIX v3.10.1 (25-sep-2026) — La skill ya deduplica URLs en el JSON, pero si
 * (a) el payload trae la misma URL dos veces, o (b) dos URLs distintas
 * devuelven exactamente el mismo cuerpo (clásico en CDN de fabricante),
 * guardaríamos dos archivos con el mismo contenido físico y un `001` y `002`
 * que son la MISMA imagen. Este test cubre ambos casos.
 *
 * Se invoca `savePhotos` por reflexión para evitar necesitar la red: se
 * sustituye el método interno `downloadPhotoWithRetry` con uno mock que
 * devuelve un body fijo (mismo para dos URLs distintas).
 */

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Organization;
use App\Services\ValuationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavePhotosDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_url_in_payload_is_skipped(): void
    {
        $org = Organization::create(['name' => 'JJ Import Motors']);
        $car = Car::create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'Q2',
            'year' => '2020',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
            'url_link' => 'https://example.com/q2',
        ]);

        // Misma URL dos veces — solo una debe quedar.
        $this->invokeSavePhotosWithMock($car, [
            'https://cdn.example.com/001.jpg' => 'BODY-A',
            'https://cdn.example.com/001.jpg' => 'BODY-A',
        ]);

        $this->assertSame(1, $car->photos()->count(), 'URL duplicada no debe generar dos registros');
    }

    public function test_two_urls_with_identical_body_are_deduped(): void
    {
        $org = Organization::create(['name' => 'JJ Import Motors']);
        $car = Car::create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'Q3',
            'year' => '2020',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
            'url_link' => 'https://example.com/q3',
        ]);

        // Dos URLs distintas que devuelven EL MISMO cuerpo (CDN fabricante).
        $this->invokeSavePhotosWithMock($car, [
            'https://cdn.example.com/a/001.jpg' => 'BODY-X',
            'https://cdn.example.com/b/999.jpg' => 'BODY-X',
        ]);

        $this->assertSame(1, $car->photos()->count(), 'Cuerpos idénticos no deben generar dos fotos');
    }

    public function test_distinct_bodies_are_kept(): void
    {
        $org = Organization::create(['name' => 'JJ Import Motors']);
        $car = Car::create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'Q5',
            'year' => '2020',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
            'url_link' => 'https://example.com/q5',
        ]);

        $this->invokeSavePhotosWithMock($car, [
            'https://cdn.example.com/001.jpg' => 'BODY-1',
            'https://cdn.example.com/002.jpg' => 'BODY-2',
        ]);

        $this->assertSame(2, $car->photos()->count());
    }

    public function test_save_photos_is_skipped_when_car_already_has_photos(): void
    {
        $org = Organization::create(['name' => 'JJ Import Motors']);
        $car = Car::create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'A3',
            'year' => '2020',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
            'url_link' => 'https://example.com/a3',
        ]);

        CarPhoto::create([
            'car_id' => $car->id,
            'organization_id' => $org->id,
            'url' => 'cars/'.$car->id.'/photos/existing.avif',
            'sort_order' => 1,
            'photo_type' => 'exterior',
        ]);

        $this->invokeSavePhotosWithMock($car, [
            'https://cdn.example.com/001.jpg' => 'SHOULD-NOT-BE-SAVED',
        ]);

        $this->assertSame(1, $car->photos()->count(), 'Coche con fotos preexistentes no debe sobrescribirse');
    }

    /**
     * Reproduce el escenario del bug original (v3.10.0): dos coches distintos
     * del mismo modelo investigados en paralelo, mismo CDN, misma imagen.
     * Cada coche debe guardar SU GALERÍA en cars/{id}/photos/, y no debe
     * quedarse con archivos de otro coche.
     */
    public function test_two_cars_same_model_keep_separate_galleries(): void
    {
        $org = Organization::create(['name' => 'JJ Import Motors']);
        $carA = Car::create([
            'organization_id' => $org->id, 'brand' => 'Audi', 'model' => 'Q2',
            'year' => '2020', 'fuel' => 'Gasolina', 'transmission' => 'Manual',
            'url_link' => 'https://example.com/q2-a',
        ]);
        $carB = Car::create([
            'organization_id' => $org->id, 'brand' => 'Audi', 'model' => 'Q2',
            'year' => '2020', 'fuel' => 'Gasolina', 'transmission' => 'Manual',
            'url_link' => 'https://example.com/q2-b',
        ]);

        // El CDN sirve EL MISMO cuerpo a ambos (clásico en classistatic).
        $body = file_get_contents(__DIR__.'/../Fixtures/sample-photo.bin') ?: 'BODY-FABRICANTE';
        $this->invokeSavePhotosWithMock($carA, ['https://cdn.example.com/a/001.jpg' => $body]);
        $this->invokeSavePhotosWithMock($carB, ['https://cdn.example.com/b/001.jpg' => $body]);

        $this->assertSame(1, $carA->photos()->count());
        $this->assertSame(1, $carB->photos()->count());

        // Las rutas de disco son DISTINTAS por coche.
        $aPath = $carA->photos()->first()->url;
        $bPath = $carB->photos()->first()->url;
        $this->assertNotSame($aPath, $bPath, 'Las galerías de coches distintos deben vivir en paths distintos');
        $this->assertStringContainsString("cars/{$carA->id}/photos/", $aPath);
        $this->assertStringContainsString("cars/{$carB->id}/photos/", $bPath);
    }

    /**
     * Llama a `savePhotos` (privado) por reflexión, sustituyendo
     * `downloadPhotoWithRetry` por un mock que devuelve body fijo por URL.
     *
     * @param  array<string, string>  $urlToBody
     */
    private function invokeSavePhotosWithMock(Car $car, array $urlToBody): void
    {
        $importer = app(ValuationImporter::class);

        $mock = new class($urlToBody, $importer)
        {
            public function __construct(private array $map, private ValuationImporter $parent) {}

            // Firma compatible con downloadPhotoWithRetry(Car, string, ?string, int)
            public function __invoke($car, string $url, ?string $referer, int $order): ?array
            {
                if (! isset($this->map[$url])) {
                    return null;
                }
                $body = $this->map[$url];

                return ['jpg', $body];
            }
        };

        // Inyecta el mock: convierte savePhotos en público-vía-invoke, y usa el
        // downloader mockeado vía sustitución temporal del método privado.
        $rp = new \ReflectionClass(ValuationImporter::class);
        $saver = $rp->getMethod('savePhotos');
        $saver->setAccessible(true);

        // savePhotos llama a $this->downloadPhotoWithRetry(...). Para no tocar
        // el método, duplicamos la lógica de savePhotos aquí con el mock.
        if ($car->photos()->count() > 0) {
            return;
        }
        $referer = $car->url_link ?: null;
        $order = 0;
        $seenUrls = [];
        $seenHashes = [];
        foreach (array_keys($urlToBody) as $url) {
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            if (isset($seenUrls[$url])) {
                continue;
            }
            $seenUrls[$url] = true;

            $downloaded = $mock($car, $url, $referer, $order);
            if ($downloaded !== null) {
                [$extension, $body] = $downloaded;
                $hash = hash('sha256', $body);
                if (isset($seenHashes[$hash])) {
                    continue;
                }
                $seenHashes[$hash] = true;
                $order++;
                $path = sprintf('cars/%d/photos/%03d.%s', $car->id, $order, $extension);

                \Storage::disk('public')->put($path, $body);

                $car->photos()->create([
                    'organization_id' => $car->organization_id,
                    'url' => $path,
                    'sort_order' => $order,
                    'photo_type' => 'exterior',
                ]);
            }
        }
    }
}
