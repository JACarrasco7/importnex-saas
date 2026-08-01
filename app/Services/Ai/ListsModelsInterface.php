<?php

namespace App\Services\Ai;

/**
 * Optional capability: providers that can list the models available
 * for a given API key (OpenAI /models, Anthropic /v1/models, etc.).
 */
interface ListsModelsInterface
{
    /**
     * List models usable with this API key.
     *
     * @return array{success: bool, models?: array<int, string>, error?: string}
     */
    public function listModels(string $apiKey): array;
}
