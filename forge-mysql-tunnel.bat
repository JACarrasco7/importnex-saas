@echo off
REM ==============================================
REM Importnex Forge - SSH Tunnel para HeidiSQL
REM ==============================================
REM Mantener esta ventana abierta mientras uses HeidiSQL
REM
REM !! ESTE ARCHIVO ESTA EN UN REPOSITORIO *PUBLICO* !!
REM NUNCA escribir aqui credenciales: password de BD, tokens ni claves.
REM Lo que se ponga aqui queda publicado en GitHub y obliga a rotarlo.
REM
REM La password de la BD NO va aqui. De donde sale:
REM   setx FORGE_DB_PASSWORD "tu-password"     (una vez por maquina)
REM Este script solo la muestra; el tunel SSH no la necesita (usa la clave).
REM ==============================================

setlocal

set SSH_KEY=C:\Users\jacar\.ssh\id_ed25519_nopass
set SSH_USER=forge
set SSH_HOST=168.144.6.105
set LOCAL_PORT=3307
set REMOTE_PORT=3306

if "%FORGE_DB_PASSWORD%"=="" (
    echo [AVISO] FORGE_DB_PASSWORD no esta definida.
    echo         El tunel funciona igual, pero HeidiSQL necesitara la password.
    echo         Definirla con:  setx FORGE_DB_PASSWORD "tu-password"
    echo.
)

echo.
echo ==============================================
echo  Importnex Forge - MySQL Tunnel
echo ==============================================
echo.
echo  Local: 127.0.0.1:%LOCAL_PORT%
echo  Remoto: %SSH_HOST%:%REMOTE_PORT%
echo  User: forge
echo  DB: forge
echo.
echo  Configuracion HeidiSQL:
echo    Host: 127.0.0.1
echo    Puerto: %LOCAL_PORT%
echo    Usuario: forge
echo    Password: ^(la de FORGE_DB_PASSWORD^)
echo.
echo ==============================================
echo.

REM Verificar si ya hay un tunel corriendo
netstat -ano | findstr ":%LOCAL_PORT%" >nul 2>&1
if %errorlevel% equ 0 (
    echo [ADVERTENCIA] Ya hay algo usando el puerto %LOCAL_PORT%.
    echo Si es otro tunel SSH, cierralo primero.
    pause
    exit /b 1
)

echo Iniciando tunel SSH...
echo (Mantener esta ventana abierta)
echo Presiona Ctrl+C para cerrar el tunel cuando termines.
echo.

ssh -i "%SSH_KEY%" -L %LOCAL_PORT%:127.0.0.1:%REMOTE_PORT% -N %SSH_USER%@%SSH_HOST%

pause
