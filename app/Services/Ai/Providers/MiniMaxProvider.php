<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;

/**
 * MiniMax (MiniMax-M3, etc.) — exposes both an Anthropic-compatible
 * `/v1/messages` endpoint AND an OpenAI-compatible `/v1/chat/completions`.
 * We use Anthropic format to match the official SDK-style wiring.
 *
 * Endpoint: https://api.minimax.io/anthropic/v1/messages
 */
class MiniMaxProvider implements AiProviderInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'minimax'; }
    public function label(): string { return 'MiniMax'; }
    public function defaultModel(): string { return 'MiniMax-M3'; }

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
            ])->post('https://api.minimax.io/anthropic/v1/messages', $payload);

            if ($resp->failed()) {
                $this->logFailure('minimax', $resp->status(), $resp->body());
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
