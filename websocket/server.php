<?php
require __DIR__ . '/vendor/autoload.php';

use App\WebSocket\NotificacionesServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use Clue\React\Redis\Factory;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🚀 SERVIDOR WEBSOCKET DE NOTIFICACIONES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$loop = Loop::get();
$notificaciones = new NotificacionesServer();

// WebSocket Server en puerto 8080
$webSock = new \React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
$webServer = new IoServer(
    new HttpServer(
        new WsServer($notificaciones)
    ),
    $webSock
);

echo "✅ WebSocket escuchando en: ws://localhost:8080\n";

// Redis Client
$factory = new Factory($loop);
$factory->createClient('127.0.0.1:6379')->then(
    function ($client) use ($notificaciones) {
        echo "✅ Conectado a Redis\n";
        echo "📡 Suscrito al canal: notificaciones\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "⏳ Esperando mensajes...\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Suscribirse al canal
        $client->subscribe('notificaciones');
        
        // Escuchar mensajes
        $client->on('message', function ($channel, $message) use ($notificaciones) {
            echo "\n📩 Mensaje recibido de Redis:\n";
            echo "   Canal: $channel\n";
            echo "   Contenido: " . substr($message, 0, 100) . "...\n";
            
            $data = json_decode($message, true);
            
            if (!$data) {
                echo "⚠️  Error: Mensaje JSON inválido\n";
                return;
            }
            
            // Enrutar según destinatarios
            if (isset($data['roles_destino'])) {
                if ($data['roles_destino'] === 'todos') {
                    $notificaciones->broadcast($message);
                } else {
                    $notificaciones->enviarPorRoles($message, $data['roles_destino']);
                }
            } elseif (isset($data['usuarios_destino'])) {
                foreach ($data['usuarios_destino'] as $uid) {
                    $notificaciones->enviarAUsuario($message, $uid);
                }
            }
            
            echo "\n";
        });
    },
    function ($error) {
        echo "❌ Error conectando a Redis: {$error}\n";
        echo "   Asegúrate de que Redis esté corriendo: sudo systemctl status redis\n";
        exit(1);
    }
);

echo "\n💡 Presiona Ctrl+C para detener el servidor\n\n";

$loop->run();
