<?php

namespace App\Services\Ai\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared helpers for AI providers: HTTP client setup, JSON-mode extraction,
 * uniform error reporting.
 */
trait MakesHttpCalls
{
    protected function http(int $timeout = 45): PendingRequest
    {
        return Http::timeout($timeout)->acceptJson();
    }

    /**
     * Attempt to extract the assistant text from a JSON body.
     * Returns null if shape is unexpected.
     */
    protected function extractText(array $body): ?string
    {
        $text = $body['content'][0]['text']
            ?? $body['choices'][0]['message']['content']
            ?? $body['candidates'][0]['content']['parts'][0]['text']
            ?? null;

        if (!is_string($text)) {
            return null;
        }

        return trim($text);
    }

    protected function logFailure(string $provider, int $status, string $body): void
    {
        Log::warning("{$provider} HTTP error", [
            'status' => $status,
            'body_preview' => mb_substr($body, 0, 300),
        ]);
    }
}
