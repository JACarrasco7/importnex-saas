<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Organization::firstOrCreate(
            ['name' => 'JJ Import Motors'],
            [
                'plan' => 'pro',
                'stripe_id' => null,
                'trial_ends_at' => now()->addDays(config('subscription.trial_days')),
                'subscribed_at' => null,
            ]
        );

        \App\Models\Organization::factory(5)->create();
    }
}
