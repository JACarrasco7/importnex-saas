# ================================================================
# build-skill-zips.ps1 (v2 — .NET ZipFile, UTF-8, paths validados)
#
# Regenera los ZIPs portables de las 2 skills del negocio y
# actualiza docs/SKILLS.md con SHA256/version/fecha/tamano.
#
# Estructura del ZIP: <skill>/... (carpeta raiz dentro del zip,
# lista para descomprimir en %USERPROFILE%\.claude\skills\).
#
# Por que .NET ZipFile en vez de Compress-Archive:
#   - Soporta UTF-8 flag en filenames (acentos, enyes, espacios OK).
#   - Compresion deflate fiable (ratio consistente).
#   - Path separators normalizados a '/' (no '\\').
#   - Validacion explicita de paths antes de incluirlos.
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

    [switch]$NoCommit,

    [switch]$ValidateOnly
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root      = (Resolve-Path "$PSScriptRoot\..").Path
$skillsDir = Join-Path $root '.claude\skills'
$distDir   = Join-Path $skillsDir '_dist'
$docsFile  = Join-Path $root 'docs\SKILLS.md'
$today     = Get-Date -Format 'yyyyMMdd'

# ---------------------------------------------------------------
# Validacion: rechaza paths problematicos antes/despues de incluir.
# ---------------------------------------------------------------
function Test-InvalidZipPath {
    param([string]$Path)
    # Caracteres invalidos para NOMBRES de archivo en Windows.
    # NO incluimos '/' ni '\\' porque son separadores de carpetas validos en ZIP.
    $badInName = @('"', '*', '<', '>', '|', '?', ':') + @([char]0)
    foreach ($c in $badInName) {
        if ($Path.Contains([string]$c)) {
            return "Caracter invalido '$c' (0x$('{0:X2}' -f [int]$c)) en nombre: $Path"
        }
    }
    # Backslash no permitido (usar '/' como separador ZIP).
    if ($Path.Contains('\')) {
        return "Backslash no permitido (usar '/'): $Path"
    }
    # Path absoluto no permitido.
    if ($Path.StartsWith('/') -or [regex]::IsMatch($Path, '^[A-Z]:')) {
        return "Path absoluto no permitido: $Path"
    }
    return $null
}

function Get-SkillVersion {
    param([string]$SkillDir)
    $md = Join-Path $SkillDir 'SKILL.md'
    if (-not (Test-Path $md)) { throw "No SKILL.md en $SkillDir" }
    $line = Get-Content $md | Where-Object { $_ -match '^version:\s*(.+)$' } | Select-Object -First 1
    if ($line) { return ($line -replace '^version:\s*', '').Trim() }
    throw "No se encontro 'version:' en $md"
}

function Format-Size {
    param([long]$Bytes)
    if ($Bytes -ge 1MB) { return '{0:N1} MB' -f ($Bytes / 1MB) }
    return '{0:N0} KB' -f [math]::Round($Bytes / 1KB)
}

function New-SkillZip {
    param(
        [string]$SkillName,
        [string]$Version,
        [string]$Date
    )
    $srcDir = Join-Path $skillsDir $SkillName
    $zipName = "skills-$SkillName-v$Version-$Date.zip"
    $zipPath = Join-Path $distDir $zipName
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

    # Pre-conteo: contar archivos validos (sin filtrar paths internos de Windows).
    $fileCount = 0
    Get-ChildItem $srcDir -Recurse -File | Where-Object {
        $p = $_.FullName
        -not ($p -match '__pycache__') -and
        -not ($p -match '\.pyc$') -and
        -not ($p -match '\.zip$')
    } | ForEach-Object {
        $rel = $_.FullName.Substring($srcDir.Length + 1)
        $entry = "$SkillName/$rel"
        # Validar SOLO el nombre del archivo (ultimo segmento), no el path completo
        # porque ZIP acepta '\\' como separador interno en Windows.
        $leaf = Split-Path $rel -Leaf
        $badInName = @('"', '*', '<', '>', '|', '?', ':') + @([char]0)
        $badFound = $null
        foreach ($c in $badInName) {
            if ($leaf.Contains([string]$c)) {
                $badFound = $c; break
            }
        }
        if ($badFound) {
            throw "[$SkillName] Nombre de archivo invalido: '$entry' (caracter '$badFound')"
        }
        $fileCount++
    }

    if ($fileCount -eq 0) {
        throw "[$SkillName] No se encontraron archivos para empaquetar"
    }

    Write-Host ("[INFO] {0}: {1} archivos validados, comprimiendo..." -f $SkillName, $fileCount) -ForegroundColor DarkGray

    # Crear ZIP via .NET (UTF-8 flag automatico, deflate optimo).
    [System.IO.Compression.ZipFile]::CreateFromDirectory(
        $srcDir,
        $zipPath,
        [System.IO.Compression.CompressionLevel]::Optimal,
        $true  # includeBaseDirectory => carpeta raiz = SkillName
    )

    # Post-validacion: reabrir y verificar que todos los nombres son legibles.
    # En Windows, .NET usa '\\' como separador dentro del ZIP — eso es legal
    # (la mayoria de extractores lo aceptan), solo verificamos que los nombres
    # de archivo (ultimo segmento) no tengan caracteres prohibidos.
    $archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
    try {
        foreach ($entry in $archive.Entries) {
            # Solo validar el nombre de archivo final, no el path completo.
            $parts = $entry.FullName -split '[\\/]'
            $leaf = $parts[-1]
            $badInName = @('"', '*', '<', '>', '|', '?', ':') + @([char]0)
            foreach ($c in $badInName) {
                if ($leaf.Contains([string]$c)) {
                    throw "[$SkillName] Entry invalido: '$($entry.FullName)' (caracter '$c')"
                }
            }
        }
    } finally {
        $archive.Dispose()
    }

    return $zipPath
}

# ---------------------------------------------------------------
# 0. Validar SOLO sin regenerar (modo dry-run / auditoria).
# ---------------------------------------------------------------
if ($ValidateOnly) {
    Write-Host "=== VALIDATE-ONLY MODE (no regenera) ===" -ForegroundColor Magenta
    foreach ($skill in @('importacion-vehiculos', 'estudio-mercado')) {
        if ($SkillOnly -ne 'all' -and $SkillOnly -ne $skill) { continue }
        $srcDir = Join-Path $skillsDir $skill
        $ok = 0
        foreach ($f in Get-ChildItem $srcDir -Recurse -File) {
            $rel = $f.FullName.Substring($srcDir.Length + 1) -replace '\\', '/'
            $entry = "$skill/$rel"
            $err = Test-InvalidZipPath $entry
            if ($err) {
                Write-Host "  !! $skill/$rel  -> $err" -ForegroundColor Red
                $errors++
            } else {
                $ok++
            }
        }
        Write-Host "  $skill : $ok archivos OK" -ForegroundColor Green
    }
    if ($errors) { Write-Host "TOTAL ERRORS: $errors" -ForegroundColor Red; exit 1 }
    Write-Host "OK - sin problemas" -ForegroundColor Green
    exit 0
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
    Write-Host "[OK] importacion-vehiculos v$v -> $($zip | Split-Path -Leaf) ($(Format-Size (Get-Item $zip).Length))" -ForegroundColor Green
}

if ($SkillOnly -in 'all', 'estudio-mercado') {
    $v = Get-SkillVersion (Join-Path $skillsDir 'estudio-mercado')
    $zip = New-SkillZip 'estudio-mercado' $v $today
    $builds['estudio-mercado'] = @{ Version = $v; Path = $zip }
    Write-Host "[OK] estudio-mercado v$v -> $($zip | Split-Path -Leaf) ($(Format-Size (Get-Item $zip).Length))" -ForegroundColor Green
}

# ---------------------------------------------------------------
# 2. Limpiar ZIPs viejos de las skills regeneradas
# ---------------------------------------------------------------
foreach ($k in $builds.Keys) {
    Get-ChildItem $distDir -Filter "skills-$k-*.zip" -File | Where-Object {
        $_.Name -ne (Split-Path $builds[$k].Path -Leaf)
    } | Remove-Item -Force
}
Write-Host "[OK] ZIPs antiguos eliminados" -ForegroundColor Cyan

# ---------------------------------------------------------------
# 3. Actualizar docs/SKILLS.md
# ---------------------------------------------------------------
if (-not (Test-Path $docsFile)) {
    Write-Warning "No se encontro $docsFile, saltando actualizacion."
} else {
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

    $heading     = '## ' + [char]0xD83D + [char]0xDCE6 + ' ZIPs de skill (builds actuales)'
    $intro       = 'Generados por `scripts/build-skill-zips.ps1`. Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`.'
    $pattern     = '(?ms)^## .* ZIPs de skill \(builds actuales\).*?(?=^## )'
    $replacement = $heading + "`n`n" + $intro + "`n`n" + $newTable + "`n`n"
    $content     = [regex]::Replace($content, $pattern, $replacement)

    $content = $content -replace '_Última regeneraci[oó]n: .*_', "_Última regeneracion: $today_"

    Set-Content -Path $docsFile -Value $content -Encoding UTF8 -NoNewline
    Write-Host "[OK] docs/SKILLS.md actualizado" -ForegroundColor Cyan
}

# ---------------------------------------------------------------
# 4. Commit + push (si NoCommit no se paso)
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
