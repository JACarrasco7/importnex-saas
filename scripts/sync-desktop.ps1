<#
.SYNOPSIS
    Sincroniza masters del repo (docs/) con Desktop (Claude Desktop) y verifica datos_mercado.json.

.DESCRIPTION
    Reemplaza al .bat del ZIP de Claude. Un solo comando para:

    1. Replicar los 4 ficheros de docs/claude-desktop/  -> raiz de Desktop
    2. Replicar los 7 ficheros de docs/memoria-desktop/  -> .claude/ + .claude/memoria/ de Desktop
    3. Mantener datos_mercado.json sincronizado entre Desktop y .claude/skills/ del repo
    4. Verificar versiones de skills instaladas en Desktop contra .claude/skills/_dist/

    Detecta divergencias, reporta hashes y se niega a borrar nada del lado Desktop
    sin confirmacion. Modo WhatIf por defecto; -Apply replica.

.PARAMETER DesktopRoot
    Ruta raiz de la carpeta Desktop. Por defecto C:\Users\jacar\Desktop\JJImportMotors

.PARAMETER Apply
    Si NO se pasa, solo informa (modo WhatIf). Con -Apply replica los ficheros.

.EXAMPLE
    .\sync-desktop.ps1
    .\sync-desktop.ps1 -Apply

.NOTES
    Regla de sincronizacion: ver .ai/rules/skills-sync.md y .ai/rules/doc-sync.md.
    IMPORTANTE: este script NO contiene em-dashes. PowerShell 5.1 (ISE) los parsea
    mal dentro de literales; ver .ai/rules/doc-sync.md "Trampas conocidas".
#>

[CmdletBinding()]
param(
    [string] $DesktopRoot = 'C:\Users\jacar\Desktop\JJImportMotors',
    [switch]  $Apply
)

$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path "$PSScriptRoot\..").Path

$MasterDesktop  = Join-Path $RepoRoot 'docs\claude-desktop'
$MasterMemoria  = Join-Path $RepoRoot 'docs\memoria-desktop'
$RepoDataJson   = Join-Path $RepoRoot '.claude\skills\datos_mercado.json'
$DistSkills     = Join-Path $RepoRoot '.claude\skills\_dist'
$DesktopSkillDir = Join-Path $DesktopRoot '.claude\skills'

# ---------- helpers ----------
function Get-HashSafe($path) {
    if (Test-Path $path) { return (Get-FileHash $path -Algorithm SHA256).Hash }
    return $null
}

function ShortHash($h) {
    if ($h) { return $h.Substring(0, [Math]::Min(12, $h.Length)) }
    return 'MISSING'
}

function Compare-AndReport {
    param($label, $masterPath, $destPath)
    $hMaster = Get-HashSafe $masterPath
    $hDest   = Get-HashSafe $destPath
    $same    = ($hMaster -and $hDest -and $hMaster -eq $hDest)
    if ($same) {
        Write-Host ("  [OK]   {0,-40}  dest OK" -f $label) -ForegroundColor Green
    } elseif ($hMaster -and -not $hDest) {
        Write-Host ("  [NEW]  {0,-40}  no existe en Desktop" -f $label) -ForegroundColor Yellow
    } elseif ($hMaster -and $hDest) {
        Write-Host ("  [DIFF] {0,-40}  master={1}  dest={2}" -f $label, (ShortHash $hMaster), (ShortHash $hDest)) -ForegroundColor Red
    } else {
        Write-Host ("  [???]  {0,-40}" -f $label) -ForegroundColor Gray
    }
    return $same
}

# ---------- 0. cabecera ----------
$mode = if ($Apply) { 'APLICAR (escribe)' } else { 'WHATIF (solo informa)' }
Write-Host ''
Write-Host "=== sync-desktop.ps1 :: $mode ===" -ForegroundColor Cyan
Write-Host ("  Repo    : {0}" -f $RepoRoot)
Write-Host ("  Desktop : {0}" -f $DesktopRoot)
Write-Host ("  Token   : {0}" -f $(if ($env:IMPORTNEX_TOKEN) {'OK (env)'} else {'NO cargado (exporta IMPORTNEX_TOKEN)'}))
Write-Host ''

if (-not (Test-Path $DesktopRoot)) {
    Write-Host "ERROR: DesktopRoot no existe: $DesktopRoot" -ForegroundColor Red
    exit 2
}
if (-not (Test-Path $MasterDesktop)) {
    Write-Host "ERROR: no hay docs/claude-desktop/ en el repo" -ForegroundColor Red
    exit 2
}

$desktopCLAUDE  = Join-Path $DesktopRoot '.claude'
$desktopMemoria = Join-Path $DesktopRoot '.claude\memoria'
$desktopDataJson = Join-Path $DesktopRoot 'datos_mercado.json'

# ---------- 1. claude-desktop -> raiz Desktop ----------
Write-Host '--- 1. claude-desktop (raiz Desktop, 4 ficheros) ---' -ForegroundColor Cyan
$mapDesktop = [ordered]@{
    'CLAUDE.md'                = 'CLAUDE.md'
    'README.md'                = 'README.md'
    'GUIA_INICIO_RAPIDO.md'    = 'GUIA_INICIO_RAPIDO.md'
    'INSTRUCCIONES_PROYECTO.md'= 'INSTRUCCIONES_PROYECTO.md'
}
$allSync = $true
foreach ($k in $mapDesktop.Keys) {
    $src = Join-Path $MasterDesktop $k
    $dst = Join-Path $DesktopRoot  $mapDesktop[$k]
    $ok  = Compare-AndReport -label $k -masterPath $src -destPath $dst
    if (-not $ok) { $allSync = $false }
    if ($Apply -and (Test-Path $src) -and -not $ok) {
        Copy-Item $src $dst -Force
        Write-Host "       -> copiado" -ForegroundColor DarkGray
    }
}

# ---------- 2. memoria-desktop -> .claude/ + .claude/memoria/ ----------
Write-Host ''
Write-Host '--- 2. memoria-desktop (.claude/, 7 ficheros) ---' -ForegroundColor Cyan
$mapMemoria = [ordered]@{
    'MEMORIA.md'           = (Join-Path $desktopCLAUDE 'MEMORIA.md')
    'decisiones.md'        = (Join-Path $desktopMemoria 'decisiones.md')
    'errores-pasados.md'   = (Join-Path $desktopMemoria 'errores-pasados.md')
    'memoria-corto.md'     = (Join-Path $desktopMemoria 'memoria-corto.md')
    'memoria-larga.md'     = (Join-Path $desktopMemoria 'memoria-larga.md')
    'preferencias.md'      = (Join-Path $desktopMemoria 'preferencias.md')
    'proyectos-activos.md' = (Join-Path $desktopMemoria 'proyectos-activos.md')
}
foreach ($k in $mapMemoria.Keys) {
    $src = Join-Path $MasterMemoria $k
    $dst = $mapMemoria[$k]
    $ok  = Compare-AndReport -label $k -masterPath $src -destPath $dst
    if (-not $ok) { $allSync = $false }
    if ($Apply -and (Test-Path $src) -and -not $ok) {
        $dstDir = Split-Path $dst
        if (-not (Test-Path $dstDir)) { New-Item -ItemType Directory -Force -Path $dstDir | Out-Null }
        Copy-Item $src $dst -Force
        Write-Host "       -> copiado" -ForegroundColor DarkGray
    }
}

# ---------- 3. datos_mercado.json dual ----------
Write-Host ''
Write-Host '--- 3. datos_mercado.json (ruta dual) ---' -ForegroundColor Cyan
$hRepo = Get-HashSafe $RepoDataJson
$hDesk = Get-HashSafe $desktopDataJson
$sameDM = ($hRepo -and $hDesk -and $hRepo -eq $hDesk)
if ($sameDM) {
    Write-Host "  [OK]   sincronizado" -ForegroundColor Green
} elseif ($hRepo -and -not $hDesk) {
    Write-Host "  [NEW]  solo en repo; Desktop no lo tiene" -ForegroundColor Yellow
    if ($Apply) { Copy-Item $RepoDataJson $desktopDataJson -Force; Write-Host "       -> copiado" -ForegroundColor DarkGray }
} elseif ($hRepo -and $hDesk) {
    Write-Host ("  [DIFF] repo={0}  desk={1}" -f (ShortHash $hRepo), (ShortHash $hDesk)) -ForegroundColor Red
    Write-Host "         politica: repo canonico -> Desktop es espejo" -ForegroundColor DarkGray
    if ($Apply) { Copy-Item $RepoDataJson $desktopDataJson -Force; Write-Host "       -> repo sobreescribe Desktop" -ForegroundColor DarkGray }
} else {
    Write-Host "  [???]  ambos ausentes" -ForegroundColor Gray
}

# ---------- 4. Skills instaladas en Desktop vs _dist/ ----------
Write-Host ''
Write-Host '--- 4. Skills en Desktop vs repo (_dist/) ---' -ForegroundColor Cyan
if (-not (Test-Path $DistSkills)) {
    Write-Host "  No hay .claude/skills/_dist/ - ejecuta scripts/build-skill-zips.ps1 primero" -ForegroundColor Yellow
} else {
    $zips = Get-ChildItem $DistSkills -Filter '*.zip'
    if (-not $zips) {
        Write-Host "  _dist/ vacio - ejecuta scripts/build-skill-zips.ps1 primero" -ForegroundColor Yellow
    } else {
        foreach ($z in $zips) {
            $zipName = $z.Name
            $repoName = '?'; $repoVer = '?'
            if ($zipName -match '^skills-([\w-]+)-v([\d.]+)-') {
                $repoName = $Matches[1]; $repoVer = $Matches[2]
            }
            Write-Host ("  repo ZIP: {0,-45}  v{1}" -f $zipName, $repoVer) -ForegroundColor DarkGray
            if (Test-Path $DesktopSkillDir) {
                $installed = Get-ChildItem $DesktopSkillDir -Directory -ErrorAction SilentlyContinue | Where-Object {
                    $_.Name -match 'importacion-vehiculos|estudio-mercado'
                }
                foreach ($d in $installed) {
                    $skillMd = Join-Path $d.FullName 'SKILL.md'
                    $ver = '?'
                    if (Test-Path $skillMd) {
                        $line = Get-Content $skillMd -TotalCount 5 -ErrorAction SilentlyContinue | Select-String -Pattern '^version:\s*(\S+)' | Select-Object -First 1
                        if ($line) { $ver = ($line -split ':\s*', 2)[1].Trim() }
                    }
                    $match = ($ver -eq $repoVer)
                    $status = if ($match) { 'OK' } else { 'DIFF' }
                    $color  = if ($match) { 'Green' } else { 'Red' }
                    Write-Host ("    [{0}] Desktop: {1,-35} v{2}   repo v{3}" -f $status, $d.Name, $ver, $repoVer) -ForegroundColor $color
                }
            } else {
                Write-Host ("    (no se encontro {0})" -f $DesktopSkillDir) -ForegroundColor Yellow
            }
        }
    }
}

# ---------- resumen ----------
Write-Host ''
Write-Host '--- resumen ---' -ForegroundColor Cyan
if ($allSync) {
    Write-Host "Todo sincronizado. Sin accion." -ForegroundColor Green
    exit 0
} elseif ($Apply) {
    Write-Host "Replica aplicada. Revisa arriba que no haya errores." -ForegroundColor Yellow
    exit 0
} else {
    Write-Host "Hay divergencias. Vuelve a ejecutar con -Apply para sincronizar." -ForegroundColor Yellow
    exit 1
}
