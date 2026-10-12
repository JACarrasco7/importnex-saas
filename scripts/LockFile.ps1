#requires -Version 5.1
<#
.SYNOPSIS
    Funciones helper para escrituras atómicas en archivos compartidos.
.DESCRIPTION
    PowerShell 5.1 Add-Content NO es atómico bajo concurrencia (ver
    auditoría ronda 4, sep-2026). Si dos procesos (subir-informe.ps1 y
    import-notify-receiver.ps1) añaden a `encargos.md` a la vez, P2
    sobrescribe la entrada de P1. Esta función usa un lockfile
    ad-hoc con reintentos para serializar las escrituras.

    Implementación: `File::Open(CreateNew, None)` falla si el lock
    ya existe -> esperamos 50-250ms y reintentamos hasta 10 veces.
    Si tras 10 reintentos no conseguimos lock, dejamos pasar (fail-open)
    para no bloquear indefinidamente (mejor entrada duplicada que
    ninguna si el sistema de archivos está roto).
#>

function Add-ContentLocked {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)] [string] $Path,
        [Parameter(Mandatory = $true)] [string] $Value,
        [int] $Retries = 10
    )

    $lock = "$Path.lock"
    $acquired = $false

    for ($i = 0; $i -lt $Retries; $i++) {
        try {
            $fs = [System.IO.File]::Open($lock, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::None)
            $fs.Close()
            $acquired = $true
            break
        } catch {
            # Lock ya existe -> esperar 50-250ms y reintentar
            Start-Sleep -Milliseconds (Get-Random -Minimum 50 -Maximum 250)
        }
    }

    try {
        if (-not (Test-Path $Path)) {
            # Crear sin BOM
            [System.IO.File]::WriteAllText($Path, $Value, (New-Object System.Text.UTF8Encoding $false))
        } else {
            Add-Content -Path $Path -Value $Value -Encoding UTF8
        }
    } finally {
        if ($acquired) {
            Remove-Item $lock -Force -ErrorAction SilentlyContinue
        }
    }
}

Export-ModuleMember -Function Add-ContentLocked
