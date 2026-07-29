<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarDocument;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarDocument>
 */
class CarDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(3, true) . '.pdf',
            'doc_key' => $this->faker->randomElement([
                'kaufvertrag', 'coc', 'fahrzeugbrief', 'fahrzeugschein', 'scheckheft',
                'payment_proof', 'transport_contract', 'transport_invoice',
                'itv_import', 'ficha_tecnica_es', 'iedmt_576', 'ivtm',
                'permiso_circulacion', 'cliente_dni', 'cliente_contrato',
                'senal_recibo', 'seguro',
            ]),
            'doc_type' => $this->faker->randomElement(['invoice', 'contract', 'permit', 'insurance', 'registration']),
            'group' => $this->faker->randomElement([
                CarDocument::GROUP_SELLER_ORIGIN,
                CarDocument::GROUP_PURCHASE_TRANSPORT,
                CarDocument::GROUP_SPAIN_PROCEDURES,
            ]),
            'status' => $this->faker->randomElement([
                CarDocument::STATUS_PENDING,
                CarDocument::STATUS_ORDERED,
                CarDocument::STATUS_RECEIVED,
                CarDocument::STATUS_NOT_APPLICABLE,
            ]),
            'url' => 'cars/' . $this->faker->uuid() . '.pdf',
            'uploaded_at' => now(),
        ];
    }
}
