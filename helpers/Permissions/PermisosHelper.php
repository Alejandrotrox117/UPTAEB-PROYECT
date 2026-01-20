<?php
namespace App\Helpers\Permissions;

use App\Core\Conexion;
use PDO;
use PDOException;

/**
 * Helper consolidado para verificación de permisos de módulos
 * Combina la funcionalidad de PermisosHelper y PermisosModuloVerificar
 * 
 * @package App\Helpers\Permissions
 */
class PermisosHelper
{
    // Constantes de permisos según la BD
    const SOLO_LECTURA = 1;
    const SOLO_REGISTRAR = 2;
    const SOLO_EDITAR = 3;
    const SOLO_ELIMINAR = 4;
    const REGISTRAR_Y_EDITAR = 5;
    const EDITAR_Y_ELIMINAR = 6;
    const REGISTRAR_Y_ELIMINAR = 7;
    const ACCESO_TOTAL = 8;

    /**
     * Verifica si el usuario tiene acceso al módulo
     * 
     * @param string $nombreModulo Nombre del módulo
     * @return bool True si tiene acceso, false si no
     */
    public static function verificarAccesoModulo(string $nombreModulo): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        $idusuario = intval($_SESSION['usuario_id']);
        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        
        return $permisos['tiene_acceso'] ?? false;
    }

    /**
     * Verifica si el usuario tiene permiso para una acción específica
     * 
     * @param string $nombreModulo Nombre del módulo
     * @param string $accion Acción a verificar (ver, crear, editar, eliminar, exportar)
     * @return bool True si tiene permiso, false si no
     */
    public static function verificarPermisoModuloAccion(string $nombreModulo, string $accion): bool
    {
        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        
        if (!($permisos['tiene_acceso'] ?? false)) {
            return false;
        }

        $accionNormalizada = strtolower($accion);
        
        return match($accionNormalizada) {
            'ver' => $permisos['puede_ver'] ?? false,
            'crear', 'registrar' => $permisos['puede_crear'] ?? false,
            'editar', 'actualizar' => $permisos['puede_editar'] ?? false,
            'eliminar', 'borrar' => $permisos['puede_eliminar'] ?? false,
            'exportar' => $permisos['puede_ver'] ?? false, // Exportar requiere al menos ver
            default => false
        };
    }

    /**
     * Obtiene todos los permisos del usuario para un módulo
     * 
     * @param string $nombreModulo Nombre del módulo
     * @return array Array con permisos detallados
     */
    public static function getPermisosUsuarioModulo(string $nombreModulo): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            return self::getPermisosVacios();
        }

        $idusuario = intval($_SESSION['usuario_id']);
        
        try {
            $conexion = new Conexion();
            $pdo = $conexion->conectar();

            // Query para obtener permisos del usuario en el módulo
            $sql = "SELECT 
                        rm.idpermiso,
                        m.nombre as modulo_nombre,
                        m.titulo as modulo_titulo,
                        p.nombre as permiso_nombre,
                        p.descripcion as permiso_descripcion
                    FROM roles_modulos rm
                    INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                    INNER JOIN permisos p ON rm.idpermiso = p.idpermiso
                    INNER JOIN usuarios u ON u.idrol = rm.idrol
                    WHERE u.idusuario = :idusuario 
                    AND u.estatus = 'activo'
                    AND LOWER(m.nombre) = LOWER(:modulo)
                    AND m.status = 1
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':modulo', $nombreModulo, PDO::PARAM_STR);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return self::getPermisosVacios();
            }

            $idPermiso = intval($resultado['idpermiso']);

            // Determinar permisos específicos basados en el tipo
            return [
                'tiene_acceso' => true,
                'id_permiso' => $idPermiso,
                'permiso_nombre' => $resultado['permiso_nombre'],
                'puede_ver' => self::tienePermisoVer($idPermiso),
                'puede_crear' => self::tienePermisoCrear($idPermiso),
                'puede_editar' => self::tienePermisoEditar($idPermiso),
                'puede_eliminar' => self::tienePermisoEliminar($idPermiso),
                'es_acceso_total' => $idPermiso === self::ACCESO_TOTAL,
                'modulo' => $nombreModulo,
                'modulo_titulo' => $resultado['modulo_titulo']
            ];

        } catch (PDOException $e) {
            error_log("Error obteniendo permisos para módulo {$nombreModulo}: " . $e->getMessage());
            return self::getPermisosVacios();
        }
    }

    /**
     * Verifica si el id de permiso permite VER
     */
    private static function tienePermisoVer(int $idPermiso): bool
    {
        return in_array($idPermiso, [
            self::SOLO_LECTURA,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ]);
    }

    /**
     * Verifica si el id de permiso permite CREAR
     */
    private static function tienePermisoCrear(int $idPermiso): bool
    {
        return in_array($idPermiso, [
            self::SOLO_REGISTRAR,
            self::REGISTRAR_Y_EDITAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ]);
    }

    /**
     * Verifica si el id de permiso permite EDITAR
     */
    private static function tienePermisoEditar(int $idPermiso): bool
    {
        return in_array($idPermiso, [
            self::SOLO_EDITAR,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ]);
    }

    /**
     * Verifica si el id de permiso permite ELIMINAR
     */
    private static function tienePermisoEliminar(int $idPermiso): bool
    {
        return in_array($idPermiso, [
            self::SOLO_ELIMINAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ]);
    }

    /**
     * Retorna estructura de permisos vacía (sin acceso)
     */
    private static function getPermisosVacios(): array
    {
        return [
            'tiene_acceso' => false,
            'id_permiso' => 0,
            'permiso_nombre' => '',
            'puede_ver' => false,
            'puede_crear' => false,
            'puede_editar' => false,
            'puede_eliminar' => false,
            'es_acceso_total' => false,
            'modulo' => '',
            'modulo_titulo' => ''
        ];
    }

    /**
     * Verifica si el usuario tiene acceso total al módulo
     */
    public static function tieneAccesoTotal(string $nombreModulo): bool
    {
        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        return $permisos['es_acceso_total'] ?? false;
    }

    // ========================================================================
    // MÉTODOS DE COMPATIBILIDAD CON VERSIÓN ANTERIOR
    // ========================================================================

    /**
     * @deprecated Use verificarPermisoModuloAccion($modulo, 'ver')
     */
    public static function puedeVer(int $idUsuario, string $modulo): bool
    {
        return self::verificarPermisoModuloAccion($modulo, 'ver');
    }

    /**
     * @deprecated Use verificarPermisoModuloAccion($modulo, 'crear')
     */
    public static function puedeCrear(int $idUsuario, string $modulo): bool
    {
        return self::verificarPermisoModuloAccion($modulo, 'crear');
    }

    /**
     * @deprecated Use verificarPermisoModuloAccion($modulo, 'editar')
     */
    public static function puedeEditar(int $idUsuario, string $modulo): bool
    {
        return self::verificarPermisoModuloAccion($modulo, 'editar');
    }

    /**
     * @deprecated Use verificarPermisoModuloAccion($modulo, 'eliminar')
     */
    public static function puedeEliminar(int $idUsuario, string $modulo): bool
    {
        return self::verificarPermisoModuloAccion($modulo, 'eliminar');
    }
}
