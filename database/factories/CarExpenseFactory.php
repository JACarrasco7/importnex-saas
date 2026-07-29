<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarExpense>
 */
class CarExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'concept' => $this->faker->randomElement(['Transport', 'ITV', 'COC', 'DGT fees', 'Professional fees']),
            'estimated' => $this->faker->randomFloat(2, 100, 2000),
            'actual' => $this->faker->randomFloat(2, 100, 2000),
            'notes' => $this->faker->sentence(),
        ];
    }
}
