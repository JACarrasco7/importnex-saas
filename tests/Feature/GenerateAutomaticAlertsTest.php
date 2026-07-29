<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientContactLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GenerateAutomaticAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_generated_for_stale_cars(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        // Car in 'Located' for 40 days
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Located',
            'updated_at' => now()->subDays(40),
        ]);

        Artisan::call('alerts:generate');

        $this->assertDatabaseHas('alerts', [
            'organization_id' => $org->id,
            'alert_type' => 'car_stale',
            'reference_id' => $car->id,
        ]);
    }

    public function test_alerts_generated_for_clients_without_contact(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $client = Client::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Negotiating',
            'created_at' => now()->subDays(20),
        ]);

        Artisan::call('alerts:generate');

        $this->assertDatabaseHas('alerts', [
            'organization_id' => $org->id,
            'alert_type' => 'client_no_contact',
            'reference_id' => $client->id,
        ]);
    }

    public function test_no_duplicate_alerts(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Located',
            'updated_at' => now()->subDays(40),
        ]);

        Artisan::call('alerts:generate');
        Artisan::call('alerts:generate');

        $this->assertEquals(1, Alert::where('alert_type', 'car_stale')->count());
    }
}
