<?php

namespace Tests\Feature;

use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_resource_returns_expected_shape(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'A3',
            'year' => '06/2020',
            'purchase_price' => 15000,
            'verdict' => 'Buy',
            'verdict_confidence' => 'high',
        ]);

        $resource = (new CarResource($car))->toArray(request());
        $data = $resource;

        $this->assertSame($car->id, $data['id']);
        $this->assertSame('Audi', $data['brand']);
        $this->assertSame('A3', $data['model']);
        $this->assertSame('06/2020', $data['year']);
        $this->assertSame(15000.0, $data['purchase_price']);
        $this->assertSame('Buy', $data['verdict']);
        $this->assertSame('high', $data['verdict_confidence']);
        $this->assertArrayHasKey('_links', $data);
        $this->assertArrayHasKey('self', $data['_links']);
        $this->assertArrayHasKey('web', $data['_links']);
        $this->assertArrayHasKey('admin', $data['_links']);
    }

    public function test_car_resource_handles_null_fields(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'verdict' => null,
            'market_avg' => null,
        ]);

        $data = (new CarResource($car))->toArray(request());

        // Null fields are present as null (clients can detect missing data)
        $this->assertNull($data['verdict']);
        $this->assertNull($data['market_avg']);
        $this->assertNull($data['market_min']);
    }

    public function test_car_resource_includes_iso_timestamps(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $data = (new CarResource($car))->toArray(request());

        $this->assertNotNull($data['created_at']);
        $this->assertNotNull($data['updated_at']);
        // ISO 8601 format: 2024-01-15T10:30:00+00:00
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['created_at']);
    }

    public function test_car_resource_includes_photos_when_loaded(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $car->load('photos');

        $data = (new CarResource($car))->toArray(request());

        $this->assertArrayHasKey('photos', $data);
        // Photos is empty array when loaded (even if no photos exist)
        $this->assertIsArray($data['photos']);
        $this->assertEmpty($data['photos']);
    }

    public function test_car_resource_photos_null_when_not_loaded(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $data = (new CarResource($car))->toArray(request());

        $this->assertNull($data['photos'], 'Photos should be null when relation not loaded (prevents N+1)');
    }

    public function test_car_resource_uses_snake_case(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $data = (new CarResource($car))->toArray(request());

        // Wire format is snake_case for JSON consistency
        foreach (['purchase_price', 'traffic_light', 'is_marketplace', 'market_avg'] as $snake) {
            $this->assertArrayHasKey($snake, $data, "Key '{$snake}' should use snake_case");
        }
    }

    public function test_car_resource_handles_currency(): void
    {
        $org = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $data = (new CarResource($car))->toArray(request());

        $this->assertArrayHasKey('currency', $data);
        $this->assertNotEmpty($data['currency']);
    }
}
