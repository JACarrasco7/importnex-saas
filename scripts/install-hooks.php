<?php

// filepath: scripts/install-hooks.php
// Instala githooks desde .githooks/*.sh a .git/hooks/

$source = __DIR__ . '/../.githooks';
$target = __DIR__ . '/../.git/hooks';

if (! is_dir($source)) {
    fwrite(STDERR, "❌ .githooks/ no encontrado\n");
    exit(1);
}

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

$installed = 0;
foreach (glob($source . '/*') as $file) {
    $name = basename($file);
    $dest = $target . '/' . $name;

    if (copy($file, $dest)) {
        chmod($dest, 0755);
        echo "✅ Instalado: $name\n";
        $installed++;
    } else {
        fwrite(STDERR, "❌ Falló instalación: $name\n");
    }
}

echo "\n🎉 $installed hooks instalados\n";
echo "Para verificar: ls -la .git/hooks/pre-commit\n";
