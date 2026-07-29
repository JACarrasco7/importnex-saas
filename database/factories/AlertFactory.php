<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'alert_type' => $this->faker->randomElement(['car_inactive', 'client_pending', 'document_missing', 'verification_failed']),
            'reference_type' => 'car',
            'reference_id' => 1,
            'message' => $this->faker->sentence(),
            'resolved' => false,
        ];
    }
}
