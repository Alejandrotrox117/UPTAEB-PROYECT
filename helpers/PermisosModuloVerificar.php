<?php

require_once "app/core/conexion.php";

class PermisosModuloVerificar
{
    private static $conexionSeguridad = null;
    private static $cachePermisos = [];
    
    private static function getConexionSeguridad()
    {
        if (self::$conexionSeguridad === null) {
            $conexion = new Conexion();
            $conexion->connect();
            self::$conexionSeguridad = $conexion->get_conectSeguridad();
        }
        return self::$conexionSeguridad;
    }

    /**
     * Verificar si un usuario tiene un permiso específico en un módulo específico
     */
    public static function verificarPermisoModuloAccion(string $nombreModulo, string $nombrePermiso): bool
    {
        if (!isset($_SESSION['user']['idusuario'])) {
            return false;
        }

        $idusuario = $_SESSION['user']['idusuario'];
        $cacheKey = $idusuario . '_' . $nombreModulo . '_' . $nombrePermiso;
        
        // Verificar cache
        if (isset(self::$cachePermisos[$cacheKey])) {
            return self::$cachePermisos[$cacheKey];
        }

        try {
            $db = self::getConexionSeguridad();
            
            $sql = "SELECT COUNT(*) as tiene_permiso
                    FROM rol_modulo_permisos rmp
                    INNER JOIN usuarios u ON u.idrol = rmp.idrol
                    INNER JOIN modulos m ON m.idmodulo = rmp.idmodulo
                    INNER JOIN permisos p ON p.idpermiso = rmp.idpermiso
                    WHERE u.idusuario = ? 
                    AND m.titulo = ? 
                    AND p.nombre_permiso = ?
                    AND rmp.activo = 1
                    AND u.estatus = 'activo'
                    AND m.estatus = 'activo'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$idusuario, $nombreModulo, $nombrePermiso]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $tienePermiso = $resultado['tiene_permiso'] > 0;
            
            // Guardar en cache
            self::$cachePermisos[$cacheKey] = $tienePermiso;
            
            return $tienePermiso;
            
        } catch (Exception $e) {
            error_log("Error en verificarPermisoModuloAccion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los permisos de un usuario para un módulo específico
     */
    public static function getPermisosUsuarioModulo(string $nombreModulo): array
    {
        if (!isset($_SESSION['user']['idusuario'])) {
            return [
                'ver' => false,
                'crear' => false,
                'editar' => false,
                'eliminar' => false,
                'exportar' => false,
                'acceso_total' => false
            ];
        }

        $idusuario = $_SESSION['user']['idusuario'];
        $cacheKey = $idusuario . '_module_' . $nombreModulo;
        
        // Verificar cache
        if (isset(self::$cachePermisos[$cacheKey])) {
            return self::$cachePermisos[$cacheKey];
        }

        try {
            $db = self::getConexionSeguridad();
            
            $sql = "SELECT p.nombre_permiso
                    FROM rol_modulo_permisos rmp
                    INNER JOIN usuarios u ON u.idrol = rmp.idrol
                    INNER JOIN modulos m ON m.idmodulo = rmp.idmodulo
                    INNER JOIN permisos p ON p.idpermiso = rmp.idpermiso
                    WHERE u.idusuario = ? 
                    AND m.titulo = ?
                    AND rmp.activo = 1
                    AND u.estatus = 'activo'
                    AND m.estatus = 'activo'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$idusuario, $nombreModulo]);
            
            $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $resultado = [
                'ver' => in_array('ver', $permisos),
                'crear' => in_array('crear', $permisos),
                'editar' => in_array('editar', $permisos),
                'eliminar' => in_array('eliminar', $permisos),
                'exportar' => in_array('exportar', $permisos),
                'acceso_total' => in_array('acceso_total', $permisos)
            ];
            
            // Guardar en cache
            self::$cachePermisos[$cacheKey] = $resultado;
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error en getPermisosUsuarioModulo: " . $e->getMessage());
            return [
                'ver' => false,
                'crear' => false,
                'editar' => false,
                'eliminar' => false,
                'exportar' => false,
                'acceso_total' => false
            ];
        }
    }

    /**
     * Verificar acceso al módulo (al menos un permiso)
     */
    public static function verificarAccesoModulo(string $nombreModulo): bool
    {
        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        return $permisos['ver'] || $permisos['crear'] || $permisos['editar'] || 
               $permisos['eliminar'] || $permisos['exportar'] || $permisos['acceso_total'];
    }

    /**
     * Obtener módulos accesibles para el usuario actual
     */
    public static function getModulosAccesibles(): array
    {
        if (!isset($_SESSION['user']['idusuario'])) {
            return [];
        }

        try {
            $db = self::getConexionSeguridad();
            
            $sql = "SELECT DISTINCT m.idmodulo, m.titulo, m.descripcion
                    FROM rol_modulo_permisos rmp
                    INNER JOIN usuarios u ON u.idrol = rmp.idrol
                    INNER JOIN modulos m ON m.idmodulo = rmp.idmodulo
                    WHERE u.idusuario = ? 
                    AND rmp.activo = 1
                    AND u.estatus = 'activo'
                    AND m.estatus = 'activo'
                    ORDER BY m.titulo";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['user']['idusuario']]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getModulosAccesibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Método de compatibilidad con el sistema anterior
     * @deprecated Usar verificarPermisoModuloAccion en su lugar
     */
    public static function verificarPermisoAccion(string $modulo, string $accion): bool
    {
        return self::verificarPermisoModuloAccion($modulo, $accion);
    }

    /**
     * Limpiar cache de permisos
     */
    public static function limpiarCachePermisos(): void
    {
        self::$cachePermisos = [];
    }
}
?>