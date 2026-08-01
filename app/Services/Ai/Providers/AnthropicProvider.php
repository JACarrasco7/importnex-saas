<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;
use App\Services\Ai\ListsModelsInterface;

/**
 * Anthropic Messages API (Claude Sonnet / Haiku / Opus).
 *
 * Endpoint: https://api.anthropic.com/v1/messages
 */
class AnthropicProvider implements AiProviderInterface, ListsModelsInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'anthropic'; }
    public function label(): string { return 'Anthropic (Claude)'; }
    public function defaultModel(): string { return 'claude-3-5-sonnet-latest'; }

    public function listModels(string $apiKey): array
    {
        try {
            $resp = $this->http(20)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->get('https://api.anthropic.com/v1/models');

            if ($resp->failed()) {
                return ['success' => false, 'error' => 'HTTP '.$resp->status()];
            }

            $ids = collect($resp->json('data') ?? [])
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            return ['success' => true, 'models' => $ids];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function chat(string $apiKey, string $model, array $params): array
    {
        try {
            $payload = [
                'model' => $model,
                'max_tokens' => $params['max_tokens'] ?? 1500,
                'messages' => $params['messages'],
            ];
            if (!empty($params['system'])) {
                $payload['system'] = $params['system'];
            }
            if (isset($params['temperature'])) {
                $payload['temperature'] = $params['temperature'];
            }

            $resp = $this->http(60)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', $payload);

            if ($resp->failed()) {
                $this->logFailure('anthropic', $resp->status(), $resp->body());
                return ['success' => false, 'error' => 'HTTP '.$resp->status(), 'provider' => $this->key(), 'model' => $model];
            }

            $text = $this->extractText($resp->json());
            if ($text === null) {
                return ['success' => false, 'error' => 'Empty response', 'provider' => $this->key(), 'model' => $model];
            }

            return ['success' => true, 'text' => $text, 'provider' => $this->key(), 'model' => $model];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'provider' => $this->key(), 'model' => $model];
        }
    }
}
