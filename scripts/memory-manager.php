<?php

// filepath: scripts/memory-manager.php
// Memory Manager para ImportnexCore — auto-aprendizaje + auto-contexto + auto-docs
//
// Uso:
//   php scripts/memory-manager.php full-analysis
//   php scripts/memory-manager.php patterns --since=30days
//   php scripts/memory-manager.php acceptance-rate
//   php scripts/memory-manager.php regenerate-context
//   php scripts/memory-manager.php load-context
//   php scripts/memory-manager.php active-skills <path>
//   php scripts/memory-manager.php recent-traps --days=30
//   php scripts/memory-manager.php auto-changelog --since=YYYY-MM-DD
//   php scripts/memory-manager.php compress-skill <name>

$command = $argv[1] ?? 'help';
$arg2 = $argv[2] ?? null;
$arg3 = $argv[3] ?? null;

$basePath = dirname(__DIR__);
$aiPath = $basePath.'/.ai';
$memoryPath = $aiPath.'/memory';
$skillsPath = $aiPath.'/skills';
$rulesPath = $aiPath.'/rules';

if (! is_dir($memoryPath)) {
    mkdir($memoryPath, 0755, true);
}

switch ($command) {
    case 'load-context':
        loadContext($basePath);
        break;
    case 'active-skills':
        if (! $arg2) {
            echo "❌ Especifica path: php memory-manager.php active-skills app/Http/Controllers/CarController.php\n";
            exit(1);
        }
        activeSkills($arg2, $skillsPath, $rulesPath);
        break;
    case 'recent-traps':
        $days = 30;
        if ($arg2 && str_starts_with($arg2, '--days=')) {
            $days = (int) substr($arg2, 7);
        }
        recentTraps($days, $aiPath);
        break;
    case 'patterns':
        $since = '30days';
        if ($arg2 && str_starts_with($arg2, '--since=')) {
            $since = substr($arg2, 8);
        }
        analyzePatterns($since, $basePath, $memoryPath);
        break;
    case 'acceptance-rate':
        acceptanceRate($basePath, $memoryPath);
        break;
    case 'regenerate-context':
        regenerateContext($basePath);
        break;
    case 'full-analysis':
        fullAnalysis($basePath, $aiPath, $memoryPath);
        break;
    case 'auto-changelog':
        $since = date('Y-m-d', strtotime('-1 week'));
        if ($arg2 && str_starts_with($arg2, '--since=')) {
            $since = substr($arg2, 8);
        }
        autoChangelog($since, $basePath);
        break;
    case 'compress-skill':
        if (! $arg2) {
            echo "❌ Especifica skill: php memory-manager.php compress-skill importnex-billing\n";
            exit(1);
        }
        compressSkill($arg2, $skillsPath);
        break;
    case 'help':
    default:
        echo <<<'HELP'

🧠 Memory Manager — ImportnexCore

Uso:
  full-analysis                    Análisis completo (slow)
  load-context                     Carga contexto rápido
  active-skills <path>             Skills activas para un path
  recent-traps [--days=N]          Trampas recientes
  patterns [--since=Nd|YYYY-MM-DD] Patrones recurrentes
  acceptance-rate                  Métricas de aceptación
  regenerate-context               Regenera cache de contexto
  auto-changelog [--since=date]    Genera CHANGELOG auto
  compress-skill <name>            Comprime una skill a versión .min.md

HELP;
        break;
}

function loadContext(string $basePath): void
{
    echo "📦 Cargando contexto mínimo viable...\n\n";

    $quickref = $basePath.'/.ai/skills/importnex-quickref/SKILL.md';
    if (file_exists($quickref)) {
        echo "✅ Quickref cargado\n";
    }

    $findingsFile = $basePath.'/.ai/memory/findings.json';
    if (file_exists($findingsFile)) {
        $findings = json_decode(file_get_contents($findingsFile), true);
        $recent = array_filter($findings, fn ($f) => strtotime($f['date']) > strtotime('-30 days'));
        echo '✅ '.count($recent).' findings recientes cargados (de '.count($findings).' totales)'."\n";
    }

    $rulesCount = count(glob($basePath.'/.ai/rules/*.md'));
    echo "✅ {$rulesCount} reglas disponibles (cargar bajo demanda por glob)\n";

    $skillsCount = count(glob($basePath.'/.ai/skills/*/SKILL.md'));
    echo "✅ {$skillsCount} skills disponibles\n";
}

function activeSkills(string $path, string $skillsPath, string $rulesPath): void
{
    echo "🔍 Skills/reglas activas para: $path\n\n";

    foreach (glob($rulesPath.'/*.md') as $ruleFile) {
        $content = file_get_contents($ruleFile);
        $name = basename($ruleFile, '.md');

        if (preg_match_all('/`([^`]+\*\*?\/[^`]*)`/', $content, $matches)) {
            foreach ($matches[1] as $glob) {
                if (matchGlob($glob, $path)) {
                    echo "  📋 Regla: $name (glob: $glob)\n";
                    break;
                }
            }
        }
    }
}

function matchGlob(string $glob, string $path): bool
{
    $pattern = str_replace(['**', '*'], ['.*', '[^/]*'], $glob);
    $pattern = '#^'.$pattern.'$#';

    return (bool) preg_match($pattern, $path);
}

function recentTraps(int $days, string $aiPath): void
{
    $findingsFile = $aiPath.'/memory/findings.json';
    if (! file_exists($findingsFile)) {
        echo "❌ No findings.json\n";

        return;
    }

    $findings = json_decode(file_get_contents($findingsFile), true);
    $cutoff = strtotime("-{$days} days");
    $recent = array_filter($findings, fn ($f) => strtotime($f['date']) > $cutoff);

    echo "🪤 Trampas recientes ({$days} días): ".count($recent)."\n\n";

    foreach ($recent as $f) {
        $severity = str_pad($f['severity'], 8);
        echo "  [$severity] {$f['issue']}\n";
        echo "    → {$f['prevention_rule']}\n\n";
    }
}

function analyzePatterns(string $since, string $basePath, string $memoryPath): void
{
    echo "🔍 Analizando patrones recurrentes desde: $since\n\n";

    if (preg_match('/^(\d+)(days?|weeks?|months?)$/', $since, $m)) {
        $gitSince = "\"{$m[1]} {$m[2]} ago\"";
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
        $gitSince = "\"{$since}\"";
    } else {
        $gitSince = "\"{$since}\"";
    }

    $output = shell_exec("git log --oneline --since={$gitSince} 2>&1") ?? '';
    $commits = $output ? array_filter(explode("\n", $output)) : [];

    $types = [];
    foreach ($commits as $commit) {
        if (preg_match('/\s([a-z]+)(\([a-z-]+\))?:/', $commit, $m)) {
            $type = $m[1];
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
    }

    arsort($types);
    echo "📊 Tipos de commits:\n";
    foreach ($types as $type => $count) {
        echo "  - $type: $count\n";
    }

    echo "\n🎯 Patrones recurrentes:\n";

    $fixCount = $types['fix'] ?? 0;
    if ($fixCount > 5) {
        echo "  🔴 $fixCount fixes — considerar crear reglas preventivas\n";
    }

    if (($types['feat'] ?? 0) > 10 && ($types['test'] ?? 0) < ($types['feat'] ?? 0) * 0.5) {
        echo "  🟠 Ratio features/tests desbalanceado — añadir tests\n";
    }
}

function acceptanceRate(string $basePath, string $memoryPath): void
{
    echo "📊 Acceptance Rate (última semana)\n\n";

    $since = date('Y-m-d', strtotime('-7 days'));
    $output = shell_exec("git log --oneline --since=\"{$since}\" 2>&1") ?? '';
    $commits = $output ? array_filter(explode("\n", $output)) : [];
    $totalOutputs = count($commits);

    echo "  Outputs generados: $totalOutputs\n";
    echo "  Editados por humano: estimar con `git diff` (heurística)\n";
    echo "  Acceptance rate: requiere tracking manual o git diff stats\n";

    $file = $memoryPath.'/acceptance-rate.json';
    $data = [
        'date' => date('Y-m-d'),
        'commits' => $totalOutputs,
        'note' => 'Estimación. Para tracking real, comparar commits vs ediciones post-commit.',
    ];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

    echo "\n💾 Guardado en $file\n";
}

function regenerateContext(string $basePath): void
{
    echo "🔄 Regenerando cache de contexto...\n\n";

    $cachePath = $basePath.'/.ai/cache';
    if (! is_dir($cachePath)) {
        mkdir($cachePath, 0755, true);
    }

    $quickref = $basePath.'/.ai/skills/importnex-quickref/SKILL.md';
    $findings = $basePath.'/.ai/memory/findings.json';

    $context = "# Project Context Snapshot\n";
    $context .= 'Generated: '.date('Y-m-d H:i:s')."\n\n";

    if (file_exists($quickref)) {
        $context .= "## Quickref\n\n";
        $context .= substr(file_get_contents($quickref), 0, 3000)."\n\n";
    }

    if (file_exists($findings)) {
        $findingsData = json_decode(file_get_contents($findings), true);
        $context .= "## Recent Findings\n\n";
        foreach (array_slice($findingsData, 0, 5) as $f) {
            $context .= "- **{$f['date']}** [{$f['severity']}] {$f['issue']}\n";
        }
        $context .= "\n";
    }

    file_put_contents($cachePath.'/project-context.md', $context);

    echo "✅ Cache regenerado: .ai/cache/project-context.md\n";
    echo '   Tamaño: '.strlen($context)." bytes\n";
}

function fullAnalysis(string $basePath, string $aiPath, string $memoryPath): void
{
    echo "🔬 Análisis completo (esto puede tardar 30s)...\n\n";

    loadContext($basePath);
    echo "\n";
    analyzePatterns('30days', $basePath, $memoryPath);
    echo "\n";
    recentTraps(30, $aiPath);
    echo "\n";
    acceptanceRate($basePath, $memoryPath);
}

function autoChangelog(string $since, string $basePath): void
{
    echo "📝 Generando CHANGELOG desde: $since\n\n";

    $output = shell_exec("git log --oneline --since=\"{$since}\" 2>&1") ?? '';
    $commits = $output ? array_filter(explode("\n", $output)) : [];

    $changelog = "## [Auto-generated] desde $since\n\n";
    $grouped = ['feat' => [], 'fix' => [], 'chore' => [], 'docs' => [], 'style' => [], 'refactor' => [], 'perf' => [], 'test' => [], 'security' => [], 'other' => []];

    foreach ($commits as $commit) {
        if (preg_match('/^([a-z]+)(\([a-z-]+\))?: (.+?) \(#([a-f0-9]+)\)/', $commit, $m)) {
            $type = $m[1];
            $scope = trim($m[2], '()');
            $msg = $m[3];
            $hash = $m[4];
            $entry = "- $msg (#$hash)";

            if (isset($grouped[$type])) {
                $grouped[$type][] = $entry;
            } else {
                $grouped['other'][] = $entry;
            }
        }
    }

    $typeNames = [
        'feat' => '### Added',
        'fix' => '### Fixed',
        'security' => '### Security',
        'perf' => '### Performance',
        'refactor' => '### Refactored',
        'docs' => '### Documentation',
        'style' => '### Style',
        'test' => '### Tests',
        'chore' => '### Chore',
        'other' => '### Other',
    ];

    foreach ($typeNames as $type => $header) {
        if (! empty($grouped[$type])) {
            $changelog .= "$header\n";
            foreach ($grouped[$type] as $entry) {
                $changelog .= "$entry\n";
            }
            $changelog .= "\n";
        }
    }

    $changelogFile = $basePath.'/docs/CHANGELOG.draft.md';
    file_put_contents($changelogFile, $changelog);

    echo "✅ Changelog generado: $changelogFile\n";
    echo '   Commits procesados: '.count($commits)."\n";
    echo "\n⚠️  Revisa antes de mergear a CHANGELOG.md oficial\n";
}

function compressSkill(string $name, string $skillsPath): void
{
    $skillFile = $skillsPath."/{$name}/SKILL.md";
    $minFile = $skillsPath."/{$name}/SKILL.min.md";

    if (! file_exists($skillFile)) {
        echo "❌ Skill no encontrada: $name\n";

        return;
    }

    $content = file_get_contents($skillFile);
    $lines = explode("\n", $content);

    $compressed = "# {$name} (versión comprimida)\n\n";
    $inCode = false;
    foreach ($lines as $line) {
        if (str_starts_with($line, '```')) {
            $inCode = ! $inCode;
            if (! $inCode) {
                $compressed .= "...\n";
            }

            continue;
        }
        if ($inCode) {
            continue;
        }
        if (preg_match('/^#+\s/', $line)) {
            $compressed .= "\n## ".trim(preg_replace('/^#+\s*/', '', $line))."\n";
        } elseif (str_starts_with($line, '- ')) {
            $compressed .= $line."\n";
        }
    }

    file_put_contents($minFile, $compressed);

    echo "✅ Comprimido: $minFile\n";
    echo '   Original: '.strlen($content)." bytes\n";
    echo '   Comprimido: '.strlen($compressed)." bytes\n";
    echo '   Ahorro: '.round((1 - strlen($compressed) / strlen($content)) * 100, 1)."%\n";
}
