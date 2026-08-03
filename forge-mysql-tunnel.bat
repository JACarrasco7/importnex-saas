@echo off
REM ==============================================
REM Importnex Forge - SSH Tunnel para HeidiSQL
REM ==============================================
REM Mantener esta ventana abierta mientras uses HeidiSQL
REM ==============================================

setlocal

set SSH_KEY=C:\Users\jacar\.ssh\id_ed25519_nopass
set SSH_USER=forge
set SSH_HOST=168.144.6.105
set LOCAL_PORT=3307
set REMOTE_PORT=3306

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
echo    Password: z5sAhm2QZfCOYvIel0hU
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
