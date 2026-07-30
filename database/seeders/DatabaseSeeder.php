<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Solo crear la organización JJ Import Motors y los 2 usuarios específicos.
        // NO se siembran datos demo automáticamente.

        $jjImport = Organization::firstOrCreate(
            ['name' => 'JJ Import Motors'],
            [
                'plan' => 'pro',
                'trial_ends_at' => now()->addDays(30),
            ]
        );

        User::updateOrCreate(
            ['email' => 'carra@jjimportmotors.com'],
            [
                'name' => 'Carra',
                'password' => Hash::make('joselete7'),
                'organization_id' => $jjImport->id,
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'jmepegounpeo@jjimportmotors.com'],
            [
                'name' => 'Jmepegounpeo',
                'password' => Hash::make('paraquelaquieresabermecagoentoquelargalaputacontrasehna'),
                'organization_id' => $jjImport->id,
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );
    }
}
