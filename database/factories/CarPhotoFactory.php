<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarPhoto>
 */
class CarPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => \App\Models\Car::factory(),
            'organization_id' => \App\Models\Organization::factory(),
            'url' => 'cars/' . $this->faker->uuid() . '.jpg',
            'photo_type' => $this->faker->randomElement(['exterior', 'interior', 'engine', 'defect']),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
