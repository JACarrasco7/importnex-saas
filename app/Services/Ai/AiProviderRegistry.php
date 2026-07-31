<?php

namespace App\Services\Ai;

use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\DeepseekProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\GlmProvider;
use App\Services\Ai\Providers\MiniMaxProvider;
use App\Services\Ai\Providers\MistralProvider;
use App\Services\Ai\Providers\OpenAiProvider;

/**
 * Maps a provider key (stored in organizations.ai_provider) to the concrete
 * implementation. Add a new provider here and it becomes available everywhere.
 */
class AiProviderRegistry
{
    /** @var array<string, class-string<AiProviderInterface>> */
    private const MAP = [
        'anthropic' => AnthropicProvider::class,
        'openai'    => OpenAiProvider::class,
        'mistral'   => MistralProvider::class,
        'gemini'    => GeminiProvider::class,
        'deepseek'  => DeepseekProvider::class,
        'minimax'   => MiniMaxProvider::class,
        'glm'       => GlmProvider::class,
    ];

    public function all(): array
    {
        return array_map(
            fn($class) => app()->bound($class) ? app($class) : new $class(),
            array_values(self::MAP),
        );
    }

    /**
     * @return array<int, array{key: string, label: string, default_model: string}>
     */
    public function options(): array
    {
        return array_map(fn(AiProviderInterface $p) => [
            'key' => $p->key(),
            'label' => $p->label(),
            'default_model' => $p->defaultModel(),
        ], $this->all());
    }

    public function get(string $key): ?AiProviderInterface
    {
        $class = self::MAP[$key] ?? null;
        return $class ? app($class) : null;
    }

    public function has(string $key): bool
    {
        return isset(self::MAP[$key]);
    }
}
