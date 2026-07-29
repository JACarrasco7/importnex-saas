<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GLM-4.5-Air (Z.AI, OpenAI-compatible) — fallback extractor #2.
 *
 * Z.AI exposes an OpenAI-compatible Chat Completions API.
 * Model glm-4.5-air offers strong extraction at very low cost.
 *
 * Endpoint: https://api.z.ai/api/paas/v4/chat/completions
 * Model:    glm-4.5-air
 */
class GlmExtractor implements AiExtractorInterface
{
    public function name(): string
    {
        return 'glm';
    }

    public function extract(string $html, string $url): array
    {
        $apiKey = config('services.glm.api_key');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'GLM_API_KEY not configured',
                'provider' => $this->name(),
            ];
        }

        $truncated = $this->truncateHtml($html);

        $schema = '{brand: string|null, model: string|null, version: string|null, year: int|null, mileage: int|null, fuel: string|null, transmission: string|null, cv: int|null, co2: int|null, color: string|null, purchase_price: int|null, city: string|null, description: string|null}';

        $systemPrompt = "Eres un extractor de datos vehiculares. Devuelves SOLO JSON válido sin markdown ni explicaciones.\n"
            . "Schema: {$schema}\n"
            . "Reglas:\n"
            . "- Si un campo no aparece en el HTML, usa null.\n"
            . "- year, mileage, cv, co2, purchase_price son enteros sin separadores.\n"
            . "- cv = caballos fiscales (PS/CV), no kW.\n"
            . "- mileage en km.\n"
            . "- purchase_price en EUR sin moneda ni puntos.\n"
            . "- description = resumen en una frase si hay texto libre.";

        $userPrompt = "Fuente: {$url}\n\nHTML (recortado):\n{$truncated}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(45)->post(config('services.glm.base_url') . '/chat/completions', [
                'model' => config('services.glm.model', 'glm-4.5-air'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.1,
                'max_tokens' => 800,
            ]);

            if ($response->failed()) {
                Log::warning('GLM extractor HTTP error', [
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
                    'error' => 'Empty response from GLM',
                    'provider' => $this->name(),
                ];
            }

            $data = $this->parseJson($content);
            if ($data === null) {
                return [
                    'success' => false,
                    'error' => 'GLM returned non-JSON: ' . substr($content, 0, 100),
                    'provider' => $this->name(),
                ];
            }

            return [
                'success' => true,
                'data' => $this->normalize($data),
                'provider' => $this->name(),
            ];
        } catch (\Throwable $e) {
            Log::error('GLM extractor exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $this->name(),
            ];
        }
    }

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
