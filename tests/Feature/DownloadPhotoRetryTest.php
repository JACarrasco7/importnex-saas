<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Services\ValuationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * FIX 23-sep-2026: `savePhotos()` ahora reintenta ante 403/429/5xx con backoff.
 * Si tras 3 intentos sigue fallando, deja la foto fuera y registra warning —
 * el operador puede reimportar el ZIP cuando el bloqueo (anti-bot) se levante.
 *
 * Estos tests unitarios validan:
 *  - 403 transitorio → retry → éxito eventual
 *  - 403 persistente → 3 intentos → warning, sin foto
 *  - 404 inmediato → NO retry (error cliente no transitorio)
 *  - 200 normal → 1 intento, sin esperar
 */
class DownloadPhotoRetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function makeCar(): Car
    {
        $org = Organization::factory()->create();
        $car = new Car([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'Q2',
            'year' => '01/2020',
            'transmission' => 'Manual',
            'fuel' => 'Gasoline',
            'url_link' => 'https://suchen.mobile.de/fahrzeuge/details.html?id=12345',
        ]);
        $car->save();

        return $car;
    }

    public function test_403_transient_recovers_after_retry(): void
    {
        $car = $this->makeCar();

        Http::fake([
            'img.classistatic.de/*' => Http::sequence()
                ->push('Forbidden', 403)
                ->push('Forbidden', 403)
                ->push("\xFF\xD8\xFF".str_repeat('X', 100), 200)
                ->push(['Content-Type' => 'image/jpeg']),
        ]);

        // Invoquemos savePhotos vía reflection para saltarnos savePhotosAndFiles
        $importer = app(ValuationImporter::class);
        $ref = new ReflectionMethod($importer, 'savePhotos');
        $ref->setAccessible(true);
        $ref->invoke($importer, $car, ['https://img.classistatic.de/test.jpg']);

        $this->assertSame(1, $car->photos()->count(), 'Tras retries + 200 OK, debe haber 1 foto');
    }

    public function test_403_persistent_fails_after_max_retries(): void
    {
        $car = $this->makeCar();

        // 4 intentos todos 403 — los 3 primeros del retry + 1 del push final (sequence consume todos)
        Http::fake([
            'img.classistatic.de/*' => Http::sequence()
                ->push('Forbidden', 403)
                ->push('Forbidden', 403)
                ->push('Forbidden', 403)
                ->push('Forbidden', 403),
        ]);

        $importer = app(ValuationImporter::class);
        $ref = new ReflectionMethod($importer, 'savePhotos');
        $ref->setAccessible(true);

        $start = microtime(true);
        $ref->invoke($importer, $car, ['https://img.classistatic.de/test.jpg']);
        $elapsed = microtime(true) - $start;

        // 3 retries con backoff 1s, 2s + jitter (~3s mínimo)
        $this->assertSame(0, $car->photos()->count(), 'Tras agotar retries, no debe haber foto');
        // Backoff mínimo 1s + 2s = 3s. Permitimos margen para jitter pero no más de 8s.
        $this->assertGreaterThan(2.5, $elapsed, 'Debió esperar al menos ~3s de backoff acumulado');
        $this->assertLessThan(10, $elapsed, 'Backoff no debe exceder ~8s en total');
    }

    public function test_404_does_not_retry(): void
    {
        $car = $this->makeCar();

        Http::fake([
            'img.classistatic.de/*' => Http::response('Not Found', 404),
        ]);

        $importer = app(ValuationImporter::class);
        $ref = new ReflectionMethod($importer, 'savePhotos');
        $ref->setAccessible(true);

        $start = microtime(true);
        $ref->invoke($importer, $car, ['https://img.classistatic.de/test.jpg']);
        $elapsed = microtime(true) - $start;

        $this->assertSame(0, $car->photos()->count(), '404 NO debe reintentar (error cliente no transitorio)');
        $this->assertLessThan(1.5, $elapsed, '404 debe fallar rápido sin reintentos');
    }

    public function test_200_ok_does_not_retry(): void
    {
        $car = $this->makeCar();

        Http::fake([
            'img.classistatic.de/*' => Http::response("\xFF\xD8\xFF".str_repeat('X', 100), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $importer = app(ValuationImporter::class);
        $ref = new ReflectionMethod($importer, 'savePhotos');
        $ref->setAccessible(true);

        $ref->invoke($importer, $car, ['https://img.classistatic.de/test.jpg']);

        $this->assertSame(1, $car->photos()->count(), '200 OK debe procesarse sin reintentos');
    }
}
