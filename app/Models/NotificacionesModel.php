<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;

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
    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getArray()
    {
        return $this->array ?? [];
    }

    public function setArray(array $array)
    {
        $this->array = $array;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    private function ejecutarCreacionNotificacion(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener el ID del módulo basado en el nombre
            $moduloId = $this->obtenerIdModulo($data['modulo']);
            if (!$moduloId) {
                throw new Exception("Módulo '{$data['modulo']}' no encontrado");
            }

            $this->setQuery(
                "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, 
                    permiso, prioridad, 
                    fecha_creacion, leida, activa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1)"
            );

            // El permiso debe ser el ID del permiso específico
            $permisoId = $data['permiso_id'] ?? null;

            $this->setArray([
                $data['tipo'],
                $data['titulo'],
                $data['mensaje'],
                $moduloId,
                $data['referencia_id'] ?? null,
                $permisoId,
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

    private function ejecutarBusquedaNotificacionesPorUsuario(int $usuarioId, int $rolId)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Verificar si es SuperAdmin (rol ID 1 usualmente)
            $this->setQuery("SELECT nombre FROM roles WHERE idrol = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$rolId]);
            $rolInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $esSuperAdmin = ($rolInfo && (strtolower($rolInfo['nombre']) === 'superadmin' || $rolInfo['nombre'] === 'SuperAdmin'));

            if ($esSuperAdmin) {
                // SuperAdmin ve todas las notificaciones
                $this->setQuery(
                    "SELECT 
                        n.idnotificacion, n.tipo, n.titulo, n.mensaje, n.modulo, 
                        n.referencia_id, n.prioridad, n.fecha_creacion, n.leida,
                        m.titulo as modulo_nombre,
                        p.nombre_permiso,
                        DATE_FORMAT(n.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
                    FROM notificaciones n 
                    LEFT JOIN modulos m ON n.modulo = m.idmodulo
                    LEFT JOIN permisos p ON n.permiso = p.idpermiso
                    WHERE n.activa = 1 
                    ORDER BY n.leida ASC, n.fecha_creacion DESC
                    LIMIT 20"
                );
                $this->setArray([]);
            } else {
                // Para otros roles, usar lógica de permisos
                $this->setQuery(
                    "SELECT DISTINCT
                        n.idnotificacion, n.tipo, n.titulo, n.mensaje, n.modulo, 
                        n.referencia_id, n.prioridad, n.fecha_creacion, n.leida,
                        m.titulo as modulo_nombre,
                        p.nombre_permiso,
                        DATE_FORMAT(n.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
                    FROM notificaciones n 
                    LEFT JOIN modulos m ON n.modulo = m.idmodulo
                    LEFT JOIN permisos p ON n.permiso = p.idpermiso
                    WHERE n.activa = 1 
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
                    )
                    ORDER BY n.leida ASC, n.fecha_creacion DESC
                    LIMIT 20"
                );
                $this->setArray([$rolId]);
            }

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

    private function ejecutarConteoNotificacionesNoLeidas(int $usuarioId, int $rolId)
    {
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

    private function ejecutarMarcarComoLeida(int $notificacionId, int $usuarioId)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener rol del usuario
            $rolId = $this->obtenerRolPorUsuario($usuarioId);
            if (!$rolId) {
                return false;
            }

            $this->setQuery(
                "UPDATE notificaciones n
                SET leida = 1, fecha_lectura = NOW() 
                WHERE n.idnotificacion = ? 
                AND EXISTS (
                    SELECT 1 FROM rol_modulo_permisos rmp 
                    WHERE rmp.idrol = ? 
                    AND rmp.idmodulo = n.modulo 
                    AND rmp.activo = 1
                    AND (
                        -- Opción 1: El usuario tiene el permiso específico de la notificación
                        rmp.idpermiso = n.permiso
                        OR
                        -- Opción 2: El usuario tiene Acceso Total al módulo
                        rmp.idpermiso IN (
                            SELECT p2.idpermiso 
                            FROM permisos p2 
                            WHERE p2.nombre_permiso = 'Acceso Total'
                        )
                        OR
                        -- Opción 3: Para notificaciones sin permiso específico, mostrar si tiene cualquier acceso al módulo
                        n.permiso IS NULL
                    )
                )"
            );

            $this->setArray([$notificacionId, $rolId]);

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

    private function ejecutarMarcarTodasComoLeidas(int $usuarioId, int $rolId)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "UPDATE notificaciones n
                SET leida = 1, fecha_lectura = NOW() 
                WHERE n.leida = 0 AND n.activa = 1
                AND EXISTS (
                    SELECT 1 FROM rol_modulo_permisos rmp 
                    WHERE rmp.idrol = ? 
                    AND rmp.idmodulo = n.modulo 
                    AND rmp.activo = 1
                    AND (
                        -- Opción 1: El usuario tiene el permiso específico de la notificación
                        rmp.idpermiso = n.permiso
                        OR
                        -- Opción 2: El usuario tiene Acceso Total al módulo
                        rmp.idpermiso IN (
                            SELECT p2.idpermiso 
                            FROM permisos p2 
                            WHERE p2.nombre_permiso = 'Acceso Total'
                        )
                        OR
                        -- Opción 3: Para notificaciones sin permiso específico, mostrar si tiene cualquier acceso al módulo
                        n.permiso IS NULL
                    )
                )"
            );

            $this->setArray([$rolId]);

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

    private function ejecutarGenerarNotificacionesProductos()
    {
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
                $titulo = "Stock Bajo - " . $producto['nombre'];
                $mensaje = "El producto '{$producto['nombre']}' tiene solo {$producto['existencia']} unidades en stock.";

                foreach ($permisosAsignados as $permiso) {
                    // Verificar si ya existe una notificación para evitar duplicados
                    if (
                        !$this->ejecutarVerificarNotificacionExistente(
                            'STOCK_BAJO',
                            'productos',
                            $producto['idproducto'],
                            $permiso['idpermiso']
                        )
                    ) {
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
                    if (
                        !$this->ejecutarVerificarNotificacionExistente(
                            'SIN_STOCK',
                            'productos',
                            $producto['idproducto'],
                            $permiso['idpermiso']
                        )
                    ) {
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


    private function ejecutarEliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId)
    {
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

    private function ejecutarObtenerUsuariosConPermiso($modulo, $accion)
    {
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

    /**
     * Método privado para verificar si ya existe una notificación
     */
    private function ejecutarVerificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId = null)
    {
        try {
            // Primero obtener ID del módulo (usa conexión a seguridad)
            $idModulo = $this->obtenerIdModulo($modulo);
            if (!$idModulo) {
                return false;
            }

            // Ahora conectar a la base de datos general para buscar notificaciones
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectGeneral();

            // Construir consulta
            $query = "SELECT COUNT(*) as total 
                FROM notificaciones 
                WHERE tipo = ? 
                AND modulo = ? 
                AND referencia_id = ? 
                AND activa = 1";

            $params = [$tipo, $idModulo, $referenciaId];

            // Si se proporciona permisoId, agregarlo a la condición
            if ($permisoId !== null) {
                $query .= " AND permiso = ?";
                $params[] = $permisoId;
            }

            $this->setQuery($query);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            $conexion->disconnect();
            return ($resultado['total'] > 0);

        } catch (Exception $e) {
            error_log("Error al verificar notificación existente: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Crear notificación de login de usuario
     * Notifica a los roles configurados cuando un usuario inicia sesión
     */
    public function crearNotificacionLogin($usuarioId, $usuarioNombre, $usuarioCorreo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Obtener ID del módulo sistema
            $moduloId = $this->obtenerIdModulo('sistema');
            if (!$moduloId) {
                throw new Exception("Módulo 'sistema' no encontrado");
            }

            // Obtener roles que deben recibir notificaciones de login
            $rolesDestino = $this->obtenerRolesParaNotificacion('LOGIN_USUARIO');

            if (empty($rolesDestino)) {
                error_log("No hay roles configurados para recibir notificaciones de LOGIN_USUARIO");
                return false;
            }

            // Crear una notificación para cada rol configurado
            $this->setQuery(
                "INSERT INTO notificaciones (
                    tipo, titulo, mensaje, modulo, referencia_id, 
                    permiso, prioridad, fecha_creacion, leida, activa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1)"
            );

            $stmt = $db->prepare($this->getQuery());
            $notificacionesCreadas = 0;

            foreach ($rolesDestino as $rol) {
                $titulo = "Nuevo inicio de sesión";
                $mensaje = "El usuario {$usuarioNombre} ({$usuarioCorreo}) ha iniciado sesión en el sistema.";

                $this->setArray([
                    'LOGIN_USUARIO',
                    $titulo,
                    $mensaje,
                    $moduloId,
                    $usuarioId,
                    null, // Sin permiso específico
                    'BAJA'
                ]);

                if ($stmt->execute($this->getArray())) {
                    $notificacionesCreadas++;
                    error_log("Notificación de login creada para rol ID: {$rol['idrol']}");
                }
            }

            return $notificacionesCreadas > 0;

        } catch (Exception $e) {
            error_log("Error al crear notificación de login: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Obtener roles configurados para recibir un tipo específico de notificación
     */
    public function obtenerRolesParaNotificacion($tipoNotificacion)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT ncr.idrol, r.nombre as rol_nombre
                FROM notificaciones_config_roles ncr
                INNER JOIN roles r ON ncr.idrol = r.idrol
                WHERE ncr.tipo_notificacion = ?
                AND ncr.activo = 1
                AND r.estatus = 'ACTIVO'
                ORDER BY ncr.idrol"
            );

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$tipoNotificacion]);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $resultado;

        } catch (Exception $e) {
            error_log("Error al obtener roles para notificación: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    // Métodos públicos
    public function crearNotificacion(array $data)
    {
        return $this->ejecutarCreacionNotificacion($data);
    }

    public function verificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId = null)
    {
        return $this->ejecutarVerificarNotificacionExistente($tipo, $modulo, $referenciaId, $permisoId);
    }

    public function eliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId)
    {
        return $this->ejecutarEliminarNotificacionesPorReferencia($tipo, $modulo, $referenciaId);
    }

    public function obtenerPermisosParaAccion($modulo, $accion)
    {
        return $this->ejecutarObtenerUsuariosConPermiso($modulo, $accion);
    }

    public function obtenerNotificacionesPorUsuario(int $usuarioId, int $rolId)
    {
        return $this->ejecutarBusquedaNotificacionesPorUsuario($usuarioId, $rolId);
    }

    public function contarNotificacionesNoLeidas(int $usuarioId, int $rolId)
    {
        return $this->ejecutarConteoNotificacionesNoLeidas($usuarioId, $rolId);
    }

    public function marcarComoLeida(int $notificacionId, int $usuarioId)
    {
        return $this->ejecutarMarcarComoLeida($notificacionId, $usuarioId);
    }

    public function marcarTodasComoLeidas(int $usuarioId, int $rolId)
    {
        return $this->ejecutarMarcarTodasComoLeidas($usuarioId, $rolId);
    }

    public function generarNotificacionesProductos()
    {
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
    public function diagnosticarNotificaciones(int $usuarioId, int $rolId)
    {
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
}
?>