<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarChecklist>
 */
class CarChecklistFactory extends Factory
{
    public function definition(): array
    {
        $kind = $this->faker->randomElement([
            CarChecklist::KIND_MILESTONE,
            CarChecklist::KIND_INSPECTION,
        ]);
        $priority = $kind === CarChecklist::KIND_INSPECTION
            ? $this->faker->randomElement([
                CarChecklist::PRIORITY_CRITICAL,
                CarChecklist::PRIORITY_IMPORTANT,
                CarChecklist::PRIORITY_MINOR,
            ])
            : null;

        return [
            'car_id' => Car::factory(),
            'organization_id' => Organization::factory(),
            'item_key' => $this->faker->randomElement([
                'itv_done', 'coc_received', 'transport_booked', 'client_paid',
                'tire_check', 'oil_level', 'lights_test',
            ]),
            'kind' => $kind,
            'priority' => $priority,
            'section' => $kind === CarChecklist::KIND_INSPECTION
                ? $this->faker->randomElement(['Exterior', 'Interior', 'Engine'])
                : null,
            'completed' => $this->faker->boolean(),
            'completed_at' => $this->faker->optional(0.5)->dateTime(),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
