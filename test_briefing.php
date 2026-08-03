<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\View;
use SimpleSoftwareIO\QrCode\Generator;

// Sample car object (no DB needed for design test)
$car = new \App\Models\Car();
$car->brand = 'BMW';
$car->model = 'Serie 3';
$car->version = '320d xDrive';
$car->year = '2021';
$car->mileage = 85000;
$car->fuel = 'Diésel';
$car->transmission = 'Automático';
$car->cv = 190;
$car->color = 'Negro';
$car->seats = 5;
$car->city = 'Berlín';
$car->purchase_price = 24500;
$car->market_avg = 27900;
$car->estimated_saving = 3400;
$car->verdict = 'Buy';
$car->id = 1;

$logoPath = __DIR__ . '/public/images/jj-import/logo-horizontal-blanco.png';
$logoBase64 = file_exists($logoPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
    : null;

$qrUrl = 'https://jjimportmotors.on-forge.com/request/jj-import-motors';
$qrSvg = (new Generator())->format('svg')
    ->size(280)
    ->margin(1)
    ->errorCorrection('H')
    ->backgroundColor(255, 255, 255)
    ->color(6, 16, 31)
    ->generate($qrUrl);

// Sample marketing contents
$contents = collect([
    (object) [
        'channel' => 'milanuncios',
        'title' => 'BMW Serie 3 320d xDrive — Importado y matriculado',
        'description' => "BMW Serie 3 320d xDrive 190CV en perfecto estado.\n\n- 85.000 km, historial completo\n- Cambio automático\n- Importado y matriculado por JJ Import Motors\n- Entrega llave en mano\n\nPrecio: 24.500€",
        'hashtags' => ['bmw', 'serie3', 'cocheimportado', 'premium'],
        'photo_tips' => ['Fotos en exterior con luz natural', 'Incluye interior, maletero y llantas'],
    ],
    (object) [
        'channel' => 'tiktok',
        'title' => 'Mira lo que puedes ahorrar 🇩🇪→🇪🇸',
        'description' => 'Así funciona importar tu coche con JJ Import Motors. Todo llave en mano.',
        'hashtags' => ['coches', 'importacion', 'ahorro', 'tiktokcoche'],
        'photo_tips' => ['Vídeo de 15s mostrando el coche', 'Añade texto con el precio final'],
    ],
]);

try {
    $html = View::make('jj-import.briefing', [
        'car' => $car,
        'contents' => $contents,
        'logo_base64' => $logoBase64,
        'car_photo_base64' => null,
        'qr_svg' => $qrSvg,
        'qr_url' => $qrUrl,
        'precio_honorarios' => '1.500 €',
        'telefono_1' => '675 70 14 39',
        'telefono_2' => '691 48 59 27',
        'email' => 'jjimportmotors@gmail.com',
    ])->render();

    file_put_contents(__DIR__ . '/storage/app/public/briefing-test.html', $html);

    \Spatie\Browsershot\Browsershot::html($html)
        ->format('A4')
        ->landscape(false)
        ->margins(0, 0, 0, 0)
        ->showBackground()
        ->waitUntilNetworkIdle()
        ->deviceScaleFactor(2)
        ->scale(1)
        ->savePdf(__DIR__ . '/storage/app/public/briefing-test.pdf');

    echo 'BRIEFING OK: ' . filesize(__DIR__ . '/storage/app/public/briefing-test.pdf') . " bytes\n";
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/storage/app/public/briefing-test.html', $html);
    echo "HTML guardado como fallback\n";
}
