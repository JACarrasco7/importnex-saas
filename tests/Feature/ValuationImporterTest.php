<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use App\Services\ValuationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValuationImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_schema_version(): void
    {
        $importer = app(ValuationImporter::class);

        $this->expectException(\RuntimeException::class);
        $importer->validate([
            '_meta' => ['schema_version' => 999],
        ]);
    }

    public function test_throws_when_schema_version_missing(): void
    {
        $importer = app(ValuationImporter::class);

        $this->expectException(\RuntimeException::class);
        $importer->validate([]);
    }

    public function test_resolves_existing_car_by_vin(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $existing = Car::factory()->create([
            'organization_id' => $org->id,
            'vin' => 'VIN12345678ABCDEF',
        ]);

        $importer = app(ValuationImporter::class);
        $resolved = $importer->resolveCar([
            'vehiculo' => ['vin' => 'VIN12345678ABCDEF'],
        ], $org);

        $this->assertSame($existing->id, $resolved->id);
    }

    public function test_resolves_existing_car_by_url_when_no_vin(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $existing = Car::factory()->create([
            'organization_id' => $org->id,
            'vin' => null,
            'url_link' => 'https://example.com/coche/1',
        ]);

        $importer = app(ValuationImporter::class);
        $resolved = $importer->resolveCar([
            'vehiculo' => ['vin' => null],
            'anuncio' => ['url' => 'https://example.com/coche/1'],
        ], $org);

        $this->assertSame($existing->id, $resolved->id);
    }

    public function test_creates_new_car_when_no_match(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $importer = app(ValuationImporter::class);
        $car = $importer->resolveCar([
            'vehiculo' => ['vin' => 'NEWVIN1234567890AB'],
            'anuncio' => ['url' => 'https://example.com/new'],
        ], $org);

        $this->assertFalse($car->exists);
        $this->assertSame($org->id, $car->organization_id);
    }

    public function test_apply_fills_enriched_fields(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'vin' => 'TESTVIN12345ABCDE',
        ]);

        $importer = app(ValuationImporter::class);
        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION],
            'vehiculo' => [
                'marca' => 'Opel', 'modelo' => 'Astra OPC', 'version' => '2.0T',
                'anio' => '07/2016', 'km' => 98000,
                'combustible' => 'Gasolina',
                'cambio' => 'Manual',
                'vin' => 'TESTVIN12345ABCDE',
            ],
            'anuncio' => ['url' => 'https://mobile.de/x'],
            'investigacion' => [
                'common_issues' => ['finding' => 'Turbo a 98k km', 'source' => 'https://forum.example.com', 'valoracion' => 'desfavorable'],
                'market_price' => ['finding' => '17.990 - 20.000', 'source' => 'coches.net', 'valoracion' => 'favorable'],
            ],
            'balance' => [
                'a_favor' => [
                    ['texto' => 'Buen precio', 'peso' => 'alto'],
                    ['texto' => 'COC disponible', 'peso' => 'medio'],
                ],
                'en_contra' => [
                    ['texto' => 'Turbo vigilar', 'peso' => 'alto'],
                ],
            ],
            'veredicto' => [
                'recomendacion' => 'Comprar',
                'confianza' => 'alta',
                'razonamiento' => 'Por debajo de mercado.',
                'que_cambiaria' => 'Si baja 500',
            ],
            'mercado' => [
                'precio_medio' => 18500,
                'precio_min' => 17990,
                'precio_max' => 20000,
                'ahorro_estimado' => 2500,
            ],
        ];

        $importer->apply($car, $payload);
        $car->refresh();

        $this->assertSame('Opel', $car->brand);
        $this->assertSame('Astra OPC', $car->model);
        $this->assertSame('Buy', $car->verdict);                    // Comprar -> Buy
        $this->assertSame('high', $car->verdict_confidence);        // alta -> high
        $this->assertSame('chat', $car->research_source);
        $this->assertSame(ValuationImporter::SUPPORTED_SCHEMA_VERSION, $car->schema_version);

        $this->assertIsArray($car->research);
        $this->assertSame('Turbo a 98k km', $car->research['common_issues']['finding']);
        $this->assertSame('unfavorable', $car->research['common_issues']['rating']);

        $this->assertCount(2, $car->pros);
        $this->assertSame('Buen precio', $car->pros[0]['text']);
        $this->assertSame('high', $car->pros[0]['weight']);         // alto -> high

        $this->assertCount(1, $car->cons);
        $this->assertSame('Turbo vigilar', $car->cons[0]['text']);

        $this->assertSame(18500.0, (float) $car->market_avg);
        $this->assertSame(17990.0, (float) $car->market_min);
        $this->assertSame(20000.0, (float) $car->market_max);
        $this->assertSame(2500.0, (float) $car->estimated_saving);
    }

    public function test_cli_imports_valid_files(): void
    {
        $tmpDir = sys_get_temp_dir().'/importnex-test-'.uniqid();
        mkdir($tmpDir, 0777, true);

        file_put_contents($tmpDir.'/test.json', json_encode([
            '_meta' => ['schema_version' => Car::CURRENT_SCHEMA_VERSION],
            'vehiculo' => [
                'marca' => 'BMW', 'modelo' => '320d',
                'vin' => 'VIN12345678ABCDEF',
                'anio' => '07/2020', 'km' => 80000,
                'combustible' => 'Diesel', 'cambio' => 'Automatic',
            ],
            'anuncio' => ['url' => 'https://mobile.de/bmw-320d-test'],
            'veredicto' => ['recomendacion' => 'Buy'],
        ]));

        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $this->artisan('importnex:import-valuation', [
            '--dir' => $tmpDir,
            '--org' => $org->name,
        ])->assertSuccessful();

        $this->assertDatabaseHas('cars', [
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
            'verdict' => 'Buy',
            'research_source' => 'chat',
        ]);

        array_map('unlink', glob($tmpDir.'/*'));
        rmdir($tmpDir);
    }

    public function test_cli_skips_invalid_files(): void
    {
        $tmpDir = sys_get_temp_dir().'/importnex-test-'.uniqid();
        mkdir($tmpDir, 0777, true);

        file_put_contents($tmpDir.'/bad.json', json_encode([
            '_meta' => ['schema_version' => 999],
        ]));

        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $this->artisan('importnex:import-valuation', [
            '--dir' => $tmpDir,
            '--org' => $org->name,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('cars', ['organization_id' => $org->id]);

        array_map('unlink', glob($tmpDir.'/*'));
        rmdir($tmpDir);
    }

    /**
     * Test against the real chat report example (Spanish contract, translated on import).
     */
    public function test_apply_full_spanish_payload_from_chat_example(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $payload = json_decode(file_get_contents(__DIR__.'/fixtures/chat_report_example.json'), true);

        $importer = app(ValuationImporter::class);
        $resolved = $importer->resolveCar($payload, $org);
        $this->assertFalse($resolved->exists, 'Should create a new car when no VIN/URL match');

        $car = $importer->apply($resolved, $payload);
        $car->refresh();

        // Identity (Spanish -> English)
        $this->assertSame('Opel', $car->brand);
        $this->assertSame('Astra', $car->model);
        $this->assertSame('1.5 CDTi Business Elegance', $car->version);
        $this->assertSame('01/2019', $car->year); // "2019" normalized to "01/2019"
        $this->assertSame(84500, (int) $car->mileage);
        $this->assertSame('Diesel', $car->fuel);   // "Diésel" -> Diesel
        $this->assertSame('Manual', $car->transmission);
        $this->assertSame(122, (int) $car->cv);
        $this->assertSame(109, (int) $car->co2);
        $this->assertSame('Gris grafito', $car->color);
        $this->assertSame(5, (int) $car->doors);
        $this->assertSame(5, (int) $car->seats);
        $this->assertSame(1, (int) $car->owners);

        // Listing
        $this->assertSame('https://www.mobile.de/EJEMPLO-no-es-un-anuncio-real', $car->url_link);
        $this->assertSame('Múnich', $car->city);
        $this->assertSame('Autohaus Beispiel GmbH (Profesional)', $car->seller);
        $this->assertSame('Traducción al español de la descripción original.', $car->description);
        $this->assertEquals(['Navegador', 'Cámara trasera', 'Faros LED', 'Climatizador bizona', 'Apple CarPlay'], $car->equipment);

        // Costs
        $this->assertSame(12900.0, (float) $car->purchase_price);
        $this->assertSame(1200.0, (float) $car->transport);
        $this->assertSame(95.0, (float) $car->itv_fee);
        $this->assertSame(20.61, (float) $car->dgt_fees);
        $this->assertSame(1500.0, (float) $car->professional_fees);

        // Research: 9 aspects, mapped to canonical English keys
        $this->assertCount(9, $car->research);
        $this->assertSame('unfavorable', $car->research['common_issues']['rating']); // "desfavorable"
        $this->assertSame('favorable', $car->research['recalls']['rating']);
        $this->assertSame('favorable', $car->research['market_price']['rating']);
        $this->assertSame('favorable', $car->research['reliability']['rating']);
        $this->assertSame('favorable', $car->research['spain_homologation']['rating']);
        $this->assertSame('neutral', $car->research['dgt_label']['rating']);
        $this->assertSame('neutral', $car->research['insurance_estimate']['rating']);
        $this->assertSame('favorable', $car->research['parts_maintenance']['rating']);
        $this->assertSame('favorable', $car->research['unit_specific']['rating']);
        $this->assertNotEmpty($car->research['recalls']['finding']);

        // Pros / Cons (Spanish keys -> English fields)
        $this->assertCount(4, $car->pros);
        $this->assertSame('Kilometraje bajo para el año', $car->pros[0]['text']);
        $this->assertSame('high', $car->pros[0]['weight']); // "alto" -> high
        $this->assertSame('medium', $car->pros[2]['weight']); // "medio" -> medium

        $this->assertCount(3, $car->cons);
        $this->assertSame('Riesgo conocido de filtro de partículas si se usa en ciudad', $car->cons[0]['text']);
        $this->assertSame('high', $car->cons[0]['weight']);
        $this->assertSame('low', $car->cons[2]['weight']); // "bajo" -> low

        // Verdict (Spanish -> English enum)
        $this->assertSame('Buy if price drops', $car->verdict); // "Comprar si baja de precio"
        $this->assertSame('medium', $car->verdict_confidence);   // "media"
        $this->assertNotEmpty($car->verdict_reasoning);
        $this->assertNotEmpty($car->verdict_changes);
        $this->assertNotNull($car->verdict_at);

        // Market
        $this->assertSame(16333.33, (float) $car->market_avg);
        $this->assertSame(15400.0, (float) $car->market_min);
        $this->assertSame(17200.0, (float) $car->market_max);
        $this->assertSame(617.72, (float) $car->estimated_saving);

        // Comparables: full schema
        $this->assertCount(3, $car->comparables_list);
        $this->assertSame('EJEMPLO Opel Astra 1.5 CDTi 2019', $car->comparables_list[0]['title']);
        $this->assertSame(16400, $car->comparables_list[0]['price']);
        $this->assertSame(79000, $car->comparables_list[0]['km']);
        $this->assertSame('https://ejemplo.example/1', $car->comparables_list[0]['url']);
        $this->assertSame('España', $car->comparables_list[0]['country']);

        // Source
        $this->assertSame('chat', $car->research_source);
        $this->assertSame(ValuationImporter::SUPPORTED_SCHEMA_VERSION, $car->schema_version);

        // Notes: contains the unstructured bits + advisories
        $this->assertStringContainsString('Warranty:', $car->notes);
        $this->assertStringContainsString('Maintenance:', $car->notes);
        $this->assertStringContainsString('IEDMT is an estimate', $car->notes);
        $this->assertStringContainsString('EJEMPLO', $car->notes);
    }

    /**
     * §3.1 — co2_confirmado: false → warning en avisos
     */
    public function test_co2_not_confirmed_adds_warning_to_avisos(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => [
                'marca' => 'VW', 'modelo' => 'Golf', 'version' => 'GTI',
                'anio' => 2018, 'km' => 50000, 'combustible' => 'Gasolina',
                'cambio' => 'Automático', 'potencia_cv' => 245,
                'co2_gkm' => 165, 'co2_confirmado' => false,
            ],
            'anuncio' => ['url' => 'https://example.com/ad-1', 'precio_publicado' => 28000],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'media', 'razonamiento' => 'Test', 'precio_objetivo' => 26000, 'fecha' => '2026-08-12'],
            'costes' => ['precio_coche' => 28000, 'pvp_nuevo' => 40000, 'transporte' => 900],
            'mercado' => ['precio_medio' => 32000, 'comparables' => []],
            'avisos' => ['Aviso previo'],
        ];

        $car = $importer->apply($car, $payload);

        // El aviso de CO₂ no confirmado debería estar en notes (que es donde se persisten los avisos)
        $this->assertStringContainsString('CO₂ no confirmado', $car->notes);
        $this->assertStringContainsString('Aviso previo', $car->notes);
    }

    /**
     * §3.2 — comparables sin URL se filtran y se avisa
     */
    public function test_comparables_without_url_are_filtered(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => ['marca' => 'VW', 'modelo' => 'Golf', 'anio' => 2018, 'km' => 50000, 'combustible' => 'Gasolina', 'cambio' => 'Manual', 'potencia_cv' => 245, 'co2_gkm' => 165],
            'anuncio' => ['url' => 'https://example.com/ad-1'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'media', 'razonamiento' => 'Test', 'precio_objetivo' => 26000, 'fecha' => '2026-08-12'],
            'costes' => ['precio_coche' => 28000, 'pvp_nuevo' => 40000, 'transporte' => 900],
            'mercado' => [
                'precio_medio' => 32000,
                'comparables' => [
                    ['titulo' => 'Con URL', 'precio' => 32000, 'km' => 55000, 'url' => 'https://example.com/1', 'pais' => 'España'],
                    ['titulo' => 'Sin URL', 'precio' => 31500, 'km' => 60000, 'pais' => 'España'],  // Sin URL
                    ['titulo' => 'Con URL 2', 'precio' => 33000, 'km' => 50000, 'url' => 'https://example.com/2', 'pais' => 'España'],
                ],
            ],
        ];

        $car = $importer->apply($car, $payload);

        // El aviso sobre comparables filtrados debería estar en notes
        $this->assertStringContainsString('1 comparables descartados', $car->notes);
    }

    /**
     * §3.3 — precio_objetivo obligatorio cuando recomendación es "Comprar si baja de precio"
     */
    public function test_precio_objetivo_required_when_buy_if_price_drops(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => ['marca' => 'VW', 'modelo' => 'Golf', 'anio' => 2018, 'km' => 50000, 'combustible' => 'Gasolina', 'cambio' => 'Manual', 'potencia_cv' => 245, 'co2_gkm' => 165],
            'anuncio' => ['url' => 'https://example.com/ad-1'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => [
                'recomendacion' => 'Comprar si baja de precio',
                'confianza' => 'media',
                'razonamiento' => 'Test',
                // precio_objetivo INTENCIONADAMENTE OMITIDO
                'fecha' => '2026-08-12',
            ],
            'costes' => ['precio_coche' => 28000, 'pvp_nuevo' => 40000],
            'mercado' => ['precio_medio' => 32000, 'comparables' => []],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('precio_objetivo es obligatorio');

        $importer->apply($car, $payload);
    }

    /**
     * §3.4 — mapeo de traccion a drivetrain (FWD)
     */
    public function test_drivetrain_mapped_from_traccion_fwd(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => [
                'marca' => 'VW', 'modelo' => 'Golf', 'anio' => 2018, 'km' => 50000,
                'combustible' => 'Gasolina', 'cambio' => 'Manual', 'potencia_cv' => 150,
                'co2_gkm' => 120, 'traccion' => 'Delantera',
            ],
            'anuncio' => ['url' => 'https://example.com/ad-1'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'alta', 'razonamiento' => 'Test', 'precio_objetivo' => 20000, 'fecha' => '2026-08-12'],
            'costes' => ['precio_coche' => 20000, 'pvp_nuevo' => 30000],
            'mercado' => ['precio_medio' => 25000, 'comparables' => []],
        ];

        $car = $importer->apply($car, $payload);

        $this->assertSame('FWD', $car->drivetrain);
    }

    /**
     * §3.4 — mapeo de traccion a drivetrain (AWD)
     */
    public function test_drivetrain_mapped_from_traccion_awd(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => [
                'marca' => 'Audi', 'modelo' => 'A4', 'anio' => 2019, 'km' => 40000,
                'combustible' => 'Diésel', 'cambio' => 'Automático', 'potencia_cv' => 190,
                'co2_gkm' => 130, 'traccion' => 'tracción total',
            ],
            'anuncio' => ['url' => 'https://example.com/ad-2'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'alta', 'razonamiento' => 'Test', 'precio_objetivo' => 28000, 'fecha' => '2026-08-12'],
            'costes' => ['precio_coche' => 28000, 'pvp_nuevo' => 45000],
            'mercado' => ['precio_medio' => 35000, 'comparables' => []],
        ];

        $car = $importer->apply($car, $payload);

        $this->assertSame('AWD', $car->drivetrain);
    }

    /**
     * §3.4 — drivetrain null si no viene traccion
     */
    public function test_drivetrain_null_when_traccion_missing(): void
    {
        $org = Organization::factory()->create();
        $importer = app(ValuationImporter::class);
        $car = Car::factory()->make(['organization_id' => $org->id]);

        $payload = [
            '_meta' => ['schema_version' => ValuationImporter::SUPPORTED_SCHEMA_VERSION, 'flujo' => 'A'],
            'vehiculo' => [
                'marca' => 'Seat', 'modelo' => 'León', 'anio' => 2020, 'km' => 30000,
                'combustible' => 'Gasolina', 'cambio' => 'Manual', 'potencia_cv' => 110,
                'co2_gkm' => 115,
                // sin 'traccion'
            ],
            'anuncio' => ['url' => 'https://example.com/ad-3'],
            'investigacion' => [],
            'balance' => ['a_favor' => [], 'en_contra' => []],
            'veredicto' => ['recomendacion' => 'Comprar', 'confianza' => 'alta', 'razonamiento' => 'Test', 'precio_objetivo' => 15000, 'fecha' => '2026-08-12'],
            'costes' => ['precio_coche' => 15000, 'pvp_nuevo' => 22000],
            'mercado' => ['precio_medio' => 18000, 'comparables' => []],
        ];

        $car = $importer->apply($car, $payload);

        $this->assertNull($car->drivetrain);
    }
}
