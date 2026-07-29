<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for AI-powered car verification using Claude API.
 */
class CarVerificationService
{
    private string $endpoint = 'https://api.anthropic.com/v1/messages';
    private string $model = 'claude-3-5-sonnet-20241022';

    /**
     * Verify a car listing by sending its data to Claude for analysis.
     */
    public function verify(Car $car): array
    {
        $payload = $this->buildPayload($car);

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->endpoint, $payload);

            if ($response->failed()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => 'API request failed with status ' . $response->status(),
                ];
            }

            $data = $response->json();
            $text = $data['content'][0]['text'] ?? '';

            return [
                'success' => true,
                'analysis' => $this->parseAnalysis($text),
                'raw_response' => $text,
            ];
        } catch (\Exception $e) {
            Log::error('Car verification exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the Claude API payload with system prompt and car data.
     */
    private function buildPayload(Car $car): array
    {
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

        $systemPrompt = <<<PROMPT
You are an expert car importer assistant specialized in evaluating used vehicles from Germany for resale in Spain.
Your task is to analyze a car listing and produce:

1. **traffic_light**: 'green' (excellent deal), 'amber' (acceptable with caution), or 'red' (avoid)
2. **valuation**: A short paragraph with your assessment of whether the price is fair for the Spanish market
3. **recommendation**: A short paragraph with clear next steps (e.g. "Negotiate €X down" or "Walk away, CO2 too high")
4. **red_flags**: Array of strings listing concerns (high mileage for year, expensive transport, missing ITV, etc.)
5. **tips**: Array of strings with negotiation or verification tips

Respond ONLY with valid JSON in this exact structure:
{
  "traffic_light": "green|amber|red",
  "valuation": "...",
  "recommendation": "...",
  "red_flags": ["...", "..."],
  "tips": ["...", "..."]
}
PROMPT;

        return [
            'model' => $this->model,
            'max_tokens' => 1500,
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => json_encode($carData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }

    /**
     * Parse Claude's response and extract structured data.
     */
    private function parseAnalysis(string $text): array
    {
        // Try to find JSON block in markdown code fences
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $json = $matches[1];
        } else {
            // Try to find raw JSON
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json = $matches[0];
            } else {
                return [
                    'traffic_light' => 'neutral',
                    'valuation' => $text,
                    'recommendation' => '',
                    'red_flags' => [],
                    'tips' => [],
                ];
            }
        }

        $parsed = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'traffic_light' => 'neutral',
                'valuation' => $text,
                'recommendation' => '',
                'red_flags' => [],
                'tips' => [],
            ];
        }

        return [
            'traffic_light' => $parsed['traffic_light'] ?? 'neutral',
            'valuation' => $parsed['valuation'] ?? '',
            'recommendation' => $parsed['recommendation'] ?? '',
            'red_flags' => $parsed['red_flags'] ?? [],
            'tips' => $parsed['tips'] ?? [],
        ];
    }
}
