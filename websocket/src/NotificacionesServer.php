<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificacionesServer implements MessageComponentInterface {
    
    protected $clients;
    protected $usuarios; // usuario_id => [conn, rol_id, rol_nombre]
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->usuarios = [];
        echo "🚀 Servidor de Notificaciones iniciado\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        $conn->send(json_encode([
            'tipo' => 'conexion',
            'mensaje' => '✅ Conectado al sistema de notificaciones'
        ]));
        
        echo "➕ Nueva conexión: ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "⚠️  Mensaje JSON inválido recibido\n";
            return;
        }
        
        // Cliente se autentica
        if (isset($data['tipo']) && $data['tipo'] === 'autenticar') {
            $this->usuarios[$data['usuario_id']] = [
                'conn' => $from,
                'rol_id' => $data['rol_id'],
                'rol_nombre' => $data['rol_nombre'] ?? 'Usuario'
            ];
            
            echo "✅ Usuario {$data['usuario_id']} ({$data['rol_nombre']}) autenticado\n";
            
            $from->send(json_encode([
                'tipo' => 'autenticacion',
                'status' => 'success',
                'mensaje' => 'Autenticado correctamente'
            ]));
        }
    }
    
    /**
     * Envía notificación a usuarios con roles específicos
     */
    public function enviarPorRoles($mensaje, $rolesIds) {
        $count = 0;
        foreach ($this->usuarios as $usuarioId => $info) {
            if (in_array($info['rol_id'], $rolesIds)) {
                $info['conn']->send($mensaje);
                $count++;
            }
        }
        echo "📤 Enviado a $count usuarios con roles " . implode(',', $rolesIds) . "\n";
    }
    
    /**
     * Envía a un usuario específico
     */
    public function enviarAUsuario($mensaje, $usuarioId) {
        if (isset($this->usuarios[$usuarioId])) {
            $this->usuarios[$usuarioId]['conn']->send($mensaje);
            echo "📤 Enviado a usuario $usuarioId\n";
        }
    }
    
    /**
     * Broadcast a todos
     */
    public function broadcast($mensaje) {
        foreach ($this->usuarios as $usuarioId => $info) {
            $info['conn']->send($mensaje);
        }
        echo "📢 Broadcast a " . count($this->usuarios) . " usuarios\n";
    }
    
    public function onClose(ConnectionInterface $conn) {
        foreach ($this->usuarios as $id => $info) {
            if ($info['conn'] === $conn) {
                unset($this->usuarios[$id]);
                echo "➖ Usuario $id desconectado\n";
                break;
            }
        }
        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
        $conn->close();
    }
}
