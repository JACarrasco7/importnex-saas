<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientContactLog>
 */
class ClientContactLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'client_id' => Client::factory(),
            'contact_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'channel' => $this->faker->randomElement(['phone', 'email', 'whatsapp', 'in_person']),
            'summary' => $this->faker->sentence(),
        ];
    }
}
