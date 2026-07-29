<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Car>
 */
class CarFactory extends Factory
{
    public function definition(): array
    {
        $brand = fake()->randomElement(['BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Opel', 'Ford']);
        $year = fake()->numberBetween(2015, 2024);

        return [
            'organization_id' => \App\Models\Organization::factory(),
            'client_id' => null,

            // Technical specs
            'brand' => $brand,
            'model' => fake()->randomElement(['3 Series', 'A4', 'C-Class', 'Golf', 'Astra', 'Focus']),
            'version' => fake()->randomElement(['2.0 TDI', '320d', '1.5 TSI', '1.6 CDTI']),
            'year' => sprintf('%02d/%04d', fake()->numberBetween(1, 12), $year),
            'mileage' => fake()->numberBetween(15000, 180000),
            'fuel' => fake()->randomElement(['Diesel', 'Gasoline', 'Hybrid']),
            'transmission' => fake()->randomElement(['Manual', 'Automatic']),
            'cv' => fake()->numberBetween(90, 320),
            'displacement' => fake()->randomElement(['1.499 cc', '1.598 cc', '1.998 cc', '2.497 cc']),
            'co2' => fake()->numberBetween(95, 220),
            'consumption' => fake()->randomElement(['4.5 l/100km', '5.2 l/100km', '6.1 l/100km', '7.8 l/100km']),
            'owners' => fake()->numberBetween(1, 3),
            'doors' => fake()->randomElement(['3', '5']),
            'seats' => fake()->numberBetween(2, 5),
            'euro_norm' => 'Euro 6',
            'color' => fake()->safeColorName(),
            'itv_date' => fake()->randomElement(['Valid until 06/2027', 'New German HU']),

            // Prices and costs
            'purchase_price' => fake()->randomFloat(2, 8000, 45000),
            'new_price' => fake()->randomFloat(2, 20000, 60000),
            'manual_tax_base' => 0,
            'boe_confirmed' => fake()->boolean(50),
            'transport' => fake()->randomFloat(2, 900, 1800),
            'itv_fee' => fake()->randomFloat(2, 150, 400),
            'coc_fee' => fake()->randomFloat(2, 300, 500),
            'dgt_fees' => fake()->randomFloat(2, 80, 120),
            'professional_fees' => fake()->randomFloat(2, 1500, 3500),
            'deposit' => fake()->randomFloat(2, 300, 1500),

            // Seller info and location
            'vin' => fake()->regexify('[A-Z0-9]{17}'),
            'vat_scenario' => 'margin',
            'seller' => fake()->randomElement(['Autohaus Müller', 'Rami Automobile', 'Mobile.de Seller']),
            'city' => fake()->city() . ', ' . fake()->countryCode(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),

            // Status and valuation
            'status' => fake()->randomElement(['Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing', 'Delivered', 'Discarded']),
            'url_link' => 'https://www.mobile.de/en/vehicles/details.html?id=' . fake()->randomNumber(9),
            'traffic_light' => fake()->randomElement(['green', 'amber', 'red', 'neutral']),
            'valuation' => fake()->paragraph(),
            'recommendation' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'equipment' => fake()->randomElements([
                'Navigation', 'Leather', 'Rear Camera', 'LED Headlights', 'Panoramic Roof', 'Heated Seats'
            ], fake()->numberBetween(2, 5)),
            'tips' => fake()->sentences(3),
            'red_flags' => fake()->randomElement([[], ['Pending recall'], ['Many owners']]),
            'comparables_list' => [],
            'fotos_json' => [],
            'notes' => fake()->sentence(),
        ];
    }
}
