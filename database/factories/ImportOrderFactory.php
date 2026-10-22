<?php

namespace Database\Factories;

use App\Enums\ImportOrderState;
use App\Models\Car;
use App\Models\Client;
use App\Models\ImportOrder;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImportOrder>
 */
class ImportOrderFactory extends Factory
{
    protected $model = ImportOrder::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'client_id' => fn (array $attributes) => Client::factory()->create([
                'organization_id' => $attributes['organization_id'],
                'email' => fake()->unique()->safeEmail(),
                'phone' => '600123456',
            ])->id,
            'car_id' => fn (array $attributes) => Car::factory()->create([
                'organization_id' => $attributes['organization_id'],
                'client_id' => $attributes['client_id'],
                'purchase_price' => 15000,
                'professional_fees' => 1500,
            ])->id,
            'reference' => 'JJIM-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'state' => ImportOrderState::Draft,
            'state_changed_at' => now(),
            'fee_amount' => 1500,
        ];
    }

    public function inState(ImportOrderState $state): static
    {
        return $this->state(['state' => $state]);
    }

    /** Genera el token del portal y lo deja accesible en $order->portal_token. */
    public function withPortal(): static
    {
        return $this->afterCreating(function (ImportOrder $order) {
            $token = Str::random(48);
            $order->forceFill(['portal_token' => $token, 'portal_token_hash' => hash('sha256', $token)])->save();
        });
    }
}
