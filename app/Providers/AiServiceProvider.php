<?php

namespace App\Providers;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\DeepseekProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\GlmProvider;
use App\Services\Ai\Providers\MiniMaxProvider;
use App\Services\Ai\Providers\MistralProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Scraping\CarScrapingService;
use App\Services\Scraping\GenericAiExtractor;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProviderRegistry::class);

        // Bind each provider as singleton by its key
        $providers = [
            'anthropic' => AnthropicProvider::class,
            'openai'    => OpenAiProvider::class,
            'mistral'   => MistralProvider::class,
            'gemini'    => GeminiProvider::class,
            'deepseek'  => DeepseekProvider::class,
            'minimax'   => MiniMaxProvider::class,
            'glm'       => GlmProvider::class,
        ];
        foreach ($providers as $key => $class) {
            $this->app->singleton("ai.provider.{$key}", $class);
        }

        // Rewrite the scraper: GenericAiExtractor only (single)
        $this->app->singleton(CarScrapingService::class, function ($app) {
            return new CarScrapingService($app->make(GenericAiExtractor::class));
        });
        $this->app->singleton(GenericAiExtractor::class);
    }

    public function boot(): void
    {
        //
    }
}
