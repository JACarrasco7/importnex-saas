<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;

/**
 * OpenAI Chat Completions (GPT-4o, GPT-4.1, o3, ...).
 *
 * Endpoint: https://api.openai.com/v1/chat/completions
 */
class OpenAiProvider implements AiProviderInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'openai'; }
    public function label(): string { return 'OpenAI (GPT)'; }
    public function defaultModel(): string { return 'gpt-4o-mini'; }

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
            ];
            if (isset($params['temperature'])) {
                $payload['temperature'] = $params['temperature'];
            }

            $resp = $this->http(60)->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if ($resp->failed()) {
                $this->logFailure('openai', $resp->status(), $resp->body());
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
