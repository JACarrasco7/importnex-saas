# ================================================================
# import-notify-receiver.ps1 -- Receptor HTTP local del webhook de import
# ================================================================
# Lo ejecuta Laravel Forge (remoto) tras importar un coche via API.
# Escucha en 127.0.0.1:8765 y reescribe encargos.md del skill
# importacion-vehiculos, para que el siguiente encargo de Claude
# Desktop vea el coche ya importado sin esperar a subir-informe.ps1.
#
# Arranque:
#   $env:IMPORTNEX_CHAT_WEBHOOK_SECRET = '<clave>'
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\import-notify-receiver.ps1
#
# IMPORTANTE: solo para uso LOCAL. En produccion (Forge) el script NO
# se ejecuta: el listener NotifyImportWebhook hace un POST a esta URL
# en tu maquina local, asi que el firewall debe dejar pasar la peticion
# o necesitas un tunnel (ngrok, Tailscale, etc.). Si no lo necesitas,
# deja IMPORTNEX_CHAT_WEBHOOK_URL vacio en .env (feature desactivada).
# ================================================================

param(
    [int]$Port = 8765,
    [string]$Bind = '127.0.0.1'
)

$ErrorActionPreference = 'Stop'

$encargosPath = Join-Path $env:USERPROFILE 'Desktop\JJImportMotors\.claude\skills\importacion-vehiculos\memoria\encargos.md'
$secret = $env:IMPORTNEX_CHAT_WEBHOOK_SECRET

if (-not $secret) {
    Write-Host 'ERROR: IMPORTNEX_CHAT_WEBHOOK_SECRET no definida.' -ForegroundColor Red
    exit 2
}

Write-Host "=== import-notify-receiver :: ${Bind}:${Port} ===" -ForegroundColor Cyan
Write-Host "  encargos.md -> $encargosPath" -ForegroundColor DarkGray

$listener = [System.Net.HttpListener]::new()
$listener.Prefixes.Add("http://${Bind}:${Port}/")
$listener.Start()

try {
    while ($listener.IsListening) {
        $ctx = $listener.GetContext()
        $req = $ctx.Request
        $resp = $ctx.Response

        try {
            if ($req.HttpMethod -ne 'POST' -or $req.Url.AbsolutePath -ne '/') {
                $resp.StatusCode = 404
                $resp.Close()
                continue
            }

            # Leer body
            $reader = [System.IO.StreamReader]::new($req.InputStream, [System.Text.Encoding]::UTF8)
            $body = $reader.ReadToEnd()
            $reader.Close()

            # Validar HMAC si llega la firma
            $sig = $req.Headers['X-Webhook-Signature']
            if ($sig) {
                $keyBytes = [System.Text.Encoding]::UTF8.GetBytes($secret)
                $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($body)
                $hmac = New-Object System.Security.Cryptography.HMACSHA256($keyBytes)
                $expected = -join ($hmac.ComputeHash($bodyBytes) | ForEach-Object { $_.ToString('x2') })
                if ($expected -ne $sig) {
                    Write-Host '  ERROR  HMAC invalido - descartado' -ForegroundColor Red
                    $resp.StatusCode = 401
                    $resp.Close()
                    continue
                }
            }

            # Parsear
            $j = $body | ConvertFrom-Json
            $carId    = $j.car_id
            $carUrl   = $j.car_url
            $marca    = if ($j.marca) { $j.marca } else { '' }
            $modelo   = if ($j.modelo) { $j.modelo } else { '' }
            $flujo    = if ($j.flujo) { $j.flujo } else { 'A' }
            $ts       = if ($j.ts) { $j.ts } else { (Get-Date).ToString('o') }

            if (-not (Test-Path $encargosPath)) {
                Write-Host "  AVISO: no existe $encargosPath - skip" -ForegroundColor Yellow
                $resp.StatusCode = 200
                $resp.Close()
                continue
            }

            $fecha = Get-Date -Format 'yyyy-MM-dd HH:mm'
            $marcaModelo = if ($marca -and $modelo) { "$marca $modelo" } else { 'sin modelo' }
            $linea = "### webhook-$carId - $marcaModelo - $fecha"
            $bloque = @"

$linea
- **Tipo:** AUTO-SUBIDO via webhook Laravel->Desktop (flujo $flujo)
- **Estado:** importado a Laravel
- **Entregables:** -> coche_id=$carId
- **Resultado:** $carUrl
- **Notas:** [webhook; revisar manualmente si falla]

"@
            Add-Content -Path $encargosPath -Value $bloque -Encoding UTF8
            Write-Host "  OK  car_id=$carId  flujo=$flujo  anadido a encargos.md" -ForegroundColor Green

            $resp.StatusCode = 200
            $bytes = [System.Text.Encoding]::UTF8.GetBytes('{"ok":true}')
            $resp.OutputStream.Write($bytes, 0, $bytes.Length)
        }
        catch {
            Write-Host "  ERROR  $_" -ForegroundColor Red
            try { $resp.StatusCode = 500 } catch {}
        }
        finally {
            try { $resp.Close() } catch {}
        }
    }
}
finally {
    $listener.Stop()
    $listener.Close()
}
