<?php

namespace App\Services\Scraping;

use App\Models\Organization;
use App\Services\Ai\AiService;
use Illuminate\Support\Facades\Log;

/**
 * Generic extractor: delegates the LLM call to the org's configured AI
 * provider via AiService. Replaces the old MistralExtractor / MiniMaxExtractor
 * / GlmExtractor as separate primary/fallback chain — now any provider the
 * org chose handles scraping.
 */
class GenericAiExtractor implements AiExtractorInterface
{
    public function __construct(private readonly AiService $ai) {}

    public function name(): string
    {
        return 'generic';
    }

    public function extract(string $html, string $url, ?Organization $org = null): array
    {
        $org ??= auth()->user()?->organization;
        if (!$org) {
            return [
                'success' => false,
                'error' => 'No authenticated organization context',
                'provider' => 'none',
            ];
        }

        if (!$org->hasAiConfigured()) {
            return [
                'success' => false,
                'error' => 'No AI provider configured for this organization. Set one in Settings → Organization.',
                'provider' => 'none',
            ];
        }

        $truncated = $this->truncateHtml($html);

        $schema = '{brand: string|null, model: string|null, version: string|null, year: int|null, mileage: int|null, fuel: string|null, transmission: string|null, cv: int|null, co2: int|null, color: string|null, purchase_price: int|null, city: string|null, description: string|null}';

        $systemPrompt = "Eres un extractor de datos vehiculares. Devuelves SOLO JSON válido sin markdown.\n"
            . "Schema: {$schema}\n"
            . "Reglas:\n"
            . "- Si un campo no aparece en el HTML, usa null.\n"
            . "- 'year', 'mileage', 'cv', 'co2', 'purchase_price' son números enteros (sin separadores).\n"
            . "- 'cv' = caballos (PS/CV/Pferd), no kW.\n"
            . "- 'mileage' en km.\n"
            . "- 'purchase_price' en EUR sin moneda ni puntos.\n"
            . "- 'description' = resumen en una frase si hay texto libre.";

        $userPrompt = "Fuente: {$url}\n\nHTML (recortado):\n{$truncated}";

        $result = $this->ai->chatJson($org, $systemPrompt, $userPrompt);

        if (!$result['success']) {
            Log::warning('Car scraping AI call failed', [
                'error' => $result['error'] ?? null,
                'provider' => $result['provider'] ?? null,
            ]);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'unknown',
                'provider' => $result['provider'] ?? 'unknown',
            ];
        }

        return [
            'success' => true,
            'data' => $this->normalize($result['data']),
            'provider' => $result['provider'] ?? 'unknown',
        ];
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
