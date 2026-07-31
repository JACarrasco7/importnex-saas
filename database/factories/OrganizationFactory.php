<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'plan' => fake()->randomElement(['starter', 'pro', 'enterprise']),
            'stripe_id' => fake()->uuid(),
            'trial_ends_at' => fake()->boolean(70) ? now()->addDays(config('subscription.trial_days')) : null,
            'subscribed_at' => fake()->boolean(30) ? now() : null,
            // AI settings: 70% of orgs have nothing configured by default to avoid
            // stray HTTP calls in unrelated tests.
            'ai_provider' => null,
            'ai_model' => null,
            'ai_api_key' => null,
        ];
    }

    public function withAi(string $provider = 'mistral', string $model = null, string $key = 'test-key'): static
    {
        return $this->state(fn () => [
            'ai_provider' => $provider,
            'ai_model' => $model,
            'ai_api_key' => $key,
        ]);
    }
}
