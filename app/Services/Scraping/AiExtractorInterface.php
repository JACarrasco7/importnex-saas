<?php

namespace App\Services\Scraping;

interface AiExtractorInterface
{
    /**
     * Extract car data from raw HTML using an AI provider.
     *
     * @param  string  $html  The HTML payload to analyze
     * @param  string  $url  The source URL (for context)
     * @return array{success: bool, data?: array, error?: string, provider: string}
     */
    public function extract(string $html, string $url): array;

    /**
     * Provider identifier for logging and metrics.
     */
    public function name(): string;
}
