@echo off
REM ================================================================
REM subir-informe.bat — Arrastra un .json aqui para subirlo
REM O ejecuta sin argumentos para subir toda la carpeta
REM ================================================================
setlocal enabledelayedexpansion

set TOKEN=22a600ba2f1f52eaa96a450dfd82bb9a36c26a28ee54f879e763583770a1fc32
set API=https://jjimportmotors.on-forge.com/api/import-valuation
set CARPETA=C:\Users\jacar\Desktop\JJImportMotors\laravel\informes

if not "%~1"=="" (
    echo Subiendo: %~nx1
    echo.
    curl -s -X POST "%API%" -H "X-Import-Token: %TOKEN%" -H "Content-Type: application/json" --data-binary "@%~1" -w "\nHTTP: %%{http_code}\n"
    echo.
    pause
    exit /b
)

echo ============================================================
echo   ImportnexCore — Subir informes
echo ============================================================
echo.

for %%f in ("%CARPETA%\*.json") do (
    echo   %%~nxf ...
    curl -s -X POST "%API%" -H "X-Import-Token: %TOKEN%" -H "Content-Type: application/json" --data-binary "@%%f" -w "  HTTP: %%{http_code}\n"
    echo.
)

echo Listo.
pause
