<?php

namespace App\Services\Scraping;

use App\Models\Organization;

interface AiExtractorInterface
{
    /**
     * Extract car data from raw HTML using an AI provider.
     *
     * @param  string  $html  The HTML payload to analyze
     * @param  string  $url  The source URL (for context)
     * @param  Organization|null  $org  Optional explicit org (else uses auth()->user()->organization)
     * @return array{success: bool, data?: array, error?: string, provider: string}
     */
    public function extract(string $html, string $url, ?Organization $org = null): array;

    /**
     * Provider identifier for logging and metrics.
     */
    public function name(): string;
}
