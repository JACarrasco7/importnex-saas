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

        $schema = json_encode([
            'traffic_light' => 'green|amber|red',
            'valuation' => 'string',
            'recommendation' => 'string',
            'red_flags' => ['string'],
            'tips' => ['string'],
            'verdict' => 'Buy|Buy if price drops|Doubtful|Discard|null',
            'verdict_confidence' => 'high|medium|low|null',
            'verdict_reasoning' => 'string|null',
            'market_avg' => 'number|null',
            'market_min' => 'number|null',
            'market_max' => 'number|null',
            'estimated_saving' => 'number|null',
            'pros' => ['string'],
            'cons' => ['string'],
        ], JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
You are an expert car importer assistant specialized in evaluating used vehicles from Germany for resale in Spain.
Your task is to analyze a car listing and produce a complete verification report.

You MUST respond with valid JSON using exactly this schema (note: every numeric field is a number or null; every text field is a string or null; arrays default to []; verdict ∈ {Buy, "Buy if price drops", Doubtful, Discard}):

{$schema}

Field guidelines:
1. **traffic_light**: green = excellent deal, amber = acceptable with caution, red = avoid
2. **valuation**: short paragraph, fair-price assessment for the Spanish market
3. **recommendation**: short paragraph, next steps (e.g. "Negotiate €X down" or "Walk away, CO2 too high")
4. **red_flags**: array of concerns (high mileage, expensive transport, missing ITV...)
5. **tips**: array of negotiation / verification tips
6. **verdict**: overall business verdict, or null when you cannot decide
7. **verdict_confidence**: high / medium / low, or null
8. **verdict_reasoning**: 1-2 sentences explaining the verdict
9. **market_avg / market_min / market_max**: estimated Spanish-market price (EUR, no thousands separator), or null when unknown
10. **estimated_saving**: bargain amount vs the asking price, EUR, or null
11. **pros / cons**: short bullet lists

Output ONLY the JSON object, no markdown fences, no extra text.
PROMPT;

        $result = $this->ai->chatJson(
            $org,
            $systemPrompt,
            json_encode($carData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'unknown', 'provider' => $result['provider'] ?? null];
        }

        $parsed = $result['data'] ?? [];

        $normalizeInt = static fn($v) => is_numeric($v) ? (int) $v : null;

        // Keep the legacy short shape for backward compatibility (status emails,
        // alerts, etc.) plus expose the full enriched payload for the modal UI.
        $short = [
            'traffic_light' => $parsed['traffic_light'] ?? 'neutral',
            'valuation' => $parsed['valuation'] ?? '',
            'recommendation' => $parsed['recommendation'] ?? '',
            'red_flags' => $parsed['red_flags'] ?? [],
            'tips' => $parsed['tips'] ?? [],
        ];

        $full = array_merge($short, [
            'verdict' => $parsed['verdict'] ?? null,
            'verdict_confidence' => $parsed['verdict_confidence'] ?? null,
            'verdict_reasoning' => $parsed['verdict_reasoning'] ?? null,
            'market_avg' => $normalizeInt($parsed['market_avg'] ?? null),
            'market_min' => $normalizeInt($parsed['market_min'] ?? null),
            'market_max' => $normalizeInt($parsed['market_max'] ?? null),
            'estimated_saving' => $normalizeInt($parsed['estimated_saving'] ?? null),
            'pros' => $parsed['pros'] ?? [],
            'cons' => $parsed['cons'] ?? [],
        ]);

        return [
            'success' => true,
            'analysis' => $short,
            'analysis_full' => $full,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
        ];
    }
}
