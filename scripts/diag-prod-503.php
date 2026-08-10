<?php
/**
 * Diagnostico de 503 / errors en produccion.
 *
 * USO: php scripts/diag-prod-503.php
 *
 * Lee storage/logs/laravel.log (sin tocar BD) y muestra:
 *   - Ultimos 30 errores con stack trace resumido
 *   - Configuracion PHP relevante (memory_limit, max_execution)
 *   - Tamano del log
 *   - Uso de disco en storage/
 */

declare(strict_types=1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "\n=== DIAGNOSTICO 503 (solo lectura) ===\n\n";

// 1) Configuracion PHP
echo "--- PHP Runtime ---\n";
echo "PHP version:     " . PHP_VERSION . "\n";
echo "memory_limit:    " . ini_get('memory_limit') . "\n";
echo "max_execution:   " . ini_get('max_execution_time') . "s\n";
echo "opcache enabled: " . (function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'] ? 'yes' : 'no') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size:       " . ini_get('post_max_size') . "\n";

// 2) Espacio en disco
echo "\n--- Storage disk usage ---\n";
$storage = __DIR__ . '/../storage';
if (is_dir($storage)) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storage, FilesystemIterator::SKIP_DOTS)) as $f) {
        $size += $f->getSize();
    }
    $gb = $size / 1024 / 1024 / 1024;
    echo "storage/: " . number_format($size) . " bytes (" . round($gb, 2) . " GB)\n";

    $diskFree = disk_free_space($storage);
    echo "disco libre: " . round($diskFree / 1024 / 1024 / 1024, 2) . " GB\n";
}

// 3) Ultimos errores del log
echo "\n--- Laravel log (ultimas 30 lineas ERROR) ---\n";
if (! file_exists($logFile)) {
    echo "(no existe storage/logs/laravel.log)\n";
    exit(0);
}

$content = file_get_contents($logFile);
$logSize = filesize($logFile);
echo "Tamano log: " . round($logSize / 1024 / 1024, 2) . " MB\n";

// Extraer bloques ERROR (PHP multi-line stack traces)
preg_match_all(
    '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] [a-z]+\.ERROR: (.+?)(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/s',
    $content,
    $matches
);

$errors = $matches[0] ?? [];
$total = count($errors);
echo "Total errores en log: $total\n\n";

$last = array_slice($errors, -30);
foreach ($last as $i => $err) {
    // Primeras 3 lineas por error (mensaje + SQL/excepcion resumido)
    $lines = explode("\n", $err);
    $head = implode("\n  ", array_slice(array_map('rtrim', $lines), 0, 4));
    echo "[" . ($total - 30 + $i + 1) . "/$total]\n  $head\n\n";
}

// 4) Memoria de PHP en runtime real (lo que reporta el proceso actual)
echo "--- Proceso actual ---\n";
echo "memory_get_peak_usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";

echo "\n=== FIN DIAGNOSTICO ===\n";
