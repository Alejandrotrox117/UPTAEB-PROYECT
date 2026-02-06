#!/usr/bin/env php
<?php
/**
 * Script para iniciar el servidor de notificaciones WebSocket
 * Ejecutar desde la raíz del proyecto: php server/start-websocket.php
 */

// Cargar autoloader primero (incluye EnvLoader)
require_once __DIR__ . '/../vendor/autoload.php';

// Ahora cargar configuración (necesita env() function del autoload)
require_once __DIR__ . '/../config/config.php';

// Cargar el servidor
require_once __DIR__ . '/NotificacionesServer.php';

echo "🚀 Iniciando servidor de notificaciones WebSocket...\n";
echo "📡 Escuchando en: ws://localhost:8080\n";
echo "⏸️  Presiona Ctrl+C para detener el servidor\n\n";

// Iniciar el servidor
\App\Server\NotificacionesServer::start();
