<?php

namespace Database\Factories;

use App\Models\InvestigationCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestigationCache>
 */
class InvestigationCacheFactory extends Factory
{
    public function definition(): array
    {
        $brand = fake()->randomElement(['BMW', 'Audi', 'Opel', 'Mercedes', 'Volkswagen']);
        $model = fake()->randomElement(['320d', 'A4', 'Astra', 'C220', 'Golf']);

        return [
            'clave_modelo' => strtolower($model).'-'.fake()->year().'-'.fake()->unique()->numberBetween(100000, 999999),
            'marca' => $brand,
            'modelo' => $model,
            'potencia' => fake()->numberBetween(110, 320),
            'combustible' => fake()->randomElement(['Diesel', 'Gasoline']),
            'aspectos' => [
                'problemas_comunes' => ['finding' => 'OK', 'source_url' => 'https://example.com', 'date' => '2026-01-01'],
            ],
            'organization_id' => null,
        ];
    }
}
