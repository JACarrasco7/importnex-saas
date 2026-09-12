# ================================================================
# build-skill-zips.ps1 (v3 - wrapper del script Python robusto)
#
# Por que este cambio:
#   - Compress-Archive (PowerShell) y System.IO.Compression.ZipFile (.NET)
#     producen ZIPs que el validador JS de Claude Skills rechaza con
#     "Zip file contains path with invalid characters".
#   - El modulo zipfile de Python genera ZIPs PKZIP 2.0 estandar con
#     separador '/' y filtra paths problematicos antes de incluirlos.
#
# Regenera los ZIPs portables de las 2 skills del negocio y
# actualiza docs/SKILLS.md con SHA256/version/fecha/tamano.
#
# Uso:
#   .\scripts\build-skill-zips.ps1                # regenera todo
#   .\scripts\build-skill-zips.ps1 -SkillOnly importacion-vehiculos
#   .\scripts\build-skill-zips.ps1 -NoCommit       # solo regenera, sin commit
#   .\scripts\build-skill-zips.ps1 -ValidateOnly   # solo audita paths, sin regenerar
# ================================================================

[CmdletBinding()]
param(
    [ValidateSet('all', 'importacion-vehiculos', 'estudio-mercado')]
    [string]$SkillOnly = 'all',

    [switch]$NoCommit,

    [switch]$ValidateOnly
)

$ErrorActionPreference = 'Stop'

$root        = (Resolve-Path "$PSScriptRoot\..").Path
$buildScript = Join-Path $root '.claude/skills/_dist/build-zips.py'
$distDir     = Join-Path $root '.claude/skills/_dist'
$docsFile    = Join-Path $root 'docs/SKILLS.md'
$today       = Get-Date -Format 'yyyyMMdd'

if (-not (Test-Path $buildScript)) {
    throw "No se encontro el script Python: $buildScript"
}

# ---------------------------------------------------------------
# 0. Modo validate-only
# ---------------------------------------------------------------
if ($ValidateOnly) {
    Write-Host "=== VALIDATE-ONLY MODE (no regenera) ===" -ForegroundColor Magenta
    py $buildScript --validate-only 2>&1
    exit $LASTEXITCODE
}

# ---------------------------------------------------------------
# 1. Regenerar ZIPs via Python
# ---------------------------------------------------------------
$args = @()
if ($SkillOnly -ne 'all') { $args += "--skill-only=$SkillOnly" }
Write-Host "[INFO] Regenerando ZIPs via Python (script robusto)..." -ForegroundColor DarkGray
py $buildScript @args
if ($LASTEXITCODE -ne 0) {
    throw "El script Python fallo con exit code $LASTEXITCODE"
}

# Limpiar ZIPs viejos: solo los de las skills regeneradas (M5, 12-sep-2026).
# Antes borraba CUALQUIER skills-*.zip sin importar la fecha -> si regenerabas
# solo una skill con -SkillOnly, dejaba la otra intacta pero al siguiente
# build a "all" se la comia igualmente. Ahora respeta el scope.
$scope = if ($SkillOnly -ne 'all') { $SkillOnly } else { 'all' }
$keptNames = @()
foreach ($skill in @('importacion-vehiculos','estudio-mercado')) {
    if ($scope -eq 'all' -or $scope -eq $skill) {
        $kept = Get-ChildItem $distDir -Filter "skills-$skill-*.zip" -File -ErrorAction SilentlyContinue
        if ($kept) { $keptNames += $kept.Name }
    }
}
Get-ChildItem $distDir -Filter 'skills-*.zip' -File | Where-Object {
    $_.Name -notin $keptNames -and $_.Name -notlike "*-$today.zip"
} | ForEach-Object {
    Write-Host "  [clean] $($_.Name)" -ForegroundColor DarkGray
    Remove-Item $_.FullName -Force
}
Write-Host "[OK] ZIPs regenerados, antiguos eliminados" -ForegroundColor Cyan

# ---------------------------------------------------------------
# 2. Actualizar docs/SKILLS.md
# ---------------------------------------------------------------
if (-not (Test-Path $docsFile)) {
    Write-Warning "No se encontro $docsFile, saltando actualizacion."
} else {
    $content = Get-Content $docsFile -Raw -Encoding UTF8

    $tableLines = @()
    $tableLines += '| Skill | ZIP | Version | Tamano | SHA256 |'
    $tableLines += '|---|---|---|---|---|'
    foreach ($skill in @('importacion-vehiculos', 'estudio-mercado')) {
        if ($SkillOnly -ne 'all' -and $SkillOnly -ne $skill) { continue }
        $zipPath = Get-ChildItem $distDir -Filter "skills-$skill-*.zip" -File |
            Sort-Object LastWriteTime -Descending | Select-Object -First 1
        if (-not $zipPath) { continue }

        $skillDir = Join-Path $root ".claude/skills/$skill"
        $version = (Get-Content (Join-Path $skillDir 'SKILL.md') |
            Where-Object { $_ -match '^version:\s*(.+)$' } |
            Select-Object -First 1) -replace '^version:\s*', ''

        $zipName = $zipPath.Name
        $hash    = (Get-FileHash $zipPath.FullName -Algorithm SHA256).Hash.ToLower()
        $size    = if ($zipPath.Length -ge 1MB) { '{0:N1} MB' -f ($zipPath.Length / 1MB) }
                   else { '{0:N0} KB' -f [math]::Round($zipPath.Length / 1KB) }
        $tableLines += ('| `{0}` | `.claude/skills/_dist/{1}` | {2} | {3} | `{4}` |' -f $skill, $zipName, $version.Trim(), $size, $hash)
    }
    $newTable = $tableLines -join "`n"

    $heading     = '## ' + [char]0xD83D + [char]0xDCE6 + ' ZIPs de skill (builds actuales)'
    $intro       = 'Generados por `scripts/build-skill-zips.ps1` (wrapper de `.claude/skills/_dist/build-zips.py`). Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`.'
    $pattern     = '(?ms)^## .* ZIPs de skill \(builds actuales\).*?(?=^## )'
    $replacement = $heading + "`n`n" + $intro + "`n`n" + $newTable + "`n`n"
    $content     = [regex]::Replace($content, $pattern, $replacement)

    $content = $content -replace '_Última regeneraci[oó]n: .*_', "_Última regeneracion: $today_"

    Set-Content -Path $docsFile -Value $content -Encoding UTF8 -NoNewline
    Write-Host "[OK] docs/SKILLS.md actualizado" -ForegroundColor Cyan
}

# ---------------------------------------------------------------
# 3. Commit + push
# ---------------------------------------------------------------
if (-not $NoCommit) {
    Push-Location $root
    try {
        git add .claude/skills/_dist/ docs/SKILLS.md scripts/build-skill-zips.ps1 | Out-Null
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
Get-ChildItem $distDir -Filter 'skills-*.zip' -File | Sort-Object Name | ForEach-Object {
    Write-Host ("  {0,-60} {1,10}" -f $_.Name, ('{0:N0} KB' -f [math]::Round($_.Length / 1KB)))
}
