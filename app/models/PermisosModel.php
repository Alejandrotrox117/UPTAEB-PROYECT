<?php
// app/Models/PermisosModel.php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class PermisosModel extends mysql
{
    private $conexion;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    /**
     * Verifica si un usuario tiene un permiso específico para un módulo
     */
    public function verificarPermisoEspecifico(int $idUsuario, string $modulo, array $permisosRequeridos): bool
    {
        try {
            // Debug temporal
            error_log("=== DEBUG PERMISOSMODEL ===");
            error_log("Usuario ID: $idUsuario");
            error_log("Módulo: $modulo");
            error_log("Permisos requeridos: " . implode(', ', $permisosRequeridos));

            // Obtener el rol del usuario
            $sqlRol = "SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'activo'";
            $stmtRol = $this->dbSeguridad->prepare($sqlRol);
            $stmtRol->execute([$idUsuario]);
            $usuario = $stmtRol->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                error_log("Usuario no encontrado o inactivo");
                return false;
            }

            $idRol = $usuario['idrol'];
            error_log("Rol del usuario: $idRol");

            // Crear placeholders para la consulta IN
            $placeholders = str_repeat('?,', count($permisosRequeridos) - 1) . '?';

            // Verificar permisos por rol
            $sql = "SELECT COUNT(*) as tiene_permiso
                    FROM rol_modulo_permisos rmp
                    JOIN modulos m ON rmp.idmodulo = m.idmodulo
                    WHERE rmp.idrol = ? 
                    AND m.titulo = ? 
                    AND rmp.idpermiso IN ($placeholders)
                    AND rmp.activo = 1
                    AND m.estatus = 'activo'";

            $parametros = array_merge([$idRol, $modulo], $permisosRequeridos);
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($parametros);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $tienePermiso = ($result['tiene_permiso'] > 0);
            error_log("Resultado: " . ($tienePermiso ? 'SÍ tiene permiso' : 'NO tiene permiso'));
            error_log("=== FIN DEBUG PERMISOSMODEL ===");

            return $tienePermiso;

        } catch (PDOException $e) {
            error_log("Error en verificarPermisoEspecifico: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos de un usuario para un módulo
     */
    public function obtenerPermisosUsuarioModulo(int $idUsuario, string $modulo): array
    {
        try {
            // Obtener permisos por rol
            $sqlRol = "SELECT DISTINCT p.idpermiso, p.nombre_permiso
                      FROM usuario u
                      JOIN rol_modulo_permisos rmp ON u.idrol = rmp.idrol
                      JOIN modulos m ON rmp.idmodulo = m.idmodulo
                      JOIN permisos p ON rmp.idpermiso = p.idpermiso
                      WHERE u.idusuario = ? 
                      AND m.titulo = ?
                      AND rmp.activo = 1
                      AND m.estatus = 'activo'
                      AND u.estatus = 'activo'";

            $stmt = $this->dbSeguridad->prepare($sqlRol);
            $stmt->execute([$idUsuario, $modulo]);
            $permisosRol = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener permisos directos (si existen)
            $sqlDirecto = "SELECT DISTINCT p.idpermiso, p.nombre_permiso
                          FROM usuario_modulo_permiso ump
                          JOIN modulos m ON ump.idmodulo = m.idmodulo
                          JOIN permisos p ON ump.idpermiso = p.idpermiso
                          WHERE ump.idusuario = ? 
                          AND m.titulo = ?
                          AND m.estatus = 'activo'";

            $stmtDirecto = $this->dbSeguridad->prepare($sqlDirecto);
            $stmtDirecto->execute([$idUsuario, $modulo]);
            $permisosDirectos = $stmtDirecto->fetchAll(PDO::FETCH_ASSOC);

            // Combinar permisos (los directos tienen prioridad)
            $todosPermisos = array_merge($permisosRol, $permisosDirectos);
            
            // Eliminar duplicados por idpermiso
            $permisosUnicos = [];
            foreach ($todosPermisos as $permiso) {
                $permisosUnicos[$permiso['idpermiso']] = $permiso;
            }

            return array_values($permisosUnicos);

        } catch (PDOException $e) {
            error_log("Error obteniendo permisos usuario-módulo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un usuario tiene acceso a un módulo (sin verificar permisos específicos)
     */
    public function tieneAccesoModulo(int $idUsuario, string $modulo): bool
    {
        try {
            // Obtener el rol del usuario
            $sqlRol = "SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'activo'";
            $stmtRol = $this->dbSeguridad->prepare($sqlRol);
            $stmtRol->execute([$idUsuario]);
            $usuario = $stmtRol->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return false;
            }

            $idRol = $usuario['idrol'];

            // Verificar si tiene algún permiso en el módulo
            $sql = "SELECT COUNT(*) as tiene_acceso
                    FROM rol_modulo_permisos rmp
                    JOIN modulos m ON rmp.idmodulo = m.idmodulo
                    WHERE rmp.idrol = ? 
                    AND m.titulo = ? 
                    AND rmp.activo = 1
                    AND m.estatus = 'activo'";

            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idRol, $modulo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ($result['tiene_acceso'] > 0);

        } catch (PDOException $e) {
            error_log("Error verificando acceso a módulo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los módulos a los que un usuario tiene acceso
     */
    public function obtenerModulosUsuario(int $idUsuario): array
    {
        try {
            // Obtener el rol del usuario
            $sqlRol = "SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'activo'";
            $stmtRol = $this->dbSeguridad->prepare($sqlRol);
            $stmtRol->execute([$idUsuario]);
            $usuario = $stmtRol->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return [];
            }

            $idRol = $usuario['idrol'];

            // Obtener módulos con acceso
            $sql = "SELECT DISTINCT m.idmodulo, m.titulo, m.descripcion
                    FROM rol_modulo_permisos rmp
                    JOIN modulos m ON rmp.idmodulo = m.idmodulo
                    WHERE rmp.idrol = ? 
                    AND rmp.activo = 1
                    AND m.estatus = 'activo'
                    ORDER BY m.titulo";

            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idRol]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo módulos del usuario: " . $e->getMessage());
            return [];
        }
    }
}
?>
