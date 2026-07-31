<?php

namespace App\Services\Ai;

/**
 * Contract every AI provider must implement.
 *
 * Each provider knows its own endpoint, default model and how to serialize
 * the request/response for its native wire format (Anthropic-style messages,
 * OpenAI-style chat completions, Gemini native, etc.).
 */
interface AiProviderInterface
{
    /** Unique provider key stored in organizations.ai_provider. */
    public function key(): string;

    /** Human-readable label for UI. */
    public function label(): string;

    /** Default model when the organization leaves the field blank. */
    public function defaultModel(): string;

    /**
     * Run a chat completion.
     *
     * @param  array{system?: string, messages: array<int, array{role: string, content: string}>, max_tokens?: int, temperature?: float}  $params
     * @return array{success: bool, text?: string, error?: string, provider: string, model: string}
     */
    public function chat(string $apiKey, string $model, array $params): array;
}
