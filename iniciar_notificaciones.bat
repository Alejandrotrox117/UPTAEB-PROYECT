@echo off
title Servidor de Notificaciones WebSocket
color 0A

echo ===================================================
echo     INICIANDO SERVIDOR DE NOTIFICACIONES
echo ===================================================
echo.
echo Iniciando Redis en segundo plano...
start "Redis Server" /MIN "c:\xampp\htdocs\project\websocket\redis\redis-server.exe" "c:\xampp\htdocs\project\websocket\redis\redis.windows.conf"
ping 127.0.0.1 -n 3 > nul

cd c:\xampp\htdocs\project\websocket
php server.php

pause
