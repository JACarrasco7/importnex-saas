<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\Concerns\MakesHttpCalls;
use App\Services\Ai\ListsModelsInterface;

/**
 * Google Gemini (gemini-1.5-pro, gemini-1.5-flash, gemini-2.0-flash, ...).
 *
 * Endpoint: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
 * Auth: API key in query string (?key=...) for simplicity.
 */
class GeminiProvider implements AiProviderInterface, ListsModelsInterface
{
    use MakesHttpCalls;

    public function key(): string { return 'gemini'; }
    public function label(): string { return 'Google Gemini'; }
    public function defaultModel(): string { return 'gemini-1.5-flash'; }

    public function listModels(string $apiKey): array
    {
        try {
            $resp = $this->http(20)
                ->get('https://generativelanguage.googleapis.com/v1beta/models?key='.$apiKey);

            if ($resp->failed()) {
                return ['success' => false, 'error' => 'HTTP '.$resp->status()];
            }

            $ids = collect($resp->json('models') ?? [])
                ->filter(fn ($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? []))
                ->map(fn ($m) => preg_replace('/^models\//', '', $m['name'] ?? ''))
                ->filter()
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
            $contents = [];
            if (!empty($params['system'])) {
                $contents[] = ['role' => 'user', 'parts' => [['text' => $params['system']]]];
                $contents[] = ['role' => 'model', 'parts' => [['text' => 'Understood.']]];
            }
            foreach ($params['messages'] as $m) {
                $role = $m['role'] === 'assistant' ? 'model' : 'user';
                $contents[] = ['role' => $role, 'parts' => [['text' => $m['content']]]];
            }

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'maxOutputTokens' => $params['max_tokens'] ?? 1500,
                    'temperature' => $params['temperature'] ?? 0.1,
                ],
            ];

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
            $resp = $this->http(60)->post($url.'?key='.$apiKey, $payload);

            if ($resp->failed()) {
                $this->logFailure('gemini', $resp->status(), $resp->body());
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
