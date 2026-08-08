<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/storage',
        __DIR__.'/bootstrap',
    ])
    ->withRootFiles([
        __DIR__.'/composer.json',
        __DIR__.'/rector.php',
    ])
    // Código quality pero sin LevelSets agresivos
    ->withSets([
        // Solo código quality, no cambios de versión PHP agresivos
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        // Early return y simplificaciones seguras
        SetList::EARLY_RETURN,
        SetList::CODING_STYLE,
    ]);
