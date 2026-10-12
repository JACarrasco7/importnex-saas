# Define la variable de entorno IMPORTNEX_TOKEN (scope User) pidiendo el valor
# por consola y SIN mostrarlo en pantalla ni dejarlo en el historial.
#
# Uso:   .\scripts\set-importnex-token.ps1
#
# El token NO se escribe en este archivo ni se imprime: se lee con
# Read-Host -AsSecureString y se convierte en memoria solo para guardarlo.

$ErrorActionPreference = 'Stop'

Write-Host 'Pega el token cuando se te pida y pulsa Enter.' -ForegroundColor Cyan
Write-Host '(No se vera en pantalla ni quedara en el historial de PowerShell.)' -ForegroundColor DarkGray
Write-Host ''

$seguro = Read-Host -AsSecureString 'IMPORTNEX_TOKEN'

if (-not $seguro -or $seguro.Length -eq 0) {
    Write-Host 'Cancelado: no se recibio ningun valor.' -ForegroundColor Yellow
    exit 1
}

$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($seguro)
try {
    $plano = [Runtime.InteropServices.Marshal]::PtrToStringAuto($ptr)
    [Environment]::SetEnvironmentVariable('IMPORTNEX_TOKEN', $plano, 'User')
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    Remove-Variable seguro, plano -ErrorAction SilentlyContinue
}

$actual = [Environment]::GetEnvironmentVariable('IMPORTNEX_TOKEN', 'User')
Write-Host ''
if ($actual) {
    Write-Host ("Listo: IMPORTNEX_TOKEN definida (User scope, {0} caracteres)." -f $actual.Length) -ForegroundColor Green
    Write-Host 'Las consolas YA ABIERTAS no la ven: abre una nueva para usarla con subir-informe.ps1.' -ForegroundColor DarkGray
} else {
    Write-Host 'Algo fallo: la variable no quedo definida.' -ForegroundColor Red
    exit 1
}
