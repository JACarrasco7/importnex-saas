# ================================================================
# subir-informe.ps1 -- Un solo comando para subir informes a ImportnexCore
# ================================================================
# Uso:
#   .\subir-informe.ps1                              -> sube TODOS los .json de la carpeta por defecto
#   .\subir-informe.ps1 -Archivo "ruta\informe.json"  -> sube UN solo archivo
#   .\subir-informe.ps1 -Carpeta "otra\carpeta"       -> sube todos de otra carpeta
#   .\subir-informe.ps1 -NoRoundTrip                  -> NO escribir en encargos.md del skill
#
# Token de la API: se lee de $env:IMPORTNEX_TOKEN. NO esta hardcodeado.
# Para configurarlo una sola vez por maquina (PowerShell):
#   [Environment]::SetEnvironmentVariable("IMPORTNEX_TOKEN","<token>","User")
# O para una sola sesion:
#   $env:IMPORTNEX_TOKEN = "<token>"
# ================================================================

param(
    [string]$Archivo,                                                 # un solo archivo
    [string]$Carpeta = "C:\Users\jacar\Desktop\JJImportMotors\laravel\informes",
    [switch]$NoRoundTrip
)

$ErrorActionPreference = "Continue"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# --- Token desde entorno (NO hardcoded) ------------------------------------
$TOKEN = $env:IMPORTNEX_TOKEN
if (-not $TOKEN) {
    Write-Host "ERROR: variable de entorno IMPORTNEX_TOKEN no definida." -ForegroundColor Red
    Write-Host "  Configurala una vez:" -ForegroundColor Yellow
    Write-Host '  [Environment]::SetEnvironmentVariable("IMPORTNEX_TOKEN","<token>","User")' -ForegroundColor Yellow
    exit 2
}

$API = "https://jjimportmotors.on-forge.com/api/import-valuation"

# --- Round-trip: registro de encargos del skill ----------------------------
$skillEncargos = Join-Path $env:USERPROFILE 'Desktop\JJImportMotors\.claude\skills\importacion-vehiculos\memoria\encargos.md'

function Write-RoundTrip($jsonPath, $carId, $carUrl) {
    if ($NoRoundTrip) { return }
    if (-not (Test-Path $skillEncargos)) {
        Write-Host "  (Round-trip: no existe $skillEncargos -- saltado)" -ForegroundColor DarkGray
        return
    }
    try {
        $j = Get-Content $jsonPath -Raw -Encoding UTF8 | ConvertFrom-Json
        $cliente  = if ($j._meta.client_id) { $j._meta.client_id } else { 'anonimo' }
        $modelo   = if ($j.vehiculo.marca -and $j.vehiculo.modelo) { "$($j.vehiculo.marca) $($j.vehiculo.modelo)" } else { 'sin modelo' }
        $fecha    = Get-Date -Format 'yyyy-MM-dd HH:mm'
        $linea    = "### $cliente - $modelo - $fecha (auto-subido)"
        $bloque   = @"

$linea
- **Tipo:** AUTO-SUBIDO via subir-informe.ps1
- **Estado:** importado a Laravel
- **Entregables:** ZIP -> coche_id=$carId
- **Resultado:** $carUrl
- **Notas:** [round-trip automatico por subir-informe.ps1 - revisar manualmente]

"@
        Add-Content -Path $skillEncargos -Value $bloque -Encoding UTF8
        Write-Host "  > round-trip anotado en encargos.md" -ForegroundColor DarkGray
    } catch {
        Write-Host "  (Round-trip fallo: $_)" -ForegroundColor Yellow
    }
}

# -- Modo: un solo archivo -------------------------------------------------
if ($Archivo) {
    if (-not (Test-Path $Archivo)) {
        Write-Host "ERROR: $Archivo no existe" -ForegroundColor Red
        exit 1
    }

    $nombre = Split-Path $Archivo -Leaf
    Write-Host "Subiendo: $nombre" -ForegroundColor Cyan

    $tmp = [System.IO.Path]::GetTempFileName() + ".json"
    try {
        $texto = [System.IO.File]::ReadAllText((Resolve-Path $Archivo), [System.Text.Encoding]::UTF8)
        [System.IO.File]::WriteAllText($tmp, $texto, (New-Object System.Text.UTF8Encoding $false))

        $ok = & curl.exe -s -X POST $API `
            -H "X-Import-Token: $TOKEN" `
            -H "Content-Type: application/json" `
            --data-binary "@$tmp" `
            -w "[HTTP:%{http_code}]" 2>&1

        $codigo = if ($ok -match '\[HTTP:(\d+)\]') { $matches[1] } else { "0" }
        $body   = $ok -replace '\[HTTP:\d+\]', ''

        if ($codigo -eq "200" -or $codigo -eq "201") {
            $data = $body | ConvertFrom-Json
            Write-Host "LISTO  car_id=$($data.car_id)  $($data.status)" -ForegroundColor Green
            Write-Host "       $($data.car_url)" -ForegroundColor DarkGray
            Write-RoundTrip $Archivo $data.car_id $data.car_url
        }
        elseif ($codigo -eq "422") {
            Write-Host "ERROR  JSON invalido o falta schema_version" -ForegroundColor Red
            Write-Host "       $body" -ForegroundColor DarkGray
        }
        elseif ($codigo -eq "401") {
            Write-Host "ERROR  Token invalido (revisa IMPORTNEX_TOKEN)" -ForegroundColor Red
        }
        else {
            Write-Host "ERROR  HTTP $codigo" -ForegroundColor Red
            Write-Host "       $body" -ForegroundColor DarkGray
        }
    }
    catch {
        Write-Host "ERROR  $_" -ForegroundColor Red
    }
    finally {
        if (Test-Path $tmp) { Remove-Item $tmp -Force }
    }
    exit 0
}

# -- Modo: carpeta completa ------------------------------------------------
if (-not (Test-Path $Carpeta)) {
    Write-Host "ERROR: $Carpeta no existe" -ForegroundColor Red
    exit 1
}

$archivos = Get-ChildItem $Carpeta -Filter "*.json" -ErrorAction SilentlyContinue
if (-not $archivos -or $archivos.Count -eq 0) {
    Write-Host "No hay .json en: $Carpeta" -ForegroundColor Yellow
    exit 0
}

$okCount = 0; $failCount = 0
foreach ($f in $archivos) {
    Write-Host "Subiendo: $($f.Name)" -ForegroundColor Cyan
    $tmp = [System.IO.Path]::GetTempFileName() + ".json"
    try {
        $texto = [System.IO.File]::ReadAllText($f.FullName, [System.Text.Encoding]::UTF8)
        [System.IO.File]::WriteAllText($tmp, $texto, (New-Object System.Text.UTF8Encoding $false))
        $r = & curl.exe -s -X POST $API -H "X-Import-Token: $TOKEN" -H "Content-Type: application/json" --data-binary "@$tmp" -w "[HTTP:%{http_code}]" 2>&1
        $codigo = if ($r -match '\[HTTP:(\d+)\]') { $matches[1] } else { "0" }
        $body   = $r -replace '\[HTTP:\d+\]', ''
        if ($codigo -eq "200" -or $codigo -eq "201") {
            $data = $body | ConvertFrom-Json
            Write-Host "  LISTO  car_id=$($data.car_id)" -ForegroundColor Green
            Write-RoundTrip $f.FullName $data.car_id $data.car_url
            $okCount++
        } else {
            Write-Host "  ERROR  HTTP $codigo  $body" -ForegroundColor Red
            $failCount++
        }
    } catch {
        Write-Host "  ERROR  $_" -ForegroundColor Red
        $failCount++
    } finally {
        if (Test-Path $tmp) { Remove-Item $tmp -Force }
    }
}
Write-Host ""
Write-Host "Resumen: $okCount OK, $failCount fallidos" -ForegroundColor $(if ($failCount -eq 0) {'Green'} else {'Yellow'})
exit 0
