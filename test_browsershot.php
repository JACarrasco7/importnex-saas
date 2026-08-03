<?php

require __DIR__ . '/vendor/autoload.php';

use Spatie\Browsershot\Browsershot;
use SimpleSoftwareIO\QrCode\Generator;

$qrUrl = 'https://jjimportmotors.on-forge.com/request/jj-import-motors';

$qrSvg = (new Generator())->format('svg')
    ->size(280)
    ->margin(1)
    ->errorCorrection('H')
    ->backgroundColor(255, 255, 255)
    ->color(6, 16, 31)
    ->generate($qrUrl);

$logoPath = __DIR__ . '/public/images/jj-import/logo-horizontal-blanco.png';
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

$vars = [
    'precio_honorarios' => '1.500 €',
    'telefono_1' => '675 70 14 39',
    'telefono_2' => '691 48 59 27',
    'email' => 'jjimportmotors@gmail.com',
    'qr_url' => $qrUrl,
    'logo_base64' => $logoBase64,
    'qr_svg' => $qrSvg,
];

$blade = file_get_contents(__DIR__ . '/resources/views/jj-import/folleto.blade.php');

$blade = preg_replace('/@php.*?@endphp/s', '', $blade, 1);

foreach ($vars as $k => $v) {
    $blade = str_replace('{{ $' . $k . ' }}', $v, $blade);
    $blade = str_replace('{!! $' . $k . ' !!}', $v, $blade);
}

try {
    Browsershot::html($blade)
        ->format('A4')
        ->margins(0, 0, 0, 0)
        ->showBackground()
        ->waitUntilNetworkIdle()
        ->deviceScaleFactor(2)
        ->scale(1)
        ->savePdf(__DIR__ . '/storage/app/public/jj-import-folleto.pdf');
    echo 'PDF OK: ' . filesize(__DIR__ . '/storage/app/public/jj-import-folleto.pdf') . " bytes\n";
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
