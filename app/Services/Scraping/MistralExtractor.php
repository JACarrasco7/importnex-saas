<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mistral Small 4 — primary extractor for car listing scraping.
 *
 * Uses Mistral's native JSON mode (`response_format`) which guarantees
 * well-formed JSON output without markdown wrappers.
 *
 * Endpoint: https://api.mistral.ai/v1/chat/completions
 * Model:    mistral-small-latest (configurable)
 */
class MistralExtractor implements AiExtractorInterface
{
    public function name(): string
    {
        return 'mistral';
    }

    public function extract(string $html, string $url): array
    {
        $apiKey = config('services.mistral.api_key');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'MISTRAL_API_KEY not configured',
                'provider' => $this->name(),
            ];
        }

        $truncated = $this->truncateHtml($html);

        $prompt = $this->buildPrompt($truncated, $url);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(45)->post(config('services.mistral.base_url') . '/chat/completions', [
                'model' => config('services.mistral.model', 'mistral-small-latest'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
                'max_tokens' => 800,
            ]);

            if ($response->failed()) {
                Log::warning('Mistral extractor HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                    'provider' => $this->name(),
                ];
            }

            $content = $response->json('choices.0.message.content');
            if (empty($content)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from Mistral',
                    'provider' => $this->name(),
                ];
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'error' => 'Mistral returned non-JSON: ' . substr($content, 0, 100),
                    'provider' => $this->name(),
                ];
            }

            return [
                'success' => true,
                'data' => $this->normalize($data),
                'provider' => $this->name(),
            ];
        } catch (\Throwable $e) {
            Log::error('Mistral extractor exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $this->name(),
            ];
        }
    }

    private function buildPrompt(string $html, string $url): string
    {
        $schema = '{'
            . '"brand": string|null, '
            . '"model": string|null, '
            . '"version": string|null, '
            . '"year": int|null, '
            . '"mileage": int|null, '
            . '"fuel": string|null, '
            . '"transmission": string|null, '
            . '"cv": int|null, '
            . '"co2": int|null, '
            . '"color": string|null, '
            . '"purchase_price": int|null, '
            . '"city": string|null, '
            . '"description": string|null'
            . '}';

        return "Eres un extractor de datos vehiculares. Devuelves SOLO JSON válido sin markdown.\n\n"
            . "Fuente: {$url}\n\n"
            . "Schema esperado: {$schema}\n\n"
            . "Reglas:\n"
            . "- Si un campo no aparece en el HTML, usa null.\n"
            . "- 'year', 'mileage', 'cv', 'co2', 'purchase_price' son números enteros (sin separadores).\n"
            . "- 'cv' = caballos (PS/CV/Pferd), no kW.\n"
            . "- 'mileage' en km.\n"
            . "- 'purchase_price' en EUR sin moneda ni puntos.\n"
            . "- 'description' = resumen en una frase si hay texto libre.\n\n"
            . "HTML (recortado):\n{$html}";
    }

    /**
     * Clean the payload — handles both HTML and markdown input.
     * Jina Reader returns markdown (clean, JS-rendered); plain HTTP returns raw HTML.
     */
    private function truncateHtml(string $raw): string
    {
        // If markdown (from Jina), keep headings/list markers as semantic signal.
        if ($this->looksLikeMarkdown($raw)) {
            $clean = $raw;
        } else {
            $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $raw) ?? $raw;
            $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $clean) ?? $clean;
            $clean = preg_replace('/<[^>]+>/', ' ', $clean) ?? $clean;
            $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;
            $clean = trim($clean);
        }

        if (mb_strlen($clean) > 40000) {
            $clean = mb_substr($clean, 0, 40000) . '...';
        }

        return $clean;
    }

    private function looksLikeMarkdown(string $text): bool
    {
        // Markdown heuristics: starts with # or contains links/images without HTML.
        $first = ltrim($text);
        return str_starts_with($first, '#') || str_starts_with($first, 'Title:')
            || (str_contains($text, '](') && !str_contains(substr($text, 0, 500), '<div'));
    }

    private function normalize(array $data): array
    {
        $int = static fn($v) => is_numeric($v) ? (int) $v : null;
        $str = static fn($v) => is_string($v) && trim($v) !== '' ? trim($v) : null;

        return [
            'brand' => $str($data['brand'] ?? null),
            'model' => $str($data['model'] ?? null),
            'version' => $str($data['version'] ?? null),
            'year' => $int($data['year'] ?? null),
            'mileage' => $int($data['mileage'] ?? null),
            'fuel' => $str($data['fuel'] ?? null),
            'transmission' => $str($data['transmission'] ?? null),
            'cv' => $int($data['cv'] ?? null),
            'co2' => $int($data['co2'] ?? null),
            'color' => $str($data['color'] ?? null),
            'purchase_price' => $int($data['purchase_price'] ?? null),
            'city' => $str($data['city'] ?? null),
            'description' => $str($data['description'] ?? null),
        ];
    }
}
