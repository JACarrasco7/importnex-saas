<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Instalación limpia: crea la organización operadora de la plataforma y su
 * usuario administrador a partir de .env (SEED_ADMIN_*). Nunca contiene
 * credenciales reales. Datos de demo: `php artisan db:seed --class=DemoDataSeeder`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => env('SEED_ORGANIZATION_NAME', config('app.name'))],
            ['plan' => 'enterprise', 'is_owner' => true, 'is_public' => true],
        );

        $email = env('SEED_ADMIN_EMAIL');
        if (blank($email)) {
            $this->command?->warn('SEED_ADMIN_EMAIL vacío: no se crea usuario administrador.');

            return;
        }

        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(16);

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('SEED_ADMIN_NAME', 'Admin'),
                'password' => Hash::make($password),
                'organization_id' => $organization->id,
                'role' => 'owner',
                'email_verified_at' => now(),
            ],
        );

        if (blank(env('SEED_ADMIN_PASSWORD'))) {
            $this->command?->info("Usuario {$email} creado con contraseña temporal: {$password}");
        }
    }
}
