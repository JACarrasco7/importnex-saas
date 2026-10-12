<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$car = App\Models\Car::find(71);
if (! $car) {
    echo "Car 71 not found" . PHP_EOL;
    exit;
}
echo json_encode([
    'id' => $car->id,
    'brand' => $car->brand,
    'verdict' => $car->verdict,
    'verdict_confidence' => $car->verdict_confidence,
    'verdict_reasoning' => $car->verdict_reasoning,
    'verdict_changes' => $car->verdict_changes,
    'traffic_light' => $car->traffic_light,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;