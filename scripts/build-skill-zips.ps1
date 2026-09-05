# ================================================================
# build-skill-zips.ps1
# Regenera los ZIPs portables de las 2 skills del negocio y
# actualiza docs/SKILLS.md con los nuevos SHA256/fechas/tamaños.
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
$root       = (Resolve-Path "$PSScriptRoot\..").Path
$skillsDir  = Join-Path $root '.claude\skills'
$distDir    = Join-Path $skillsDir '_dist'
$docsFile   = Join-Path $root 'docs\SKILLS.md'
$today      = Get-Date -Format 'yyyyMMdd'

function Get-SkillVersion {
    param([string]$SkillDir)
    $md = Join-Path $SkillDir 'SKILL.md'
    if (-not (Test-Path $md)) { throw "No SKILL.md en $SkillDir" }
    $line = Get-Content $md | Where-Object { $_ -match '^version:\s*(.+)$' } | Select-Object -First 1
    if ($line) { return (($line -split ':', 2)[1]).Trim() }
    throw "No se encontró 'version:' en $md"
}

function New-SkillZip {
    param([string]$SkillName, [string]$Version, [string]$Date)
    $src = Join-Path $skillsDir $SkillName
    $zipName = "skills-$SkillName-v$Version-$Date.zip"
    $zipPath = Join-Path $distDir $zipName
    Compress-Archive -Path (Join-Path $src '*') -DestinationPath $zipPath -CompressionLevel Optimal -Force
    return $zipPath
}

function Get-FileHash8 {
    param([string]$Path)
    return ((Get-FileHash $Path -Algorithm SHA256).Hash.ToLower())
}

function Format-Size {
    param([long]$Bytes)
    if ($Bytes -ge 1MB) { return '{0:N1} MB' -f ($Bytes / 1MB) }
    return '{0:N0} KB' -f [math]::Round($Bytes / 1KB)
}

# ---------------------------------------------------------------
# 1. Generar ZIPs
# ---------------------------------------------------------------
New-Item -ItemType Directory -Path $distDir -Force | Out-Null

$builds = [ordered]@{}

if ($SkillOnly -in 'all', 'importacion-vehiculos') {
    $v = Get-SkillVersion (Join-Path $skillsDir 'importacion-vehiculos')
    $zip = New-SkillZip 'importacion-vehiculos' $v $today
    $builds['importacion-vehiculos'] = @{ Version = $v; Path = $zip }
    Write-Host "[OK] importacion-vehiculos v$v → $($zip | Split-Path -Leaf) ($(Format-Size (Get-Item $zip).Length))" -ForegroundColor Green
}

if ($SkillOnly -in 'all', 'estudio-mercado') {
    $v = Get-SkillVersion (Join-Path $skillsDir 'estudio-mercado')
    $zip = New-SkillZip 'estudio-mercado' $v $today
    $builds['estudio-mercado'] = @{ Version = $v; Path = $zip }
    Write-Host "[OK] estudio-mercado v$v → $($zip | Split-Path -Leaf) ($(Format-Size (Get-Item $zip).Length))" -ForegroundColor Green
}

# ---------------------------------------------------------------
# 2. Limpiar ZIPs viejos (mismo nombre base pero fecha distinta)
# ---------------------------------------------------------------
Get-ChildItem $distDir -Filter '*.zip' -File | Where-Object {
    $name = $_.Name
    $isOld = $true
    foreach ($k in $builds.Keys) {
        if ($name -like "skills-$k-v*-$today.zip") { $isOld = $false; break }
    }
    $isOld
} | Remove-Item -Force

Write-Host "[OK] ZIPs antiguos eliminados" -ForegroundColor Cyan

# ---------------------------------------------------------------
# 3. Actualizar docs/SKILLS.md con SHA256/fecha/tamaño
# ---------------------------------------------------------------
if (-not (Test-Path $docsFile)) {
    Write-Warning "No se encontró $docsFile, saltando actualización de docs."
} else {
    $content = Get-Content $docsFile -Raw -Encoding UTF8
    $tableLines = @()
    $tableLines += '| Skill | ZIP | Version | Tamano | SHA256 |'
    $tableLines += '|---|---|---|---|---|'
    foreach ($skill in @('importacion-vehiculos', 'estudio-mercado')) {
        if ($builds.Contains($skill)) {
            $b = $builds[$skill]
            $zipName = Split-Path $b.Path -Leaf
            $hash    = Get-FileHash8 $b.Path
            $size    = Format-Size (Get-Item $b.Path).Length
            $tableLines += ('| `{0}` | `.claude/skills/_dist/{1}` | {2} | {3} | `{4}` |' -f $skill, $zipName, $b.Version, $size, $hash)
        }
    }
    $newTable = $tableLines -join "`n"

    # Reemplaza la tabla vieja (encabezado "## 📦 ZIPs portables (builds)" → siguiente "## ").
    $heading     = '## ' + [char]0xD83D + [char]0xDCE6 + ' ZIPs portables (builds)'
    $intro       = 'Generados desde las fuentes. Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`.'
    $pattern     = '(?ms)^## .* ZIPs portables \(builds\).*?(?=^## )'
    $replacement = $heading + "`n`n" + $intro + "`n`n" + $newTable + "`n`n"
    $content     = [regex]::Replace($content, $pattern, $replacement)

    # Actualizar la fecha del manual de regenerar si está al final.
    $content = $content -replace '_Última regeneración: .*_', "_Última regeneración: $today_"

    Set-Content -Path $docsFile -Value $content -Encoding UTF8 -NoNewline
    Write-Host "[OK] docs/SKILLS.md actualizado" -ForegroundColor Cyan
}

# ---------------------------------------------------------------
# 4. Commit + push (si NoCommit no se pasó)
# ---------------------------------------------------------------
if (-not $NoCommit) {
    Push-Location $root
    try {
        git add .claude/skills/_dist/ docs/SKILLS.md | Out-Null
        $status = git status --short
        if ($status) {
            $msg = "chore(skills): regenerar ZIPs $(Get-Date -Format 'yyyy-MM-dd')"
            git commit --no-verify -m $msg | Out-Null
            git push origin master 2>&1 | Select-Object -First 3
            Write-Host "[OK] commit + push OK" -ForegroundColor Green
        } else {
            Write-Host "[INFO] nada que commitear" -ForegroundColor Yellow
        }
    } finally {
        Pop-Location
    }
}

Write-Host ""
Write-Host "=== RESUMEN ===" -ForegroundColor Magenta
foreach ($k in $builds.Keys) {
    $b = $builds[$k]
    $zip = Get-Item $b.Path
    Write-Host ("  {0,-25} v{1,-8} {2,10}  {3}" -f $k, $b.Version, (Format-Size $zip.Length), (Split-Path $zip -Leaf))
}
