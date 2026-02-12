<?php
/**
 * Helper para Notificaciones en Tiempo Real
 * Versión: 1.0 - Básica para pruebas
 * 
 * Este helper centraliza el envío de notificaciones vía Redis Pub/Sub
 */

namespace App\Helpers;

class NotificacionHelper {
    
    private $redis;
    private $connected = false;
    
    public function __construct() {
        try {
            $this->redis = new \Redis();
            $this->connected = $this->redis->connect('127.0.0.1', 6379, 2.5); // timeout 2.5s
            
            if (!$this->connected) {
                error_log("⚠️ NotificacionHelper: No se pudo conectar a Redis");
            }
        } catch (\Exception $e) {
            error_log("❌ NotificacionHelper Error: " . $e->getMessage());
            $this->connected = false;
        }
    }
    
    /**
     * Verifica si hay conexión a Redis
     */
    public function isConnected() {
        return $this->connected;
    }
    
    /**
     * Enviar notificación por módulo (con filtrado por permisos y configuración)
     */
    public function enviarPorModulo($modulo, $tipo, $data, $prioridad = 'MEDIA') {
        // 1. Obtener roles con permiso en el módulo
        $rolesConPermiso = $this->obtenerRolesConPermiso($modulo, 'ver');
        
        if (empty($rolesConPermiso)) {
            error_log("No hay roles con permiso ver para: $modulo");
            return false;
        }
        
        // 2. Filtrar por configuración de notificaciones (tipo específico)
        $rolesFiltrados = $this->filtrarRolesPorConfigNotificacion($rolesConPermiso, $modulo, $tipo);
        
        if (empty($rolesFiltrados)) {
            error_log("No hay roles con notificación $tipo habilitada en $modulo");
            return false;
        }
        
        // 3. Enviar por WebSocket
        return $this->enviarPorRoles($tipo, $data, $rolesFiltrados, $prioridad);
    }
    
    /**
     * Obtener roles con permiso en un módulo
     */
    private function obtenerRolesConPermiso($modulo, $accion) {
        try {
            $conn = new \App\Core\Conexion();
            $conn->connect();
            $db = $conn->get_conectGeneral();
            
            $sql = "SELECT DISTINCT rp.idrol 
                    FROM rol_permiso rp
                    INNER JOIN permiso p ON rp.idpermiso = p.idpermiso
                    INNER JOIN modulo m ON p.idmodulo = m.idmodulo
                    WHERE m.url = ? AND p.accion = ? AND p.estatus = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$modulo, $accion]);
            $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $conn->disconnect();
            return $roles ? $roles : [];
        } catch (\Exception $e) {
            error_log("Error obtenerRolesConPermiso: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Filtrar roles por configuración de tipo de notificación
     */
    private function filtrarRolesPorConfigNotificacion($roles, $modulo, $tipoNotificacion) {
        if (empty($roles)) {
            return [];
        }
        
        try {
            $conn = new \App\Core\Conexion();
            $conn->connect();
            $db = $conn->get_conectGeneral();
            
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            
            // Obtener roles que tienen DESHABILITADA esta notificación específica
            $sql = "SELECT rol_id 
                    FROM notificaciones_config 
                    WHERE rol_id IN ($placeholders) 
                      AND modulo = ? 
                      AND tipo_notificacion = ?
                      AND habilitada = 0";
            
            $params = array_merge($roles, [$modulo, $tipoNotificacion]);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rolesDeshabilitados = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $conn->disconnect();
            
            // Filtrar roles deshabilitados
            $rolesFiltrados = array_diff($roles, $rolesDeshabilitados);
            
            return array_values($rolesFiltrados);
            
        } catch (\Exception $e) {
            error_log("Error filtrarRolesPorConfigNotificacion: " . $e->getMessage());
            // En caso de error, enviar a todos con permiso
            return $roles;
        }
    }
    
    /**
     * Envía notificación a roles específicos
     * 
     * @param string $tipo Tipo de notificación (ej: 'STOCK_BAJO', 'COMPRA_POR_AUTORIZAR')
     * @param array $data Datos de la notificación (titulo, mensaje, icono, etc)
     * @param array $rolesIds IDs de roles destinatarios [1, 2, 3]
     * @param string $prioridad 'BAJA'|'MEDIA'|'ALTA'|'CRITICA'
     * @return bool True si se envió correctamente
     */
    public function enviarPorRoles($tipo, $data, $rolesIds, $prioridad = 'MEDIA') {
        if (!$this->connected) {
            error_log("⚠️ NotificacionHelper: Redis no conectado, no se envió notificación");
            return false;
        }
        
        try {
            $mensaje = [
                'tipo' => $tipo,
                'roles_destino' => $rolesIds,
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            // Publicar en canal de notificaciones
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            error_log("📤 Notificación enviada: $tipo a roles " . implode(',', $rolesIds) . " ($suscriptores suscriptores)");
            
            return true;
            
        } catch (\Exception $e) {
            error_log("❌ Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía notificación a un usuario específico
     * 
     * @param string $tipo Tipo de notificación
     * @param array $data Datos de la notificación
     * @param int $usuarioId ID del usuario destinatario
     * @param string $prioridad Nivel de prioridad
     * @return bool True si se envió correctamente
     */
    public function enviarAUsuario($tipo, $data, $usuarioId, $prioridad = 'MEDIA') {
        if (!$this->connected) {
            error_log("⚠️ NotificacionHelper: Redis no conectado, no se envió notificación");
            return false;
        }
        
        try {
            $mensaje = [
                'tipo' => $tipo,
                'usuarios_destino' => [$usuarioId],
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            error_log("📤 Notificación enviada: $tipo a usuario $usuarioId ($suscriptores suscriptores)");
            
            return true;
            
        } catch (\Exception $e) {
            error_log("❌ Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía notificación broadcast a todos los usuarios conectados
     * 
     * @param string $tipo Tipo de notificación
     * @param array $data Datos de la notificación
     * @param string $prioridad Nivel de prioridad
     * @return bool True si se envió correctamente
     */
    public function enviarATodos($tipo, $data, $prioridad = 'MEDIA') {
        if (!$this->connected) {
            error_log("⚠️ NotificacionHelper: Redis no conectado, no se envió notificación");
            return false;
        }
        
        try {
            $mensaje = [
                'tipo' => $tipo,
                'roles_destino' => 'todos',
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            error_log("📢 Broadcast enviado: $tipo ($suscriptores suscriptores)");
            
            return true;
            
        } catch (\Exception $e) {
            error_log("❌ Error al enviar broadcast: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cierra la conexión a Redis
     */
    public function __destruct() {
        if ($this->connected && $this->redis) {
            try {
                $this->redis->close();
            } catch (\Exception $e) {
                // Ignorar errores al cerrar
            }
        }
    }
}
