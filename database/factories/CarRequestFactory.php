<?php

namespace Database\Factories;

use App\Models\CarRequest;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarRequestFactory extends Factory
{
    protected $model = CarRequest::class;

    public function definition(): array
    {
        $fuelTypes = ['Diesel', 'Gasolina', 'Híbrido', 'Eléctrico', 'Gas'];
        $transmissions = ['Manual', 'Automático'];
        $bodyTypes = ['Berlina', 'SUV', 'Compacto', 'Monovolumen', 'Coupe', 'Cabrio'];

        return [
            'organization_id' => Organization::factory(),
            'client_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'brand' => fake()->randomElement(['BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Seat', 'Opel', 'Toyota']),
            'model' => fake()->randomElement(['Serie 3', 'Clase C', 'A4', 'Golf', 'Ibiza', 'Astra', 'Corolla']),
            'year_min' => fake()->numberBetween(2015, 2020),
            'year_max' => fake()->numberBetween(2021, 2026),
            'budget_min' => fake()->numberBetween(10000, 20000),
            'budget_max' => fake()->numberBetween(25000, 45000),
            'mileage_max' => fake()->numberBetween(50000, 150000),
            'fuel' => fake()->randomElement($fuelTypes),
            'transmission' => fake()->randomElement($transmissions),
            'body_type' => fake()->randomElement($bodyTypes),
            'doors' => fake()->randomElement([3, 5]),
            'seats' => fake()->randomElement([5, 7]),
            'color' => fake()->randomElement(['Negro', 'Blanco', 'Gris', 'Azul', 'Rojo']),
            'requirements' => fake()->optional()->text(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }
}
