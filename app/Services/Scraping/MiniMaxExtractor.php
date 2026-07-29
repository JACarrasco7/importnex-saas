<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MiniMax M3 (Anthropic-compatible) — fallback extractor #1.
 *
 * Uses the Anthropic Messages API format exposed at api.minimax.io/anthropic.
 * Same schema and prompts as Mistral extractor, just different wire format.
 *
 * Endpoint: https://api.minimax.io/anthropic/v1/messages
 * Model:    MiniMax-M3
 */
class MiniMaxExtractor implements AiExtractorInterface
{
    public function name(): string
    {
        return 'minimax';
    }

    public function extract(string $html, string $url): array
    {
        $apiKey = config('services.minimax.api_key');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'MINIMAX_API_KEY not configured',
                'provider' => $this->name(),
            ];
        }

        $truncated = $this->truncateHtml($html);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(45)->post(config('services.minimax.base_url') . '/v1/messages', [
                'model' => config('services.minimax.model', 'MiniMax-M3'),
                'max_tokens' => 800,
                'temperature' => 0.1,
                'system' => 'Eres un extractor de datos vehiculares. Devuelves SOLO JSON válido sin markdown. Schema: {brand, model, version, year (int|null), mileage (int|null), fuel, transmission, cv (int|null), co2 (int|null), color, purchase_price (int|null), city, description}. Si un campo no aparece, usa null. year/mileage/cv/co2/purchase_price son enteros sin separadores.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Fuente: {$url}\n\nHTML (recortado):\n{$truncated}",
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('MiniMax extractor HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                    'provider' => $this->name(),
                ];
            }

            $content = $response->json('content.0.text');
            if (empty($content)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from MiniMax',
                    'provider' => $this->name(),
                ];
            }

            $data = $this->parseJson($content);
            if ($data === null) {
                return [
                    'success' => false,
                    'error' => 'MiniMax returned non-JSON: ' . substr($content, 0, 100),
                    'provider' => $this->name(),
                ];
            }

            return [
                'success' => true,
                'data' => $this->normalize($data),
                'provider' => $this->name(),
            ];
        } catch (\Throwable $e) {
            Log::error('MiniMax extractor exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $this->name(),
            ];
        }
    }

    /**
     * MiniMax M3 sometimes wraps JSON in markdown ```json fences.
     * Strip them defensively before decoding.
     */
    private function parseJson(string $content): ?array
    {
        $clean = trim($content);
        $clean = preg_replace('/^```(?:json)?\s*\n?/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\n?```\s*$/i', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function truncateHtml(string $raw): string
    {
        $first = ltrim($raw);
        $looksMarkdown = str_starts_with($first, '#') || str_starts_with($first, 'Title:')
            || (str_contains($raw, '](') && !str_contains(substr($raw, 0, 500), '<div'));

        if ($looksMarkdown) {
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
