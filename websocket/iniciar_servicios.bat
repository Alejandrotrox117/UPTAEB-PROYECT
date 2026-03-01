@echo off
title Lanzador de Servicios de Notificaciones
color 0b

echo =========================================================
echo       INICIANDO SERVICIOS DE NOTIFICACIONES
echo =========================================================
echo.

echo [1/2] Iniciando Redis Server en una nueva ventana...
start "Servidor Redis" cmd /k "cd /d %~dp0redis && redis-server.exe redis.windows.conf"

:: Esperar 2 segundos para dar tiempo a que Redis inicie correctamente
timeout /t 2 /nobreak >nul

echo [2/2] Iniciando Servidor WebSocket (PHP) en una nueva ventana...
start "Servidor WebSocket (PHP)" cmd /k "cd /d %~dp0 && php server.php"

echo.
echo =========================================================
echo  SERVICIOS LANZADOS CON EXITO
echo  (Puede minimizar esta ventana o cerrarla, los
echo   servicios seguiran corriendo en sus ventanas
echo   respectivas. Cierre las otras ventanas para detenerlos)
echo =========================================================
echo.
pause
exit
