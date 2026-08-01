<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;
use App\Services\Ai\ListsModelsInterface;

/**
 * Mistral Chat Completions (mistral-small, mistral-large, codestral, ...).
 *
 * Endpoint: https://api.mistral.ai/v1/chat/completions
 * Supports `response_format: json_object` when caller needs strict JSON.
 */
class MistralProvider implements AiProviderInterface, ListsModelsInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'mistral'; }
    public function label(): string { return 'Mistral AI'; }
    public function defaultModel(): string { return 'mistral-small-latest'; }

    public function listModels(string $apiKey): array
    {
        try {
            $resp = $this->http(20)->withToken($apiKey)
                ->get('https://api.mistral.ai/v1/models');

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
            $messages = [];
            if (!empty($params['system'])) {
                $messages[] = ['role' => 'system', 'content' => $params['system']];
            }
            foreach ($params['messages'] as $m) {
                $messages[] = ['role' => $m['role'], 'content' => $m['content']];
            }

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $params['max_tokens'] ?? 1500,
                'temperature' => $params['temperature'] ?? 0.1,
            ];
            if (!empty($params['json_mode'])) {
                $payload['response_format'] = ['type' => 'json_object'];
            }

            $resp = $this->http(60)->withToken($apiKey)
                ->post('https://api.mistral.ai/v1/chat/completions', $payload);

            if ($resp->failed()) {
                $this->logFailure('mistral', $resp->status(), $resp->body());
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
