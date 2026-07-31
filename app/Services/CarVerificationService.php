<?php

namespace App\Services;

use App\Models\Car;
use App\Services\Ai\AiService;

/**
 * Sends a Car's data to the org's configured AI provider and returns a
 * structured analysis (traffic light, valuation, recommendation, red flags,
 * tips).
 */
class CarVerificationService
{
    public function __construct(private readonly AiService $ai) {}

    public function verify(Car $car): array
    {
        $org = $car->organization;
        if (!$org) {
            return ['success' => false, 'error' => 'Car has no organization'];
        }

        $carData = [
            'brand' => $car->brand,
            'model' => $car->model,
            'version' => $car->version,
            'year' => $car->year,
            'mileage' => $car->mileage,
            'fuel' => $car->fuel,
            'transmission' => $car->transmission,
            'cv' => $car->cv,
            'displacement' => $car->displacement,
            'co2' => $car->co2,
            'consumption' => $car->consumption,
            'owners' => $car->owners,
            'doors' => $car->doors,
            'seats' => $car->seats,
            'euro_norm' => $car->euro_norm,
            'color' => $car->color,
            'itv_date' => $car->itv_date,
            'purchase_price' => $car->purchase_price,
            'new_price' => $car->new_price,
            'manual_tax_base' => $car->manual_tax_base,
            'transport' => $car->transport,
            'itv_fee' => $car->itv_fee,
            'coc_fee' => $car->coc_fee,
            'dgt_fees' => $car->dgt_fees,
            'professional_fees' => $car->professional_fees,
            'vin' => $car->vin,
            'seller' => $car->seller,
            'city' => $car->city,
            'url_link' => $car->url_link,
            'description' => $car->description,
            'equipment' => $car->equipment,
            'tips' => $car->tips,
            'red_flags' => $car->red_flags,
            'notes' => $car->notes,
        ];

        $schema = '{traffic_light: "green|amber|red", valuation: string, recommendation: string, red_flags: string[], tips: string[]}';

        $systemPrompt = <<<PROMPT
You are an expert car importer assistant specialized in evaluating used vehicles from Germany for resale in Spain.
Your task is to analyze a car listing and produce:

1. **traffic_light**: 'green' (excellent deal), 'amber' (acceptable with caution), or 'red' (avoid)
2. **valuation**: A short paragraph with your assessment of whether the price is fair for the Spanish market
3. **recommendation**: A short paragraph with clear next steps (e.g. "Negotiate €X down" or "Walk away, CO2 too high")
4. **red_flags**: Array of strings listing concerns (high mileage for year, expensive transport, missing ITV, etc.)
5. **tips**: Array of strings with negotiation or verification tips

Respond ONLY with valid JSON in this exact structure: {$schema}
PROMPT;

        $result = $this->ai->chatJson(
            $org,
            $systemPrompt,
            json_encode($carData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'unknown', 'provider' => $result['provider'] ?? null];
        }

        $parsed = $result['data'];

        return [
            'success' => true,
            'analysis' => [
                'traffic_light' => $parsed['traffic_light'] ?? 'neutral',
                'valuation' => $parsed['valuation'] ?? '',
                'recommendation' => $parsed['recommendation'] ?? '',
                'red_flags' => $parsed['red_flags'] ?? [],
                'tips' => $parsed['tips'] ?? [],
            ],
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
        ];
    }
}
