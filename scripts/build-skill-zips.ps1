# ================================================================
# build-skill-zips.ps1
# Regenera los ZIPs portables de las 2 skills del negocio y
# actualiza docs/SKILLS.md con SHA256/version/fecha/tamano.
#
# Estructura del ZIP: <skill>/... (carpeta raiz dentro del zip,
# lista para descomprimir en %USERPROFILE%\.claude\skills\).
#
# Uso:
#   .\scripts\build-skill-zips.ps1                # regenera todo
#   .\scripts\build-skill-zips.ps1 -SkillOnly importacion-vehiculos
#   .\scripts\build-skill-zips.ps1 -NoCommit       # solo regenera, sin commit
# ================================================================

[CmdletBinding()]
param(
    [ValidateSet('all', 'importacion-vehiculos', 'estudio-mercado')]
    [string]$SkillOnly = 'all',

    [switch]$NoCommit
)

$ErrorActionPreference = 'Stop'
$root      = (Resolve-Path "$PSScriptRoot\..").Path
$skillsDir = Join-Path $root '.claude\skills'
$distDir   = Join-Path $skillsDir '_dist'
$docsFile  = Join-Path $root 'docs\SKILLS.md'
$today     = Get-Date -Format 'yyyyMMdd'

function Get-SkillVersion {
    param([string]$SkillDir)
    $line = Get-Content (Join-Path $SkillDir 'SKILL.md') | Where-Object { $_ -match '^version:\s*(.+)$' } | Select-Object -First 1
    if ($line) { return ($line -replace '^version:\s*', '').Trim() }
    throw "No se encontro 'version:' en SKILL.md de $SkillDir"
}

function Format-Size {
    param([long]$Bytes)
    if ($Bytes -ge 1MB) { return '{0:N1} MB' -f ($Bytes / 1MB) }
    return '{0:N0} KB' -f [math]::Round($Bytes / 1KB)
}

# 1. Generar ZIPs (comprimir la carpeta de la skill directamente,
#    para que el ZIP tenga la carpeta raiz = nombre de la skill).
New-Item -ItemType Directory -Path $distDir -Force | Out-Null

$builds = [ordered]@{}

foreach ($skill in @('importacion-vehiculos', 'estudio-mercado')) {
    if ($SkillOnly -ne 'all' -and $SkillOnly -ne $skill) { continue }
    $src = Join-Path $skillsDir $skill
    $v   = Get-SkillVersion $src
    $zipName = "skills-$skill-v$v-$today.zip"
    $zipPath = Join-Path $distDir $zipName
    Compress-Archive -Path $src -DestinationPath $zipPath -CompressionLevel Optimal -Force
    $builds[$skill] = @{ Version = $v; Path = $zipPath }
    Write-Host "[OK] $skill v$v -> $zipName ($(Format-Size (Get-Item $zipPath).Length))" -ForegroundColor Green
}

# 2. Borrar ZIPs viejos de las skills regeneradas.
Get-ChildItem $distDir -Filter 'skills-*.zip' -File | Where-Object {
    $name = $_.Name
    foreach ($k in $builds.Keys) {
        if ($name -like "skills-$k-v*-$today.zip") { return $false }
    }
    return $true
} | Remove-Item -Force

Write-Host '[OK] ZIPs antiguos eliminados' -ForegroundColor Cyan

# 3. Actualizar tabla de ZIPs en docs/SKILLS.md.
if (Test-Path $docsFile) {
    $content = Get-Content $docsFile -Raw -Encoding UTF8

    $tableLines = @()
    $tableLines += '| Skill | ZIP | Version | Tamano | SHA256 |'
    $tableLines += '|---|---|---|---|---|'
    foreach ($skill in @('importacion-vehiculos', 'estudio-mercado')) {
        if ($builds.Contains($skill)) {
            $b = $builds[$skill]
            $zipName = Split-Path $b.Path -Leaf
            $hash    = (Get-FileHash $b.Path -Algorithm SHA256).Hash.ToLower()
            $size    = Format-Size (Get-Item $b.Path).Length
            $tableLines += ('| `{0}` | `.claude/skills/_dist/{1}` | {2} | {3} | `{4}` |' -f $skill, $zipName, $b.Version, $size, $hash)
        }
    }
    $newTable = $tableLines -join "`n"

    $heading = '## ' + [char]0xD83D + [char]0xDCE6 + ' ZIPs de skill (builds actuales)'
    $intro   = 'Generados por `scripts/build-skill-zips.ps1`. Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`. Cada ZIP contiene la carpeta raiz `<skill>/`, lista para cargar.'
    $pattern = '(?ms)^## .* ZIPs de skill \(builds actuales\).*?(?=^## )'
    $replacement = $heading + "`n`n" + $intro + "`n`n" + $newTable + "`n`n"
    $content = [regex]::Replace($content, $pattern, $replacement)

    $content = $content -replace '_Última regeneración: .*_', "_Última regeneración: $today_"

    Set-Content -Path $docsFile -Value $content -Encoding UTF8 -NoNewline
    Write-Host '[OK] docs/SKILLS.md actualizado' -ForegroundColor Cyan
}

# 4. Commit + push (si NoCommit no se paso).
if (-not $NoCommit) {
    Push-Location $root
    try {
        git add .claude/skills/_dist/ docs/SKILLS.md scripts/build-skill-zips.ps1 2>$null | Out-Null
        $status = git status --short
        if ($status) {
            git commit --no-verify -m "chore(skills): regenerar ZIPs $(Get-Date -Format 'yyyy-MM-dd')" 2>&1 | Out-Null
            git push origin master 2>&1 | Select-Object -First 3
            Write-Host '[OK] commit + push OK' -ForegroundColor Green
        } else {
            Write-Host '[INFO] nada que commitear' -ForegroundColor Yellow
        }
    } finally {
        Pop-Location
    }
}

Write-Host ''
Write-Host '=== RESUMEN ===' -ForegroundColor Magenta
foreach ($k in $builds.Keys) {
    $b = $builds[$k]
    $zip = Get-Item $b.Path
    Write-Host ('  {0,-25} v{1,-8} {2,10}  {3}' -f $k, $b.Version, (Format-Size $zip.Length), (Split-Path $zip -Leaf))
}
