<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;

/**
 * Z.AI GLM (glm-4.5-air, glm-4-plus, glm-4-flash, ...) — OpenAI-compatible.
 *
 * Endpoint: https://api.z.ai/api/paas/v4/chat/completions
 */
class GlmProvider implements AiProviderInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'glm'; }
    public function label(): string { return 'Z.AI (GLM)'; }
    public function defaultModel(): string { return 'glm-4.5-air'; }

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

            $resp = $this->http(60)->withToken($apiKey)
                ->post('https://api.z.ai/api/paas/v4/chat/completions', $payload);

            if ($resp->failed()) {
                $this->logFailure('glm', $resp->status(), $resp->body());
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
