<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "CarDocument cargado: " . (class_exists(\App\Models\CarDocument::class) ? "OK" : "NO") . PHP_EOL;
echo "GROUP_AI_REPORTS: " . \App\Models\CarDocument::GROUP_AI_REPORTS . PHP_EOL;
