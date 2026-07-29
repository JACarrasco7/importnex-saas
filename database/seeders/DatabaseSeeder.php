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
        $this->call([
            OrganizationSeeder::class,
            MessageTemplatesSeeder::class,
        ]);

        $jjImport = Organization::where('name', 'JJ Import Motors')->first();
        if ($jjImport) {
            User::updateOrCreate(
                ['email' => 'carra@admin.com'],
                [
                    'name' => 'Carra',
                    'password' => Hash::make('joselete7'),
                    'organization_id' => $jjImport->id,
                    'role' => 'owner',
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => 'jmepegounpeo@admin.com'],
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
}
