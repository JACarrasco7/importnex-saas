@echo off
REM Script para configurar el bridge de importación de informes del chat
REM Genera un token seguro y configura el servidor para aceptar peticiones del chat

echo 🚀 Configurando Importnex Chat Bridge...

REM Generar token seguro
echo 📋 Generando token seguro...
for /f "delims=" %%i in ('openssl rand -hex 32') do set TOKEN=%%i
echo ✅ Token generado: %TOKEN%

REM Guardar en .env
echo IMPORTNEX_CHAT_IMPORT_TOKEN=%TOKEN% >> .env
echo ✅ Token guardado en .env

REM Crear estructura de carpetas
echo 📁 Creando estructura de carpetas...
if not exist "storage\app\importnex\import\JJ_Import_Motors\vehicles" mkdir "storage\app\importnex\import\JJ_Import_Motors\vehicles"
echo ✅ Carpeta creada: storage\app\importnex\import\JJ_Import_Motors\vehicles

if not exist "storage\app\importnex\import\JJ_Import_Motors\processed" mkdir "storage\app\importnex\import\JJ_Import_Motors\processed"
echo ✅ Carpeta de procesados creada

REM Guardar token en archivo de referencia
echo %TOKEN% > laravel\api_token.txt
echo ✅ Token guardado en laravel\api_token.txt

REM Limpiar caché
echo 🧹 Limpiando caché...
php artisan config:clear
php artisan cache:clear
echo ✅ Caché limpiado

echo.
echo 🎯 Bridge configurado correctamente!
echo.
echo 📍 Endpoint: https://jjimportmotors.on-forge.com/api/import-valuation
echo 🔑 Token: %TOKEN%
echo.
echo 📋 Instrucciones para el chat:
echo    curl -X POST https://jjimportmotors.on-forge.com/api/import-valuation ^
echo      -H "X-Import-Token: %TOKEN%" ^
echo      -H "Content-Type: application/json" ^
echo      --data @informe.json
echo.
echo 📚 Nota: Token guardado en laravel/api_token.txt
pause
