<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'name' => fake()->name(),
            'contact_info' => fake()->phoneNumber(),
            'looking_for' => fake()->randomElement(['BMW 3 Series', 'Mercedes C-Class', 'Audi A4']),
            'budget_min' => fake()->numberBetween(10000, 25000),
            'budget_max' => fake()->numberBetween(25000, 50000),
            'status' => fake()->randomElement(['New', 'Briefing', 'Quote sent', 'Negotiating', 'Order signed', 'In process', 'Delivered']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
