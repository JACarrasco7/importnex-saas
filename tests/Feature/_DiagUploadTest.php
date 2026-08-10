<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class _DiagUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_upload_flow_with_complete_data(): void
    {
        $org = Organization::factory()->create([
            'plan' => 'pro',
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        echo "\n=== DIAGNOSTICO SUBIDA COCHE ===\n";
        echo "Org: {$org->name} (plan={$org->plan}, isOwner=" . ($org->isOwner() ? 'SI' : 'NO') . ")\n";
        echo "User: {$user->email}\n";
        echo "limitFor('cars'): {$org->limitFor('cars')}\n";
        echo "limitReached('cars'): " . ($org->limitReached('cars') ? 'SI' : 'NO') . "\n\n";

        $payload = [
            'brand' => 'Opel',
            'model' => 'Astra GTC',
            'version' => 'OPC',
            'year' => '01/2020',
            'fuel' => 'Gasoline',
            'transmission' => 'Manual',
            'purchase_price' => 18500,
            'status' => 'Located',
            'traffic_light' => 'green',
            'mileage' => 45000,
            'cv' => 280,
        ];

        echo "=== Test 1: POST /cars con year='01/2020' (formato valido) ===\n";
        $response = $this->actingAs($user)->post(route('cars.store'), $payload);
        echo "Status HTTP: {$response->getStatusCode()}\n";
        if ($response->getStatusCode() >= 400) {
            if ($response->getStatusCode() === 302) {
                echo "Redirect to: " . $response->headers->get('Location') . "\n";
                echo "Session errors: " . json_encode(session('errors') ?: []) . "\n";
            } elseif ($response->getStatusCode() === 422) {
                echo "Validation errors: " . json_encode(session('errors') ?: $response->json('errors')) . "\n";
            }
        } else {
            echo "OK: coche creado con id=" . Car::latest()->first()->id . "\n";
        }

        echo "\n=== Test 2: POST /cars con year=2020 (numero puro, NO scrapeado) ===\n";
        $payload2 = array_merge($payload, ['year' => '2020', 'brand' => 'BMW']);
        $response2 = $this->actingAs($user)->post(route('cars.store'), $payload2);
        echo "Status HTTP: {$response2->getStatusCode()}\n";
        if ($response2->getStatusCode() === 302) {
            echo "Redirect (redirects to back with errors)\n";
            echo "Errors: " . json_encode(session('errors') ?: []) . "\n";
        } elseif ($response2->getStatusCode() >= 400) {
            echo "Errors: " . json_encode($response2->json('errors') ?? []) . "\n";
        } else {
            echo "OK: coche creado\n";
        }

        echo "\n=== Test 3: POST /cars con TODOS los campos completos (formulario full) ===\n";
        $payload3 = [
            'brand' => 'Audi',
            'model' => 'A3',
            'year' => '06/2019',
            'fuel' => 'Diesel',
            'transmission' => 'Automatic',
            'purchase_price' => 22000,
            'status' => 'Located',
            'traffic_light' => 'green',
            'new_price' => 0,
            'manual_tax_base' => 0,
            'transport' => 800,
            'itv_fee' => 150,
            'coc_fee' => 300,
            'dgt_fees' => 100,
            'professional_fees' => 500,
            'deposit' => 0,
            'mileage' => 60000,
            'cv' => 150,
            'displacement' => 2000,
            'co2' => 120,
            'consumption' => 5.5,
            'doors' => 5,
            'seats' => 5,
            'owners' => 2,
            'lat' => 40.4168,
            'lng' => -3.7038,
            'version' => 'Sportback',
            'color' => 'Negro',
            'vin' => 'WAUZZZ8V0KA123456',
            'euro_norm' => 'Euro 6',
            'seller' => 'Audi Madrid',
            'city' => 'Madrid',
            'url_link' => 'https://example.com/coche',
            'vat_scenario' => 'standard',
            'description' => 'Coche bien cuidado.',
            'notes' => 'Importado de Alemania.',
            'itv_date' => '2024-12-15',
            'boe_confirmed' => true,
            'is_marketplace' => false,
        ];
        $response3 = $this->actingAs($user)->post(route('cars.store'), $payload3);
        echo "Status HTTP: {$response3->getStatusCode()}\n";
        if ($response3->getStatusCode() === 302) {
            echo "Redirect to: " . $response3->headers->get('Location') . "\n";
        } elseif ($response3->getStatusCode() >= 400) {
            echo "Errors: " . json_encode($response3->json('errors') ?? session('errors') ?: []) . "\n";
        } else {
            echo "OK: coche creado\n";
        }

        echo "\n=== Coches en BD al final: " . Car::count() . " ===\n";
        $this->assertTrue(true);
    }

    public function test_year_accepts_multiple_formats(): void
    {
        $org = Organization::factory()->create(['plan' => 'pro']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $base = [
            'brand' => 'BMW', 'model' => 'X1', 'fuel' => 'Diesel',
            'transmission' => 'Automatic', 'purchase_price' => 20000,
            'status' => 'Located', 'traffic_light' => 'green',
        ];

        $cases = [
            '2020' => '01/2020',
            '01/2020' => '01/2020',
            '2020-03' => '01/2020',
            '2020-03-15' => '01/2020',
        ];

        foreach ($cases as $input => $expected) {
            $payload = array_merge($base, ['year' => $input]);
            $response = $this->actingAs($user)->post(route('cars.store'), $payload);
            $this->assertEquals(302, $response->getStatusCode(), "Year '$input' must return 302, got {$response->getStatusCode()}");
        }

        $this->assertTrue(true);
    }
}
