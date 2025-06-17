<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class NotificacionesModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;

    public function __construct()
    {
        
    }

    // Getters y Setters
    public function getQuery(){
        return $this->query;
    }

    public function setQuery(string $query){
        $this->query = $query;
    }

    public function getArray(){
        return $this->array ?? [];
    }

    public function setArray(array $array){
        $this->array = $array;
    }

    public function getData(){
        return $this->data ?? [];
    }

    public function setData(array $data){
        $this->data = $data;
    }

    public function getResult(){
        return $this->result;
    }

    public function setResult($result){
        $this->result = $result;
    }

    private function ejecutarCreacionNotificacion(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, 
                    rol_destinatario, usuario_destinatario, prioridad, 
                    fecha_creacion, leida, activa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1)"
            );
            
            $this->setArray([
                $data['tipo'],
                $data['titulo'],
                $data['mensaje'],
                $data['modulo'],
                $data['referencia_id'] ?? null,
                $data['rol_destinatario'] ?? null,
                $data['usuario_destinatario'] ?? null,
                $data['prioridad'] ?? 'MEDIA'
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $notificacionId = $db->lastInsertId();
            
            $resultado = $notificacionId > 0;
            
        } catch (Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    public function obtenerRolPorUsuario($usuarioId)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT u.idrol, r.nombre as rol_nombre, r.estatus 
                FROM usuario u 
                INNER JOIN roles r 
                ON u.idrol = r.idrol 
                WHERE u.idusuario = ?
                AND u.estatus = 'ACTIVO' 
                AND r.estatus = 'ACTIVO';"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$usuarioId]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                return $resultado['idrol'];
            } else {
                return false;
            }

        } catch (Exception $e) {
            error_log("Error al obtener rol del usuario: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaNotificacionesPorUsuario(int $usuarioId, int $rolId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT 
                    n.idnotificacion, n.tipo, n.titulo, n.mensaje, n.modulo, 
                    n.referencia_id, n.prioridad, n.fecha_creacion, n.leida,
                    DATE_FORMAT(n.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
                FROM notificaciones n 
                WHERE n.activa = 1 
                AND (n.usuario_destinatario = ? OR n.rol_destinatario = ?)
                ORDER BY n.leida ASC, n.fecha_creacion DESC
                LIMIT 20"
            );
            
            $this->setArray([$usuarioId, $rolId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener notificaciones: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener notificaciones",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarConteoNotificacionesNoLeidas(int $usuarioId, int $rolId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT COUNT(*) as total 
                FROM notificaciones 
                WHERE activa = 1 AND leida = 0 
                AND (usuario_destinatario = ? OR rol_destinatario = ?)"
            );
            
            $this->setArray([$usuarioId, $rolId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $resultado = intval($result['total'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Error al contar notificaciones: " . $e->getMessage());
            $resultado = 0;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarMarcarComoLeida(int $notificacionId, int $usuarioId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "UPDATE notificaciones 
                SET leida = 1, fecha_lectura = NOW() 
                WHERE idnotificacion = ? 
                AND (usuario_destinatario = ? OR usuario_destinatario IS NULL)"
            );
            
            $this->setArray([$notificacionId, $usuarioId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error al marcar notificación como leída: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarMarcarTodasComoLeidas(int $usuarioId, int $rolId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "UPDATE notificaciones 
                SET leida = 1, fecha_lectura = NOW() 
                WHERE leida = 0 AND activa = 1
                AND (usuario_destinatario = ? OR rol_destinatario = ?)"
            );
            
            $this->setArray([$usuarioId, $rolId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
            $resultado = 0;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarGenerarNotificacionesProductos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();
        $dbGeneral = $conexion->getDatabaseGeneral(); // Obtener nombre de BD general

        try {
            // Limpiar notificaciones anteriores de stock
            $this->setQuery("DELETE FROM notificaciones WHERE tipo IN ('STOCK_BAJO', 'SIN_STOCK') AND activa = 1");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();

            // Obtener roles que pueden ver productos (una sola vez)
            $rolQuery = "SELECT DISTINCT
                                r.idrol
                            FROM 
                                roles r
                            INNER JOIN 
                                rol_modulo_permisos rmp ON r.idrol = rmp.idrol
                            INNER JOIN 
                                modulos m ON rmp.idmodulo = m.idmodulo
                            INNER JOIN 
                                permisos p ON rmp.idpermiso = p.idpermiso
                            WHERE 
                                LOWER(m.titulo) = 'productos'
                                AND LOWER(p.nombre_permiso) = 'Acceso Total'
                                AND r.estatus = 'activo'
                                AND rmp.activo = 1";
            $stmtRol = $db->prepare($rolQuery);
            $stmtRol->execute();
            $roles = $stmtRol->fetchAll(PDO::FETCH_COLUMN);

            if (empty($roles)) {
                throw new Exception("No se encontraron roles con permisos para productos");
            }

            // Obtener productos con stock bajo (1-9)
            $this->setQuery(
                "SELECT idproducto, nombre, existencia 
                FROM {$dbGeneral}.producto 
                WHERE estatus = 'ACTIVO' AND existencia BETWEEN 1 AND 9"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $productosStockBajo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener productos sin stock (0)
            $this->setQuery(
                "SELECT idproducto, nombre, existencia 
                FROM {$dbGeneral}.producto 
                WHERE estatus = 'ACTIVO' AND existencia = 0"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $productosSinStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Preparar statement para insertar notificaciones
            $this->setQuery(
                "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, 
                    rol_destinatario, prioridad, fecha_creacion, leida, activa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1)"
            );
            $stmtInsert = $db->prepare($this->getQuery());

            // Procesar productos con stock bajo
            foreach ($productosStockBajo as $producto) {
                $titulo = "Stock Bajo - " . $producto['nombre'];
                $mensaje = "El producto '{$producto['nombre']}' tiene solo {$producto['existencia']} unidades en stock.";
                
                foreach ($roles as $rolId) {
                    $this->setArray([
                        'STOCK_BAJO',
                        $titulo,
                        $mensaje,
                        'productos',
                        $producto['idproducto'],
                        $rolId,
                        'ALTA'
                    ]);
                    
                    $stmtInsert->execute($this->getArray());
                }
            }

            // Procesar productos sin stock
            foreach ($productosSinStock as $producto) {
                $titulo = "Sin Stock - " . $producto['nombre'];
                $mensaje = "El producto '{$producto['nombre']}' no tiene existencias disponibles.";
                
                foreach ($roles as $rolId) {
                    $this->setArray([
                        'SIN_STOCK',
                        $titulo,
                        $mensaje,
                        'productos',
                        $producto['idproducto'],
                        $rolId,
                        'CRITICA'
                    ]);
                    
                    $stmtInsert->execute($this->getArray());
                }
            }

            $resultado = true;
            
        } catch (Exception $e) {
            error_log("Error al generar notificaciones de productos: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos
    public function crearNotificacion(array $data){
        return $this->ejecutarCreacionNotificacion($data);
    }

    public function obtenerNotificacionesPorUsuario(int $usuarioId, int $rolId){
        return $this->ejecutarBusquedaNotificacionesPorUsuario($usuarioId, $rolId);
    }

    public function contarNotificacionesNoLeidas(int $usuarioId, int $rolId){
        return $this->ejecutarConteoNotificacionesNoLeidas($usuarioId, $rolId);
    }

    public function marcarComoLeida(int $notificacionId, int $usuarioId){
        return $this->ejecutarMarcarComoLeida($notificacionId, $usuarioId);
    }

    public function marcarTodasComoLeidas(int $usuarioId, int $rolId){
        return $this->ejecutarMarcarTodasComoLeidas($usuarioId, $rolId);
    }

    public function generarNotificacionesProductos(){
        return $this->ejecutarGenerarNotificacionesProductos();
    }
}
?>