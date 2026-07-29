<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportRealCarsTest extends TestCase
{
    use RefreshDatabase;

    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = sys_get_temp_dir() . '/import-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tmpFile)) {
            File::delete($this->tmpFile);
        }
        parent::tearDown();
    }

    public function test_imports_cars_from_json(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $data = [
            [
                'id' => '1',
                'marca' => 'Opel',
                'modelo' => 'Astra OPC',
                'version' => '2.0 Turbo',
                'anio' => '07/2016',
                'km' => 98000,
                'combustible' => 'Gasolina',
                'cambio' => 'Manual',
                'cv' => 280,
                'cilindrada' => '1.998 cc',
                'co2' => 185,
                'precioCoche' => 13999,
                'precioNuevo' => 31000,
                'transporte' => 1200,
                'estado' => 'Valorando',
                'enlace' => 'https://example.com/car/1',
                'vendedor' => 'Rami Automobile',
                'ciudad' => 'Hamburg',
                'lat' => 53.55,
                'lng' => 9.99,
                'semaforo' => 'green',
                'valoracion' => 'Good deal',
                'recomendacion' => 'Buy',
                'descripcion' => 'Test car',
                'equipamiento' => ['LED', 'GPS'],
                'consejos' => ['Inspect'],
                'banderasRojas' => [],
                'fotos' => ['https://example.com/photo.jpg'],
            ],
        ];

        File::put($this->tmpFile, json_encode($data));

        Artisan::call('import:real-cars', ['--file' => $this->tmpFile]);

        $this->assertDatabaseHas('cars', [
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra OPC',
            'year' => '07/2016',
            'purchase_price' => 13999,
            'status' => 'Valuing',
            'traffic_light' => 'green',
        ]);
    }

    public function test_dry_run_does_not_insert(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $data = [['id' => '1', 'marca' => 'Opel', 'modelo' => 'Astra', 'anio' => '07/2020',
                  'combustible' => 'Gasolina', 'cambio' => 'Manual', 'precioCoche' => 10000, 'estado' => 'Localizado']];
        File::put($this->tmpFile, json_encode($data));

        Artisan::call('import:real-cars', ['--file' => $this->tmpFile, '--dry-run' => true]);

        $this->assertDatabaseCount('cars', 0);
    }
}
