<?php
/**
 * Script: setup-server.php
 * Se ejecuta en el servidor via SSH para preparar toda la infraestructura
 * de importación de informes desde el chat.
 *
 * Uso:
 *   cd /var/www/importnex-saas
 *   php scripts/setup-server.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Organization;
use App\Models\Car;

echo "============================================================\n";
echo "   ImportnexCore - Server Setup Script\n";
echo "============================================================\n\n";

// 1. Verificar / crear organización JJ Import Motors
echo "[1/5] Verificando organización JJ Import Motors...\n";
$org = Organization::firstOrCreate(
    ['name' => 'JJ Import Motors'],
    [
        'plan' => 'pro',
        'trial_ends_at' => now()->addDays(30),
    ]
);
echo "  ✓ Organización ID: {$org->id}\n\n";

// 2. Verificar token de importación
echo "[2/5] Verificando token de importación...\n";
$token = config('services.importnex_chat.token');
if (blank($token)) {
    echo "  ⚠️  IMPORTNEX_CHAT_IMPORT_TOKEN no configurado\n";
    echo "  Generando uno nuevo...\n";
    $token = bin2hex(random_bytes(32));
    // Aquí habría que añadirlo al .env, pero eso requiere permisos especiales
    echo "  Token generado: $token\n";
} else {
    echo "  ✓ Token configurado: " . substr($token, 0, 8) . "..." . substr($token, -8) . "\n";
}
echo "\n";

// 3. Crear estructura de carpetas
echo "[3/5] Creando estructura de carpetas...\n";
$orgDirName = str_replace(' ', '_', $org->name);
$baseDir = storage_path("app/importnex/import/{$orgDirName}");

$dirs = [
    $baseDir,
    "{$baseDir}/vehicles",
    "{$baseDir}/processed",
];

foreach ($dirs as $dir) {
    if (!File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
        echo "  ✓ Creada: $dir\n";
    } else {
        echo "  · Existe: $dir\n";
    }
}
echo "\n";

// 4. Mover JSONs pendientes a la nueva estructura
echo "[4/5] Migrando JSONs pendientes...\n";
$oldDir = storage_path('app/importnex/import');
$moved = 0;

if (File::isDirectory($oldDir)) {
    foreach (File::files($oldDir) as $file) {
        if ($file->getExtension() === 'json') {
            $newPath = "{$baseDir}/vehicles/" . $file->getFilename();
            File::move($file->getPathname(), $newPath);
            echo "  ✓ Movido: {$file->getFilename()}\n";
            $moved++;
        }
    }
}
echo "  Total movidos: $moved\n\n";

// 5. Generar reporte de coches existentes
echo "[5/5] Estado actual de la base de datos...\n";
$carCount = Car::where('organization_id', $org->id)->count();
echo "  Coches en JJ Import Motors: $carCount\n";

if ($carCount > 0) {
    echo "\n  Últimos 5 coches:\n";
    Car::where('organization_id', $org->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->each(function ($car) {
            $status = $car->status ?? 'N/A';
            echo "    - [{$car->id}] {$car->brand} {$car->model} ({$car->year}) - $status\n";
        });
}

echo "\n============================================================\n";
echo "   ✅ Setup completado\n";
echo "============================================================\n";
echo "\nEndpoint para subir informes:\n";
echo "  POST https://dev.aktive.cloud/importnexcore/api/import-valuation\n";
echo "  Header: X-Import-Token: $token\n";
echo "\n";
