<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->boot();

echo "MistralExtractor: " . (class_exists('App\Services\Scraping\MistralExtractor') ? 'EXISTS' : 'gone') . PHP_EOL;
echo "MiniMaxExtractor:  " . (class_exists('App\Services\Scraping\MiniMaxExtractor') ? 'EXISTS' : 'gone') . PHP_EOL;
echo "GlmExtractor:      " . (class_exists('App\Services\Scraping\GlmExtractor') ? 'EXISTS' : 'gone') . PHP_EOL;
echo "GenericAiExtractor:" . (class_exists('App\Services\Scraping\GenericAiExtractor') ? 'EXISTS' : 'gone') . PHP_EOL;
echo "AiService:         " . (class_exists('App\Services\Ai\AiService') ? 'EXISTS' : 'gone') . PHP_EOL;
echo "Registry:          " . (class_exists('App\Services\Ai\AiProviderRegistry') ? 'EXISTS' : 'gone') . PHP_EOL;

$registry = $app->make(\App\Services\Ai\AiProviderRegistry::class);
echo PHP_EOL . "Providers:" . PHP_EOL;
foreach ($registry->options() as $opt) {
    echo "  - {$opt['key']} ({$opt['label']}) default={$opt['default_model']}" . PHP_EOL;
}
