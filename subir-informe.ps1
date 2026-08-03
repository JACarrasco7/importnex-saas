# ================================================================
# subir-informe.ps1 â€” Un solo comando para subir informes a ImportnexCore
# ================================================================
# Uso:
#   .\subir-informe.ps1                              â†’ sube TODOS los .json de la carpeta por defecto
#   .\subir-informe.ps1 -Archivo "ruta\informe.json"  â†’ sube UN solo archivo
#   .\subir-informe.ps1 -Carpeta "otra\carpeta"       â†’ sube todos de otra carpeta
# ================================================================

param(
    [string]$Archivo,  # un solo archivo
    [string]$Carpeta = "C:\Users\jacar\Desktop\JJImportMotors\laravel\informes"
)

$ErrorActionPreference = "Continue"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$TOKEN  = "22a600ba2f1f52eaa96a450dfd82bb9a36c26a28ee54f879e763583770a1fc32"
$API    = "https://jjimportmotors.on-forge.com/api/import-valuation"

# â”€â”€ Modo: un solo archivo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
        }
        elseif ($codigo -eq "422") {
            Write-Host "ERROR  JSON invalido o falta schema_version" -ForegroundColor Red
            Write-Host "       $body" -ForegroundColor DarkGray
        }
        elseif ($codigo -eq "401") {
            Write-Host "ERROR  Token invalido" -ForegroundColor Red
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

# â”€â”€ Modo: carpeta completa â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (-not (Test-Path $Carpeta)) {
    Write-Host "ERROR: $Carpeta no existe" -ForegroundColor Red
    exit 1
}

$archivos = Get-ChildItem $Carpeta -Filter "*.json" -ErrorAction SilentlyContinue
if ($archivos.Count -eq 0) {
    Write-Host "No hay .json en: $Carpeta" -ForegroundColor Yellow
    exit 0
}

Clear-Host
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host "   ImportnexCore - Subir $($archivos.Count) informe(s)" -ForegroundColor Magenta
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host ""

$total = $archivos.Count; $ok = 0; $fail = 0

foreach ($f in $archivos) {
    Write-Host "  $($f.Name) " -NoNewline

    $tmp = [System.IO.Path]::GetTempFileName() + ".json"
    try {
        $texto = [System.IO.File]::ReadAllText($f.FullName, [System.Text.Encoding]::UTF8)
        [System.IO.File]::WriteAllText($tmp, $texto, (New-Object System.Text.UTF8Encoding $false))

        $out = & curl.exe -s -X POST $API `
            -H "X-Import-Token: $TOKEN" `
            -H "Content-Type: application/json" `
            --data-binary "@$tmp" `
            -w "[HTTP:%{http_code}]" 2>&1

        $codigo = if ($out -match '\[HTTP:(\d+)\]') { $matches[1] } else { "0" }
        $body   = $out -replace '\[HTTP:\d+\]', ''

        if ($codigo -eq "200" -or $codigo -eq "201") {
            $data = $body | ConvertFrom-Json
            Write-Host "car_id=$($data.car_id) $($data.status)" -ForegroundColor Green
            $ok++
        }
        else {
            Write-Host "FAIL HTTP $codigo" -ForegroundColor Red
            $fail++
        }
    }
    catch {
        Write-Host "FAIL $_" -ForegroundColor Red
        $fail++
    }
    finally {
        if (Test-Path $tmp) { Remove-Item $tmp -Force }
    }
}

Write-Host ""
Write-Host "OK: $ok / FAIL: $fail / TOTAL: $total" -ForegroundColor Magenta
