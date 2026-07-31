<?php

namespace App\Services\Ai;

use App\Models\Organization;

/**
 * Single entry point for any AI call inside the app.
 *
 * Resolves the provider + API key + model from the given organization
 * (or an explicit override) and dispatches the request.
 */
class AiService
{
    public function __construct(private readonly AiProviderRegistry $registry) {}

    /**
     * Chat completion using the org's configured provider.
     *
     * @param  array{system?: string, messages: array<int, array{role: string, content: string}>, max_tokens?: int, temperature?: float, json_mode?: bool}  $params
     * @return array{success: bool, text?: string, error?: string, provider?: string, model?: string}
     */
    public function chat(Organization $org, array $params, ?string $providerKey = null, ?string $modelOverride = null): array
    {
        $providerKey = $providerKey ?: $org->ai_provider;
        $model = $modelOverride ?: $org->ai_model;

        if (empty($providerKey) || !$this->registry->has($providerKey)) {
            return ['success' => false, 'error' => 'No AI provider configured for this organization. Set one in Settings → Organization.'];
        }

        $apiKey = $org->ai_api_key; // decrypted cast
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No API key configured. Add one in Settings → Organization.'];
        }

        $provider = $this->registry->get($providerKey);
        if ($provider === null) {
            return ['success' => false, 'error' => 'Unknown AI provider: '.$providerKey];
        }

        $effectiveModel = $model ?: $provider->defaultModel();
        $result = $provider->chat($apiKey, $effectiveModel, $params);

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'unknown', 'provider' => $providerKey, 'model' => $effectiveModel];
        }

        return [
            'success' => true,
            'text' => $result['text'],
            'provider' => $providerKey,
            'model' => $effectiveModel,
        ];
    }

    /**
     * Convenience: ask the AI to return strict JSON. The model is told via the
     * system prompt and (when supported) response_format is enabled.
     *
     * @return array{success: bool, data?: array, error?: string, provider?: string, model?: string, raw?: string}
     */
    public function chatJson(Organization $org, string $systemPrompt, string $userPrompt, ?string $providerKey = null, ?string $modelOverride = null): array
    {
        $result = $this->chat($org, [
            'system' => $systemPrompt."\n\nRespond ONLY with valid JSON, no markdown.",
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
            'temperature' => 0.1,
            'json_mode' => true,
        ], $providerKey, $modelOverride);

        if (!$result['success']) {
            return $result;
        }

        $text = $result['text'];
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
            $text = $m[1];
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            return [
                'success' => false,
                'error' => 'AI returned non-JSON: '.mb_substr($text, 0, 200),
                'provider' => $result['provider'] ?? null,
                'model' => $result['model'] ?? null,
                'raw' => $text,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
            'raw' => $text,
        ];
    }
}
