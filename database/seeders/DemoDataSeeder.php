<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\CarDocument;
use App\Models\CarExpense;
use App\Models\CarPhoto;
use App\Models\Client;
use App\Models\ClientContactLog;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // HARD GUARD: nunca correr en produccion. Triple check (env + APP_ENV + APP_DEBUG).
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder skipped in production.');

            return;
        }
        if (config('app.env') === 'production') {
            $this->command?->warn('DemoDataSeeder skipped (app.env=production).');

            return;
        }
        if (! config('app.debug')) {
            $this->command?->warn('DemoDataSeeder skipped (APP_DEBUG=false).');

            return;
        }

        // Empresa ficticia propia: nunca se mezcla con una empresa real (antes
        // reutilizaba «JJ Import Motors» por nombre y metía coches de demo en ella).
        $org = Organization::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Importaciones Demo',
                'plan' => 'pro',
                'is_public' => true,
                'trial_ends_at' => now()->addDays(30),
                'company' => ['city' => 'Sevilla', 'phone' => '600 000 000', 'email' => 'demo@example.com'],
            ]
        );

        // Contraseña desde .env (SEED_DEMO_PASSWORD) o una aleatoria que se muestra al final.
        $password = (string) (env('SEED_DEMO_PASSWORD') ?: Str::password(14, symbols: false));

        User::updateOrCreate(
            ['email' => 'demo-owner@example.com'],
            [
                'name' => 'Demo (propietario)',
                'password' => Hash::make($password),
                'organization_id' => $org->id,
                'role' => 'owner',
                'locale' => config('app.locale', 'es'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo-staff@example.com'],
            [
                'name' => 'Demo (operador)',
                'password' => Hash::make($password),
                'organization_id' => $org->id,
                'role' => 'operator',
                'locale' => config('app.locale', 'es'),
                'email_verified_at' => now(),
            ]
        );

        // Cars (6)
        $statuses = ['Located', 'Offered', 'Reserved', 'Delivered', 'Located', 'Offered'];
        $cars = [];
        for ($i = 0; $i < 6; $i++) {
            $cars[] = Car::factory()->create([
                'organization_id' => $org->id,
                'status' => $statuses[$i],
                'brand' => ['BMW', 'Audi', 'Mercedes', 'Volkswagen', 'Porsche', 'Tesla'][$i],
                'model' => ['320d', 'A4', 'C220', 'Golf', '911', 'Model 3'][$i],
                'year' => 2019 + ($i % 4),
                'mileage' => 30000 + ($i * 12000),
                'new_price' => 18000 + ($i * 4500),
                // Los no vendidos salen en la web de demo (/web/demo).
                'is_marketplace' => $statuses[$i] !== 'Delivered',
            ]);
        }

        // For each car, add photos, checklist, document, expense
        foreach ($cars as $car) {
            CarPhoto::factory(3)->create(['car_id' => $car->id, 'organization_id' => $org->id]);
            CarChecklist::factory()->create(['car_id' => $car->id, 'organization_id' => $org->id]);
            CarDocument::factory(2)->create(['car_id' => $car->id, 'organization_id' => $org->id]);
            CarExpense::factory(2)->create(['car_id' => $car->id, 'organization_id' => $org->id]);
        }

        // Clients (8)
        $clients = [];
        for ($i = 0; $i < 8; $i++) {
            $clients[] = Client::factory()->create([
                'organization_id' => $org->id,
                'status' => ['active', 'lead', 'inactive'][$i % 3],
            ]);
        }

        // Contacts (12)
        for ($i = 0; $i < 12; $i++) {
            Contact::factory()->create([
                'organization_id' => $org->id,
                'client_id' => $clients[$i % 8]->id,
            ]);
        }

        // Contact logs
        foreach ($clients as $client) {
            ClientContactLog::factory(2)->create([
                'organization_id' => $org->id,
                'client_id' => $client->id,
            ]);
        }

        $this->command->info('✅ Demo data seeded for '.$org->name);
        $this->command->info('   - 6 cars, 8 clients, 12 contacts, 16 contact logs');
        $this->command->info('   - Login: demo-owner@example.com / '.$password);
        $this->command->info('   - Web pública: '.url('/web/demo'));
    }
}
