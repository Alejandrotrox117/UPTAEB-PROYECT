<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use App\Helpers\NotificacionHelper;
use PDO;
use PDOException;
use Exception;

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
        try {
            $notifHelper = new NotificacionHelper();
            
            // Determinar destinatarios
            $destinatarios = [];
            
            // Si hay permisoId, obtener los roles que tienen ese permiso
            if (isset($data['permiso_id']) && $data['permiso_id']) {
                // Obtener roles con este permiso específico
                $conexion = new Conexion();
                $conexion->connect();
                $db = $conexion->get_conectSeguridad();
                
                $sql = "SELECT DISTINCT rmp.idrol FROM rol_modulo_permisos rmp 
                        WHERE rmp.idpermiso = ? AND rmp.activo = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$data['permiso_id']]);
                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $destinatarios = !empty($roles) ? $roles : [1]; // Fallback a admin
                $conexion->disconnect();
            } else {
                // Si no hay permiso específico, enviar a todos los roles
                $conexion = new Conexion();
                $conexion->connect();
                $db = $conexion->get_conectSeguridad();
                
                $sql = "SELECT idrol FROM roles WHERE estatus = 'ACTIVO'";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $destinatarios = !empty($roles) ? $roles : [1];
                $conexion->disconnect();
            }
            
            // Llamar al NotificacionHelper para guardar y enviar
            $resultado = $notifHelper->enviarPorRoles(
                $data['tipo'],
                [
                    'titulo' => $data['titulo'],
                    'mensaje' => $data['mensaje'],
                    'modulo' => $data['modulo'],
                    'referencia_id' => $data['referencia_id'] ?? null,
                    'icono' => $data['icono'] ?? null
                ],
                $destinatarios,
                $data['prioridad'] ?? 'MEDIA'
            );
            
            error_log("Notificación creada usando NotificacionHelper: " . $data['tipo']);
            return $resultado;
            
        } catch (Exception $e) {
            error_log("❌ Error al crear notificación: " . $e->getMessage());
            return false;
        }
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
                AND u.estatus = 'activo' 
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
        $resultado = [
            "status" => false,
            "message" => "No se procesó la solicitud",
            "data" => []
        ];
        
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // Obtener notificaciones dirigidas al usuario O a su rol (DISTINCT evita duplicados)
            $query = "
                SELECT DISTINCT
                    idnotificacion,
                    tipo,
                    titulo,
                    mensaje,
                    modulo,
                    referencia_id,
                    prioridad,
                    fecha_creacion,
                    leida,
                    fecha_lectura,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
                FROM notificaciones
                WHERE (idusuario_destino = ? OR idrol_destino = ?)
                  AND activa = 1
                ORDER BY leida ASC, fecha_creacion DESC, idnotificacion DESC
                LIMIT 50
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$usuarioId, $rolId]);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resultado = [
                "status" => true,
                "data" => $datos
            ];
            
            error_log("ejecutarBusquedaNotificacionesPorUsuario: Obtuvimos " . count($datos) . " notificaciones");
            
        } catch (\Exception $e) {
            error_log("❌ Error en ejecutarBusquedaNotificacionesPorUsuario: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener notificaciones: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            if (isset($conexion)) {
                $conexion->disconnect();
            }
        }

        return $resultado;
    }

    private function ejecutarConteoNotificacionesNoLeidas(int $usuarioId, int $rolId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Verificar si es SuperAdmin
            $this->setQuery("SELECT nombre FROM roles WHERE idrol = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$rolId]);
            $rolInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $esSuperAdmin = ($rolInfo && (strtolower($rolInfo['nombre']) === 'superadmin' || $rolInfo['nombre'] === 'SuperAdmin'));
            
            if ($esSuperAdmin) {
                // SuperAdmin ve todas las notificaciones
                $this->setQuery(
                    "SELECT COUNT(*) as total 
                    FROM notificaciones 
                    WHERE activa = 1 AND leida = 0"
                );
                $this->setArray([]);
            } else {
                // Para otros roles, usar lógica de permisos
                $this->setQuery(
                    "SELECT COUNT(DISTINCT n.idnotificacion) as total 
                    FROM notificaciones n
                    WHERE n.activa = 1 AND n.leida = 0 
                    AND (
                        -- Caso 1: Notificaciones sin permiso específico (para todos)
                        n.permiso IS NULL
                        OR
                        -- Caso 2: El usuario tiene acceso al módulo de la notificación
                        EXISTS (
                            SELECT 1 FROM rol_modulo_permisos rmp 
                            WHERE rmp.idrol = ? 
                            AND rmp.idmodulo = n.modulo 
                            AND rmp.activo = 1
                            AND (
                                rmp.idpermiso = n.permiso  -- Permiso específico
                                OR
                                rmp.idpermiso IN (  -- Acceso Total
                                    SELECT p2.idpermiso FROM permisos p2 
                                    WHERE p2.nombre_permiso = 'Acceso Total'
                                )
                            )
                        )
                    )"
                );
                $this->setArray([$rolId]);
            }
            
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
            $this->setQuery("
                UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
                WHERE idnotificacion = ?
                AND (
                    idusuario_destino = ?
                    OR idrol_destino = (
                        SELECT idrol FROM usuario WHERE idusuario = ? LIMIT 1
                    )
                )
            ");
            
            $this->setArray([$notificacionId, $usuarioId, $usuarioId]);
            
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute($this->getArray());
            
            error_log("Notificación marcada como leída: " . ($resultado ? 'OK' : 'FALLO'));
            
        } catch (Exception $e) {
            error_log("❌ Error al marcar notificación como leída: " . $e->getMessage());
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
            $this->setQuery("
                UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
                WHERE leida = 0
                AND (idusuario_destino = ? OR idrol_destino = ?)
            ");
            
            $this->setArray([$usuarioId, $rolId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount();
            
            error_log("Marcadas $resultado notificaciones como leídas");
            
        } catch (Exception $e) {
            error_log("❌ Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
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
        $dbGeneral = $conexion->getDatabaseGeneral(); 

        try {
            // Limpiar notificaciones anteriores de stock
            $this->setQuery("DELETE FROM notificaciones WHERE tipo IN ('STOCK_BAJO', 'SIN_STOCK') AND activa = 1");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();

            // Obtener ID del módulo productos
            $moduloProductosId = $this->obtenerIdModulo('productos');
            if (!$moduloProductosId) {
                throw new Exception("Módulo 'productos' no encontrado");
            }

            // Obtener permisos que están REALMENTE asignados al módulo productos
            // Solo permisos de lectura para alertas de stock
            $permisosAsignados = $this->ejecutarObtenerUsuariosConPermiso('productos', 'ver');
            
            if (empty($permisosAsignados)) {
                throw new Exception("No se encontraron permisos asignados para notificaciones de productos");
            }

            // Obtener productos con stock bajo o igual al stock_minimo (configurado por producto)
            $this->setQuery(
                "SELECT idproducto, nombre, existencia, stock_minimo 
                FROM {$dbGeneral}.producto 
                WHERE estatus = 'ACTIVO' 
                AND stock_minimo > 0
                AND existencia > 0 
                AND existencia <= stock_minimo"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $productosStockBajo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener productos sin stock (0) que tengan stock_minimo configurado
            $this->setQuery(
                "SELECT idproducto, nombre, existencia, stock_minimo 
                FROM {$dbGeneral}.producto 
                WHERE estatus = 'ACTIVO' 
                AND stock_minimo > 0
                AND existencia = 0"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $productosSinStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Preparar query de inserción
            $this->setQuery(
                "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, 
                    permiso, prioridad, fecha_creacion, leida, activa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1)"
            );
            $stmtInsert = $db->prepare($this->getQuery());

            // Procesar productos con stock bajo - UNA notificación por permiso asignado
            foreach ($productosStockBajo as $producto) {
                $titulo = "⚠️ Stock Mínimo - " . $producto['nombre'];
                $mensaje = "El producto '{$producto['nombre']}' tiene {$producto['existencia']} unidades (mínimo: {$producto['stock_minimo']}).";
                
                foreach ($permisosAsignados as $permiso) {
                    // Verificar si ya existe una notificación para evitar duplicados
                    if (!$this->ejecutarVerificarNotificacionExistente(
                        'STOCK_BAJO', 
                        'productos', 
                        $producto['idproducto'], 
                        $permiso['idpermiso']
                    )) {
                        $this->setArray([
                            'STOCK_BAJO',
                            $titulo,
                            $mensaje,
                            $moduloProductosId,
                            $producto['idproducto'],
                            $permiso['idpermiso'],
                            'ALTA'
                        ]);
                        
                        $stmtInsert->execute($this->getArray());
                    }
                }
            }

            // Procesar productos sin stock - UNA notificación por permiso asignado
            foreach ($productosSinStock as $producto) {
                $titulo = "Sin Stock - " . $producto['nombre'];
                $mensaje = "El producto '{$producto['nombre']}' no tiene existencias disponibles.";
                
                foreach ($permisosAsignados as $permiso) {
                    // Verificar si ya existe una notificación para evitar duplicados
                    if (!$this->ejecutarVerificarNotificacionExistente(
                        'SIN_STOCK', 
                        'productos', 
                        $producto['idproducto'], 
                        $permiso['idpermiso']
                    )) {
                        $this->setArray([
                            'SIN_STOCK',
                            $titulo,
                            $mensaje,
                            $moduloProductosId,
                            $producto['idproducto'],
                            $permiso['idpermiso'],
                            'CRITICA'
                        ]);
                        
                        $stmtInsert->execute($this->getArray());
                    }
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

    private function ejecutarVerificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo
            $moduloId = $this->obtenerIdModulo($modulo);
            if (!$moduloId) {
                return false;
            }

            if ($permisoId) {
                $this->setQuery(
                    "SELECT COUNT(*) as total FROM notificaciones 
                    WHERE tipo = ? AND modulo = ? AND referencia_id = ? 
                    AND permiso = ? AND activa = 1"
                );
                $this->setArray([$tipo, $moduloId, $referenciaId, $permisoId]);
            } else {
                $this->setQuery(
                    "SELECT COUNT(*) as total FROM notificaciones 
                    WHERE tipo = ? AND modulo = ? AND referencia_id = ? AND activa = 1"
                );
                $this->setArray([$tipo, $moduloId, $referenciaId]);
            }
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $resultado = $result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("Error al verificar notificación existente: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo
            $moduloId = $this->obtenerIdModulo($modulo);
            if (!$moduloId) {
                return 0;
            }

            $this->setQuery(
                "UPDATE notificaciones 
                SET activa = 0 
                WHERE tipo = ? AND modulo = ? AND referencia_id = ? AND activa = 1"
            );
            
            $this->setArray([$tipo, $moduloId, $referenciaId]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Error al eliminar notificaciones por referencia: " . $e->getMessage());
            $resultado = 0;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarObtenerUsuariosConPermiso($modulo, $accion){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo
            $moduloId = $this->obtenerIdModulo($modulo);
            if (!$moduloId) {
                return [];
            }

            // Mapear acciones a permisos específicos
            $permisoMap = [
                'eliminar' => ['Acceso Total', 'Solo Eliminar', 'Editar y Eliminar', 'Registrar y Eliminar'],
                'crear' => ['Acceso Total', 'Solo Registrar', 'Registrar y Editar', 'Registrar y Eliminar'],
                'editar' => ['Acceso Total', 'Solo Editar', 'Registrar y Editar', 'Editar y Eliminar'],
                'ver' => ['Acceso Total', 'Solo Lectura']
            ];
            
            $permisosValidos = $permisoMap[$accion] ?? ['Acceso Total'];
            $placeholders = str_repeat('?,', count($permisosValidos) - 1) . '?';
            
            // IMPORTANTE: Solo obtener permisos que están REALMENTE asignados al módulo
            $this->setQuery(
                "SELECT DISTINCT p.idpermiso, p.nombre_permiso
                FROM permisos p
                INNER JOIN rol_modulo_permisos rmp ON p.idpermiso = rmp.idpermiso
                WHERE rmp.idmodulo = ?
                AND p.nombre_permiso IN ($placeholders)
                AND rmp.activo = 1
                ORDER BY p.idpermiso"
            );
            
            $params = array_merge([$moduloId], $permisosValidos);
            $this->setArray($params);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            $resultado = [];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos privados
    private function obtenerIdModulo($nombreModulo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT idmodulo FROM modulos WHERE LOWER(titulo) = LOWER(?) AND estatus = 'activo'"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$nombreModulo]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado ? $resultado['idmodulo'] : null;

        } catch (Exception $e) {
            error_log("Error al obtener ID del módulo: " . $e->getMessage());
            return null;
        } finally {
            $conexion->disconnect();
        }
    }

    // Métodos públicos
    public function crearNotificacion(array $data){
        return $this->ejecutarCreacionNotificacion($data);
    }

    public function verificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId = null){
        return $this->ejecutarVerificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId);
    }

    public function eliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId){
        return $this->ejecutarEliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId);
    }

    public function obtenerPermisosParaAccion($modulo, $accion){
        return $this->ejecutarObtenerUsuariosConPermiso($modulo, $accion);
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

    public function limpiarNotificacionesCompra($idCompra, $accion)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo compras
            $moduloComprasId = $this->obtenerIdModulo('compras');
            if (!$moduloComprasId) {
                throw new Exception("Módulo 'compras' no encontrado");
            }

            switch ($accion) {
                case 'autorizar':
                    // Eliminar notificaciones de autorización pendientes
                    $this->setQuery(
                        "DELETE FROM notificaciones 
                         WHERE referencia_id = ? 
                         AND tipo = 'COMPRA_POR_AUTORIZAR'
                         AND modulo = ?"
                    );
                    $stmt = $db->prepare($this->getQuery());
                    $resultado = $stmt->execute([$idCompra, $moduloComprasId]);
                    
                    if ($resultado && $stmt->rowCount() > 0) {
                        error_log("Eliminadas {$stmt->rowCount()} notificaciones de autorización de compra ID: {$idCompra}");
                    }
                    break;
                    
                case 'pagar':
                    // Eliminar notificaciones de pago pendientes
                    $this->setQuery(
                        "DELETE FROM notificaciones 
                         WHERE referencia_id = ? 
                         AND tipo = 'COMPRA_AUTORIZADA_PAGO'
                         AND modulo = ?"
                    );
                    $stmt = $db->prepare($this->getQuery());
                    $resultado = $stmt->execute([$idCompra, $moduloComprasId]);
                    
                    if ($resultado && $stmt->rowCount() > 0) {
                        error_log("Eliminadas {$stmt->rowCount()} notificaciones de pago de compra ID: {$idCompra}");
                    }
                    break;
                    
                case 'completar':
                    // Eliminar todas las notificaciones de esta compra (más robusta)
                    $this->setQuery(
                        "DELETE FROM notificaciones 
                         WHERE referencia_id = ?
                         AND modulo = ?
                         AND tipo IN ('COMPRA_POR_AUTORIZAR', 'COMPRA_AUTORIZADA_PAGO')"
                    );
                    $stmt = $db->prepare($this->getQuery());
                    $resultado = $stmt->execute([$idCompra, $moduloComprasId]);
                    
                    // Log para verificar que se eliminaron las notificaciones
                    if ($resultado && $stmt->rowCount() > 0) {
                        error_log("Se eliminaron {$stmt->rowCount()} notificaciones de la compra ID: {$idCompra}");
                    }
                    break;
                    
                default:
                    $resultado = false;
            }
            
        } catch (Exception $e) {
            error_log("Error al limpiar notificaciones de compra: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }
        
        return $resultado;
    }

    public function limpiarNotificacionesProcesadas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo compras
            $moduloComprasId = $this->obtenerIdModulo('compras');
            if (!$moduloComprasId) {
                throw new Exception("Módulo 'compras' no encontrado");
            }

            // Limpiar notificaciones de compras ya completadas
            $dbGeneral = $conexion->getDatabaseGeneral();
            $this->setQuery(
                "DELETE n FROM notificaciones n
                 INNER JOIN {$dbGeneral}.compras c ON n.referencia_id = c.idcompra
                 WHERE n.tipo IN ('COMPRA_POR_AUTORIZAR', 'COMPRA_AUTORIZADA_PAGO')
                 AND n.modulo = ?
                 AND c.estatus IN ('AUTORIZADA', 'PAGADA')"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute([$moduloComprasId]);
            
        } catch (Exception $e) {
            error_log("Error al limpiar notificaciones procesadas: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }
        
        return $resultado;
    }

    public function verificarNotificacionesCompra($idCompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo compras
            $moduloComprasId = $this->obtenerIdModulo('compras');
            if (!$moduloComprasId) {
                throw new Exception("Módulo 'compras' no encontrado");
            }

            $this->setQuery(
                "SELECT COUNT(*) as total, tipo
                 FROM notificaciones 
                 WHERE referencia_id = ? 
                 AND modulo = ? 
                 AND activa = 1
                 GROUP BY tipo"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idCompra, $moduloComprasId]);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error al verificar notificaciones de compra: " . $e->getMessage());
            $resultado = [];
        } finally {
            $conexion->disconnect();
        }
        
        return $resultado;
    }

    public function limpiarNotificacionesCompraPagada($idCompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo compras
            $moduloComprasId = $this->obtenerIdModulo('compras');
            if (!$moduloComprasId) {
                throw new Exception("Módulo 'compras' no encontrado");
            }

            // Eliminar todas las notificaciones de compra cuando se marca como pagada
            $this->setQuery(
                "DELETE FROM notificaciones 
                 WHERE referencia_id = ?
                 AND modulo = ?
                 AND tipo IN ('COMPRA_POR_AUTORIZAR', 'COMPRA_AUTORIZADA_PAGO')"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute([$idCompra, $moduloComprasId]);
            
            // Log para verificar que se eliminaron las notificaciones
            if ($resultado && $stmt->rowCount() > 0) {
                error_log("TRIGGER: Se eliminaron {$stmt->rowCount()} notificaciones de la compra pagada ID: {$idCompra}");
            } else {
                error_log("TRIGGER: No se encontraron notificaciones para eliminar de la compra ID: {$idCompra}");
            }
            
        } catch (Exception $e) {
            error_log("Error en trigger al limpiar notificaciones de compra pagada: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }
        
        return $resultado;
    }

    public function crearProcedimientoLimpiarNotificacionesCompra()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Primero eliminar el procedimiento si existe
            $this->setQuery("DROP PROCEDURE IF EXISTS limpiar_notificaciones_compra_pagada");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();

            // Crear el nuevo procedimiento
            $procedimiento = "
            CREATE PROCEDURE limpiar_notificaciones_compra_pagada(IN p_idcompra INT)
            BEGIN
                DELETE FROM notificaciones 
                WHERE referencia_id = p_idcompra
                AND modulo = 'compras'
                AND tipo IN ('COMPRA_POR_AUTORIZAR', 'COMPRA_AUTORIZADA_PAGO');
                
                -- Log del resultado
                SELECT ROW_COUNT() as notificaciones_eliminadas;
            END
            ";
            
            $this->setQuery($procedimiento);
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute();
            
            if ($resultado) {
                error_log("Procedimiento limpiar_notificaciones_compra_pagada creado exitosamente");
            }
            
        } catch (Exception $e) {
            error_log("Error al crear procedimiento de limpieza de notificaciones: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }
        
        return $resultado;
    }

    // Método de depuración para diagnosticar problemas de notificaciones
    public function diagnosticarNotificaciones(int $usuarioId, int $rolId) {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $diagnostico = [];
            
            // 1. Verificar notificaciones totales activas
            $this->setQuery("SELECT COUNT(*) as total FROM notificaciones WHERE activa = 1");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $diagnostico['notificaciones_activas'] = $result['total'];
            
            // 2. Verificar permisos del rol
            $this->setQuery(
                "SELECT rmp.idmodulo, m.titulo as modulo, rmp.idpermiso, p.nombre_permiso 
                FROM rol_modulo_permisos rmp 
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo 
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso 
                WHERE rmp.idrol = ? AND rmp.activo = 1"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$rolId]);
            $diagnostico['permisos_usuario'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 3. Verificar notificaciones y sus permisos
            $this->setQuery(
                "SELECT n.idnotificacion, n.tipo, n.modulo, n.permiso, 
                m.titulo as modulo_nombre, p.nombre_permiso 
                FROM notificaciones n 
                LEFT JOIN modulos m ON n.modulo = m.idmodulo 
                LEFT JOIN permisos p ON n.permiso = p.idpermiso 
                WHERE n.activa = 1 
                ORDER BY n.idnotificacion DESC LIMIT 5"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $diagnostico['notificaciones_detalle'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 4. Usar consulta simplificada para ver si hay coincidencias
            $this->setQuery(
                "SELECT COUNT(*) as total 
                FROM notificaciones n 
                INNER JOIN rol_modulo_permisos rmp ON rmp.idmodulo = n.modulo 
                WHERE n.activa = 1 AND rmp.idrol = ? AND rmp.activo = 1"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$rolId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $diagnostico['notificaciones_modulo_match'] = $result['total'];
            
            return $diagnostico;
            
        } catch (Exception $e) {
            error_log("Error en diagnóstico: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    // ===== MÉTODOS CRUD PARA NOTIFICACIONES =====

    /**
     * Marcar una notificación como leída
     */
    public function marcarComoLeidaCompleto($idnotificacion, $usuarioId, $rolId) {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            $sql = "UPDATE notificaciones 
                    SET leida = 1, fecha_lectura = NOW()
                    WHERE idnotificacion = ?
                    AND (idusuario_destino = ? OR idrol_destino = ?)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$idnotificacion, $usuarioId, $rolId]);
            
            $conexion->disconnect();
            
            return [
                'status' => true,
                'message' => 'Notificación marcada como leída'
            ];
            
        } catch (\Exception $e) {
            error_log("Error al marcar como leída: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al marcar como leída'
            ];
        }
    }

    /**
     * Marcar todas las notificaciones como leídas para un usuario/rol
     */
    public function marcarTodasComoLeidasCompleto($usuarioId, $rolId) {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            $sql = "UPDATE notificaciones 
                    SET leida = 1, fecha_lectura = NOW()
                    WHERE (idusuario_destino = ? OR idrol_destino = ?)
                    AND activa = 1
                    AND leida = 0";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$usuarioId, $rolId]);
            $rowsUpdated = $stmt->rowCount();
            
            $conexion->disconnect();
            
            return [
                'status' => true,
                'message' => "Se marcaron $rowsUpdated notificaciones como leídas",
                'cantidad' => $rowsUpdated
            ];
            
        } catch (\Exception $e) {
            error_log("Error al marcar todas como leídas: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al marcar como leídas'
            ];
        }
    }

    /**
     * Eliminar una notificación
     */
    public function eliminarNotificacion($idnotificacion, $usuarioId, $rolId) {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // Soft delete - marcar como inactiva
            $sql = "UPDATE notificaciones 
                    SET activa = 0
                    WHERE idnotificacion = ?
                    AND (idusuario_destino = ? OR idrol_destino = ?)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$idnotificacion, $usuarioId, $rolId]);
            
            $conexion->disconnect();
            
            return [
                'status' => true,
                'message' => 'Notificación eliminada'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al eliminar'
            ];
        }
    }

    /**
     * Limpiar notificaciones antiguas y leídas (más de 30 días)
     */
    public function limpiarNotificacionesAntiguas() {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // Eliminar notificaciones leídas hace más de 30 días
            $sql = "UPDATE notificaciones 
                    SET activa = 0
                    WHERE leida = 1 
                    AND DATE_SUB(NOW(), INTERVAL 30 DAY) >= fecha_lectura";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute();
            $rowsAffected = $stmt->rowCount();
            
            $conexion->disconnect();
            
            return [
                'status' => true,
                'message' => "Se limpiaron $rowsAffected notificaciones antiguas",
                'cantidad' => $rowsAffected
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error al limpiar'
            ];
        }
    }
}
?>