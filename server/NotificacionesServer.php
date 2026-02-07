<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;

class NotificacionesServer implements MessageComponentInterface
{
    protected $clients;
    protected $usuarios; // Map: userId => [connections]
    protected $ultimaRevision; // Timestamp de última revisión de notificaciones

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->usuarios = [];
        $this->ultimaRevision = time();

        echo "✅ Servidor de notificaciones iniciado\n";
        echo "🕐 " . date('Y-m-d H:i:s') . "\n";
        echo "🔄 Polling de nuevas notificaciones: cada 5 segundos\n\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Parsear query string para obtener ID de usuario
        parse_str($conn->httpRequest->getUri()->getQuery(), $query);
        $userId = $query['userId'] ?? null;

        if (!$userId) {
            echo "❌ Conexión rechazada: sin userId\n";
            $conn->close();
            return;
        }

        // Guardar conexión
        $this->clients->attach($conn);

        // Asociar conexión con usuario
        if (!isset($this->usuarios[$userId])) {
            $this->usuarios[$userId] = new \SplObjectStorage;
        }
        $this->usuarios[$userId]->attach($conn);

        $conn->userId = $userId;

        echo "✅ Usuario {$userId} conectado (ID: {$conn->resourceId}) - " . date('H:i:s') . "\n";
        echo "📊 Usuarios conectados: " . count($this->usuarios) . "\n";

        // Enviar notificaciones pendientes
        $this->enviarNotificacionesPendientes($conn, $userId);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (!$data) {
            echo "❌ Mensaje JSON inválido\n";
            return;
        }

        echo "📩 Mensaje recibido: " . ($data['action'] ?? 'sin acción') . " - " . date('H:i:s') . "\n";

        switch ($data['action'] ?? '') {
            case 'marcar_leida':
                $this->marcarNotificacionLeida($from, $data['notificacionId'] ?? 0);
                break;

            case 'ping':
                $from->send(json_encode(['action' => 'pong', 'timestamp' => time()]));
                break;

            default:
                echo "⚠️ Acción desconocida: " . ($data['action'] ?? 'ninguna') . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        // Remover de la lista de usuarios
        if (isset($conn->userId) && isset($this->usuarios[$conn->userId])) {
            $this->usuarios[$conn->userId]->detach($conn);

            // Si no quedan conexiones para este usuario, eliminar
            if ($this->usuarios[$conn->userId]->count() === 0) {
                unset($this->usuarios[$conn->userId]);
            }
        }

        echo "🔌 Conexión cerrada (ID: {$conn->resourceId}) - " . date('H:i:s') . "\n";
        echo "📊 Usuarios conectados: " . count($this->usuarios) . "\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "❌ Error: {$e->getMessage()}\n";
        $conn->close();
    }

    // Métodos auxiliares

    private function enviarNotificacionesPendientes($conn, $userId)
    {
        try {
            $model = new NotificacionesModel();

            // Obtener rol del usuario
            $rolId = $model->obtenerRolPorUsuario($userId);
            if (!$rolId) {
                echo "⚠️ No se pudo obtener rol para usuario {$userId}\n";
                return;
            }

            // Obtener notificaciones
            $resultado = $model->obtenerNotificacionesPorUsuario($userId, $rolId);

            if ($resultado['status'] && !empty($resultado['data'])) {
                $conn->send(json_encode([
                    'action' => 'notificaciones_iniciales',
                    'data' => $resultado['data']
                ]));

                echo "📬 Enviadas " . count($resultado['data']) . " notificaciones a usuario {$userId}\n";
            } else {
                echo "📭 Sin notificaciones pendientes para usuario {$userId}\n";
            }

        } catch (\Exception $e) {
            echo "❌ Error al enviar notificaciones: {$e->getMessage()}\n";
        }
    }

    private function marcarNotificacionLeida($conn, $notificacionId)
    {
        try {
            if (!$notificacionId) {
                echo "⚠️ ID de notificación inválido\n";
                return;
            }

            $model = new NotificacionesModel();
            $resultado = $model->marcarComoLeida($notificacionId, $conn->userId);

            if ($resultado) {
                $conn->send(json_encode([
                    'action' => 'notificacion_marcada',
                    'notificacionId' => $notificacionId
                ]));

                echo "✅ Notificación {$notificacionId} marcada como leída por usuario {$conn->userId}\n";
            } else {
                echo "❌ No se pudo marcar notificación {$notificacionId}\n";
            }

        } catch (\Exception $e) {
            echo "❌ Error al marcar notificación: {$e->getMessage()}\n";
        }
    }

    /**
     * Verificar nuevas notificaciones y enviarlas a usuarios conectados
     * Este método se ejecuta periódicamente cada 5 segundos
     */
    public function checkNuevasNotificaciones()
    {
        try {
            // Solo revisar si hay usuarios conectados
            if (count($this->usuarios) === 0) {
                return;
            }

            $ahora = time();
            $model = new NotificacionesModel();

            // Buscar notificaciones creadas desde la última revisión
            foreach ($this->usuarios as $userId => $connections) {
                try {
                    // Obtener rol del usuario
                    $rolId = $model->obtenerRolPorUsuario($userId);
                    if (!$rolId) {
                        continue;
                    }

                    // Obtener notificaciones para este usuario
                    $resultado = $model->obtenerNotificacionesPorUsuario($userId, $rolId);

                    if ($resultado['status'] && !empty($resultado['data'])) {
                        // Filtrar solo las notificaciones creadas desde la última revisión
                        $notificacionesNuevas = array_filter($resultado['data'], function ($notif) {
                            $fechaCreacion = strtotime($notif['fecha_creacion']);
                            return $fechaCreacion > $this->ultimaRevision;
                        });

                        // Enviar cada notificación nueva
                        foreach ($notificacionesNuevas as $notif) {
                            foreach ($connections as $conn) {
                                $conn->send(json_encode([
                                    'action' => 'nueva_notificacion',
                                    'data' => $notif
                                ]));
                            }

                            echo "🆕 Notificación nueva enviada a usuario {$userId}: {$notif['titulo']}\n";
                        }
                    }

                } catch (\Exception $e) {
                    error_log("Error al verificar notificaciones para usuario {$userId}: " . $e->getMessage());
                }
            }

            // Actualizar timestamp de última revisión
            $this->ultimaRevision = $ahora;

        } catch (\Exception $e) {
            error_log("Error en checkNuevasNotificaciones: " . $e->getMessage());
        }
    }

    // Método público para enviar notificación a usuarios específicos por rol
    public function enviarNotificacionPorRol($rolId, $notificacion)
    {
        $enviadas = 0;

        foreach ($this->usuarios as $userId => $connections) {
            try {
                $model = new NotificacionesModel();
                $userRolId = $model->obtenerRolPorUsuario($userId);

                if ($userRolId == $rolId) {
                    foreach ($connections as $conn) {
                        $conn->send(json_encode([
                            'action' => 'nueva_notificacion',
                            'data' => $notificacion
                        ]));
                        $enviadas++;
                    }
                }
            } catch (\Exception $e) {
                echo "❌ Error al verificar rol de usuario {$userId}: {$e->getMessage()}\n";
            }
        }

        echo "📤 Notificación enviada a {$enviadas} conexiones del rol {$rolId}\n";
        return $enviadas;
    }

    // Método para enviar notificación a usuarios específicos
    public function enviarNotificacionAUsuarios($userIds, $notificacion)
    {
        $enviadas = 0;

        foreach ($userIds as $userId) {
            if (isset($this->usuarios[$userId])) {
                foreach ($this->usuarios[$userId] as $conn) {
                    $conn->send(json_encode([
                        'action' => 'nueva_notificacion',
                        'data' => $notificacion
                    ]));
                    $enviadas++;
                }
            }
        }

        echo "📤 Notificación enviada a {$enviadas} conexiones\n";
        return $enviadas;
    }
}

// Iniciar servidor
echo "\n";
echo "═══════════════════════════════════════════════\n";
echo "  🚀 SERVIDOR WEBSOCKET DE NOTIFICACIONES\n";
echo "═══════════════════════════════════════════════\n";
echo "\n";

// Crear instancia del servidor
$notificationServer = new NotificacionesServer();

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            $notificationServer
        )
    ),
    8080,
    '0.0.0.0' // Escuchar en todas las interfaces
);

echo "🌐 Servidor corriendo en: ws://localhost:8080\n";
echo "👀 Esperando conexiones...\n";
echo "💡 Presiona Ctrl+C para detener\n\n";

// Configurar polling de nuevas notificaciones cada 5 segundos
$loop = $server->loop;
$loop->addPeriodicTimer(5, function () use ($notificationServer) {
    $notificationServer->checkNuevasNotificaciones();
});

$server->run();
