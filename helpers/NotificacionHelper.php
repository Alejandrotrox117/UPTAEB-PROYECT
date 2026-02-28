<?php


namespace App\Helpers;

use Predis\Client as PredisClient;

class NotificacionHelper {
    
    private $redis;
    private $connected = false;
    
    public function __construct() {
        try {
           
            $this->redis = new PredisClient([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'timeout' => 2.5
            ]);
            
            // Probar conexión
            $this->redis->ping();
            $this->connected = true;
            error_log(" NotificacionHelper: Conectado a Redis usando Predis");
            
        } catch (\Throwable $e) {
            error_log(" NotificacionHelper Error: " . $e->getMessage());
            $this->connected = false;
            $this->redis = null;
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
        error_log("enviarPorModulo: modulo=$modulo, tipo=$tipo");
        
        try {
            // 1. Obtener roles con permiso en el módulo
            $rolesConPermiso = $this->obtenerRolesConPermiso($modulo);
            
            if (empty($rolesConPermiso)) {
                error_log("No hay roles con permiso ver para: $modulo");
                return false;
            }
            
            error_log(" Roles con permiso 'ver' en $modulo: " . implode(', ', $rolesConPermiso));
            
            // 2. Filtrar por configuración de notificaciones (tipo específico)
            $rolesFiltrados = $this->filtrarRolesPorConfigNotificacion($rolesConPermiso, $modulo, $tipo);
            
            if (empty($rolesFiltrados)) {
                error_log("No hay roles con notificación $tipo habilitada en $modulo");
                return false;
            }
            
            error_log(" Roles con notificación habilitada: " . implode(', ', $rolesFiltrados));
            
            // 3. AGREGAR MÓDULO A LOS DATOS SI NO ESTÁ
            if (!isset($data['modulo'])) {
                $data['modulo'] = $modulo;
            }
            
            // 4. Enviar por WebSocket
            return $this->enviarPorRoles($tipo, $data, $rolesFiltrados, $prioridad);
            
        } catch (\Exception $e) {
            error_log(" Error en enviarPorModulo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener roles con permiso en un módulo
     */
    private function obtenerRolesConPermiso($modulo, $accion = null) {
        try {
            $conn = new \App\Core\Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad();
            
            // Obtener todos los roles que tienen al menos un permiso ACTIVO en el módulo especificado
            $sql = "SELECT DISTINCT rmp.idrol 
                    FROM rol_modulo_permisos rmp
                    INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                    WHERE LOWER(m.titulo) = LOWER(?)
                      AND rmp.activo = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$modulo]);
            $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            error_log(" obtenerRolesConPermiso: módulo=$modulo, roles encontrados=" . implode(',', $roles ?: []));
            
            $conn->disconnect();
            return $roles ? $roles : [];
        } catch (\Exception $e) {
            error_log("❌ Error obtenerRolesConPermiso: " . $e->getMessage());
            return [];
        }
    }
    
   
    /**
     * Obtener roles que tienen Acceso Total (idpermiso=8) en un módulo.
     * Estos son los roles autorizadores (gerentes/admins).
     */
    public function obtenerRolesConAccesoTotal($modulo) {
        try {
            $conn = new \App\Core\Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad();

            $sql = "SELECT DISTINCT rmp.idrol
                    FROM rol_modulo_permisos rmp
                    INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                    WHERE LOWER(m.titulo) = LOWER(?)
                      AND rmp.idpermiso = 8
                      AND rmp.activo = 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$modulo]);
            $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $conn->disconnect();
            error_log(" obtenerRolesConAccesoTotal: módulo=$modulo, roles=" . implode(',', $roles ?: []));
            return $roles ?: [];
        } catch (\Exception $e) {
            error_log("❌ Error obtenerRolesConAccesoTotal: " . $e->getMessage());
            return [];
        }
    }

    private function filtrarRolesPorConfigNotificacion($roles, $modulo, $tipoNotificacion) {
        if (empty($roles)) {
            return [];
        }
        
        try {
            $conn = new \App\Core\Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad();
            
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            
            // Obtener roles que tienen DESHABILITADA esta notificación específica
            $sql = "SELECT idrol 
                    FROM notificaciones_config 
                    WHERE idrol IN ($placeholders) 
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
    
   
    public function enviarPorRoles($tipo, $data, $rolesIds, $prioridad = 'MEDIA') {
        try {
           
            $modulo = $data['modulo'] ?? 'general';
            
            $idnotificacion = $this->guardarNotificacionCompletaBD(
                $tipo,
                $data['titulo'] ?? 'Notificación',
                $data['mensaje'] ?? '',
                $modulo,
                $data['referencia_id'] ?? null,
                $prioridad,
                $rolesIds,
                'rol'
            );

            if (!$idnotificacion) {
              
                return false;
            }

           
       
            if (!$this->connected) {
            
                return true; // Retornar true porque YA está guardada
            }

            $mensaje = [
                'idnotificacion' => $idnotificacion,
                'tipo' => $tipo,
                'roles_destino' => $rolesIds,
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            error_log(" Notificación enviada: $tipo a roles " . implode(',', $rolesIds) . " ($suscriptores suscriptores conectados)");
            
            return true;
            
        } catch (\Exception $e) {
            error_log(" Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }
    
   
    public function enviarAUsuario($tipo, $data, $usuarioId, $prioridad = 'MEDIA') {
        try {
            $modulo = $data['modulo'] ?? 'general';
            
            // 1. GUARDAR EN BD CON DESTINATARIO USUARIO
            $idnotificacion = $this->guardarNotificacionCompletaBD(
                $tipo,
                $data['titulo'] ?? 'Notificación Personal',
                $data['mensaje'] ?? '',
                $modulo,
                $data['referencia_id'] ?? null,
                $prioridad,
                [$usuarioId],
                'usuario'
            );

            if (!$idnotificacion) {
                error_log(" No se pudo guardar notificación de usuario en BD");
                return false;
            }

            error_log(" Notificación guardada en BD con ID: $idnotificacion para usuario: $usuarioId");

            // 2. ENVIAR POR WEBSOCKET
            if (!$this->connected) {
                error_log("Redis no conectado, pero notificación guardada en BD");
                return true;
            }

            $mensaje = [
                'idnotificacion' => $idnotificacion,
                'tipo' => $tipo,
                'usuarios_destino' => [$usuarioId],
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            error_log(" Notificación enviada: $tipo a usuario $usuarioId ($suscriptores suscriptores)");
            
            return true;
            
        } catch (\Exception $e) {
            error_log(" Error al enviar notificación a usuario: " . $e->getMessage());
            return false;
        }
    }
    
   
    public function enviarATodos($tipo, $data, $prioridad = 'MEDIA') {
        try {
            $modulo = $data['modulo'] ?? 'general';
            
            // 1. OBTENER TODOS LOS ROLES ACTIVOS
            $todosLosRoles = $this->obtenerTodosRoles();
            
            if (empty($todosLosRoles)) {
                error_log("No hay roles disponibles en el sistema");
                return false;
            }

            // 2. GUARDAR EN BD PARA TODOS LOS ROLES
            $idnotificacion = $this->guardarNotificacionCompletaBD(
                $tipo,
                $data['titulo'] ?? 'Notificación General',
                $data['mensaje'] ?? '',
                $modulo,
                $data['referencia_id'] ?? null,
                $prioridad,
                $todosLosRoles,
                'rol'
            );

            if (!$idnotificacion) {
                error_log(" No se pudo guardar notificación broadcast en BD");
                return false;
            }

            error_log(" Notificación guardada en BD con ID: $idnotificacion para todos los roles");

            // 3. ENVIAR POR WEBSOCKET
            if (!$this->connected) {
                error_log("Redis no conectado, pero notificación guardada en BD");
                return true;
            }

            $mensaje = [
                'idnotificacion' => $idnotificacion,
                'tipo' => $tipo,
                'roles_destino' => 'todos',
                'prioridad' => $prioridad,
                'timestamp' => time(),
                'fecha_formato' => date('d/m/Y H:i:s'),
                'data' => $data
            ];
            
            $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
            
            
            return true;
            
        } catch (\Exception $e) {
           
            return false;
        }
    }
    
    /**
     * Enviar notificación INFORMATIVA (solo WebSocket, no guarda en BD)
     * Se almacena en localStorage del navegador
     */
    public function enviarInformativa($tipo, $data, $modulo, $prioridad = 'MEDIA') {
        if (!$this->isConnected()) {
            error_log("Redis desconectado, no se puede enviar notificación informativa");
            return false;
        }
        
        try {
            // Obtener roles con permiso en el módulo
            $rolesConPermiso = $this->obtenerRolesConPermiso($modulo);
            
            if (empty($rolesConPermiso)) {
                error_log("No hay roles con permiso para: $modulo");
                return false;
            }
            
            // Filtrar por configuración de notificaciones
            $rolesFiltrados = $this->filtrarRolesPorConfigNotificacion($rolesConPermiso, $modulo, $tipo);
            
            if (empty($rolesFiltrados)) {
                error_log("No hay roles con notificación $tipo habilitada");
                return false;
            }
            
            // Preparar payload
            $payload = [
                'tipo' => $tipo,
                'titulo' => $data['titulo'] ?? 'Notificación',
                'mensaje' => $data['mensaje'] ?? '',
                'roles_destino' => $rolesFiltrados,
                'prioridad' => $prioridad,
                'modulo' => $modulo,
                'esInformativa' => true, // ✨ Marca como informativa para frontend
                'timestamp' => time(),
                'data' => $data
            ];
            
            // Publicar a Redis (solo WebSocket, NO BD)
            $mensaje = json_encode($payload);
            $result = $this->redis->publish('notificaciones', $mensaje);
            
            error_log(" Notificación informativa enviada: $tipo (alcance: $result suscriptores)");
            return $result > 0;
            
        } catch (\Exception $e) {
            error_log(" Error al enviar notificación informativa: " . $e->getMessage());
            return false;
        }
    }
    
   
public function enviarNotificacionStockMinimo($producto, $rolesDestino = null) {
        if (!$this->isConnected()) {
            error_log("⚠️ Redis no conectado, intentando continuar de todas formas...");
        }
        
        try {
            error_log(" enviarNotificacionStockMinimo: producto={$producto['nombre']}");
            
            // Si no se especifican roles, obtenerlos dinámicamente según permisos
            if (empty($rolesDestino)) {
                error_log(" Roles no especificados, obteniendo roles con permiso en módulo 'productos'...");
                
                // Obtener roles con permiso en el módulo productos
                $rolesConPermiso = $this->obtenerRolesConPermiso('productos');
                
                if (empty($rolesConPermiso)) {
                    
                    $rolesDestino = [1, 2]; // Fallback a admin
                } else {
                    // Filtrar por configuración de notificaciones (tipo STOCK_MINIMO)
                    $rolesDestino = $this->filtrarRolesPorConfigNotificacion($rolesConPermiso, 'productos', 'STOCK_MINIMO');
                    
                    if (empty($rolesDestino)) {
                       
                        $rolesDestino = $rolesConPermiso;
                    }
                }
                
                } else {
                }
            
            $idnotificacion = $this->guardarNotificacionCompletaBD(
                'STOCK_MINIMO',
                "Stock Mínimo - {$producto['nombre']}",
                "El producto '{$producto['nombre']}' tiene {$producto['existencia']} unidades (mínimo: {$producto['stock_minimo']})",
                'productos',
                $producto['idproducto'],
                'ALTA',
                $rolesDestino,
                'rol'
            );
            
            if (!$idnotificacion) {
                error_log("No se pudo guardar notificación de stock en BD");
                return false;
            }
            
         
            if ($this->isConnected()) {
                try {
                    $data = [
                        'titulo' => "Stock Mínimo - {$producto['nombre']}",
                        'mensaje' => "El producto '{$producto['nombre']}' tiene {$producto['existencia']} unidades (mínimo: {$producto['stock_minimo']})",
                        'producto_id' => $producto['idproducto'],
                        'producto_nombre' => $producto['nombre'],
                        'existencia' => $producto['existencia'],
                        'stock_minimo' => $producto['stock_minimo'],
                        'modulo' => 'productos'
                    ];
                    
                    $mensaje = [
                        'idnotificacion' => $idnotificacion,
                        'tipo' => 'STOCK_MINIMO',
                        'roles_destino' => $rolesDestino,
                        'prioridad' => 'ALTA',
                        'timestamp' => time(),
                        'fecha_formato' => date('d/m/Y H:i:s'),
                        'data' => $data
                    ];
                    
                    $suscriptores = $this->redis->publish('notificaciones', json_encode($mensaje));
                  
                } catch (\Exception $e) {
                    error_log("Error al publicar en WebSocket: " . $e->getMessage());
                }
            }
            
           
            return $idnotificacion;
            
        } catch (\Exception $e) {
            error_log(" Error al enviar notificación de stock: " . $e->getMessage());
            return false;
        }
    }
    
    
    
    private function guardarNotificacionCompletaBD($tipo, $titulo, $mensaje, $modulo, $referenciaId = null, $prioridad = 'MEDIA', $destinatarios = [], $tipoDestinatario = 'rol') {
        try {
           
            $conexion = new \App\Core\Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            
            // 1. OBTENER ID DEL MÓDULO POR NOMBRE
            $stmtModulo = $db->prepare("SELECT idmodulo FROM modulos WHERE LOWER(titulo) = LOWER(?) LIMIT 1");
            $stmtModulo->execute([$modulo]);
            $moduloResult = $stmtModulo->fetch(\PDO::FETCH_ASSOC);
            
            if (!$moduloResult) {
                error_log("Módulo '$modulo' no encontrado en BD. Verifica que el módulo exista en la tabla modulos.");
                $conexion->disconnect();
                return false;
            }

            $moduloId = $moduloResult['idmodulo'];
            error_log(" Módulo encontrado: ID $moduloId");
            
            // 2. INSERTAR NOTIFICACIONES EN LA TABLA UNIFICADA (una por cada destinatario)
            $primerIdNotificacion = null;
            
            if (!empty($destinatarios)) {
                $sqlNotificacion = "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, prioridad,
                    idusuario_destino, idrol_destino, leida, activa, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())";
                
                $stmtNotificacion = $db->prepare($sqlNotificacion);
                
                // Prepared statement para verificar duplicados antes de insertar
                $sqlCheck = "SELECT COUNT(*) FROM notificaciones
                             WHERE tipo = ? AND modulo = ? AND referencia_id = ?
                             AND activa = 1
                             AND (
                                 (? IS NOT NULL AND idusuario_destino = ?)
                                 OR
                                 (? IS NOT NULL AND idrol_destino = ?)
                             )";
                $stmtCheck = $db->prepare($sqlCheck);
                
                foreach ($destinatarios as $destinatario) {
                    $idusuario = ($tipoDestinatario === 'usuario') ? $destinatario : null;
                    $idrol     = ($tipoDestinatario === 'rol')     ? $destinatario : null;
                    
                    // Verificar si ya existe una notificación activa idéntica para este destinatario
                    $stmtCheck->execute([
                        $tipo, $moduloId, $referenciaId,
                        $idusuario, $idusuario,
                        $idrol,     $idrol
                    ]);
                    if ((int)$stmtCheck->fetchColumn() > 0) {
                        error_log(" Notificación duplicada omitida: tipo=$tipo, ref=$referenciaId, destinatario=$destinatario");
                        // Usar el ID de la notificación existente como referencia
                        if (!$primerIdNotificacion) {
                            $stmtExist = $db->prepare(
                                "SELECT idnotificacion FROM notificaciones
                                 WHERE tipo=? AND modulo=? AND referencia_id=? AND activa=1
                                 AND (idusuario_destino=? OR idrol_destino=?)
                                 LIMIT 1"
                            );
                            $stmtExist->execute([$tipo, $moduloId, $referenciaId, $idusuario, $idrol]);
                            $primerIdNotificacion = $stmtExist->fetchColumn() ?: null;
                        }
                        continue; // Saltar la inserción duplicada
                    }
                    
                    $result = $stmtNotificacion->execute([
                        $tipo,
                        $titulo,
                        $mensaje,
                        $moduloId,
                        $referenciaId,
                        $prioridad,
                        $idusuario,
                        $idrol
                    ]);
                    
                    if ($result) {
                        $idNotificacion = $db->lastInsertId();
                        if (!$primerIdNotificacion) {
                            $primerIdNotificacion = $idNotificacion;
                        }
                        error_log(" Notificación guardada (ID: $idNotificacion) para " . ($tipoDestinatario === 'rol' ? "rol $destinatario" : "usuario $destinatario"));
                    } else {
                        error_log(" Error al guardar notificación: " . json_encode($stmtNotificacion->errorInfo()));
                    }
                }
            } else {
                // Si no hay destinatarios especificados, guardar para todos (rol 1 = SuperAdmin)
                $sqlNotificacion = "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, prioridad,
                    idrol_destino, leida, activa, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 0, 1, NOW())";
                
                $stmtNotificacion = $db->prepare($sqlNotificacion);
                $result = $stmtNotificacion->execute([
                    $tipo,
                    $titulo,
                    $mensaje,
                    $moduloId,
                    $referenciaId,
                    $prioridad
                ]);
                
                if ($result) {
                    $primerIdNotificacion = $db->lastInsertId();
                    error_log(" Notificación guardada (ID: $primerIdNotificacion) para SuperAdmin (rol 1)");
                } else {
                    error_log(" Error al guardar notificación: " . json_encode($stmtNotificacion->errorInfo()));
                }
            }
            
            error_log(" Notificaciones guardadas, primer ID: $primerIdNotificacion");
            
            $conexion->disconnect();
            return $primerIdNotificacion ?: false;
            
        } catch (\Exception $e) {
            error_log(" Error en guardarNotificacionCompletaBD: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los roles activos del sistema
     */
    private function obtenerTodosRoles() {
        try {
            $conexion = new \App\Core\Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            
            $sql = "SELECT idrol FROM roles WHERE estatus = 'ACTIVO'";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $conexion->disconnect();
            
            return $roles ?: [];
            
        } catch (\Exception $e) {
            error_log("Error obtenerTodosRoles: " . $e->getMessage());
            return [];
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
