@echo off
REM ================================================================
REM subir-informe.bat -- Arrastra un .json encima y se sube solo.
REM Requiere $env:IMPORTNEX_TOKEN definido.
REM   Set: [Environment]::SetEnvironmentVariable("IMPORTNEX_TOKEN","<token>","User")
REM ================================================================
setlocal
if "%~1"=="" (
    echo Arrastra un .json encima para subirlo.
    pause
    exit /b 1
)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0subir-informe.ps1" -Archivo "%~1" %2 %3
endlocal
