<?php
namespace App\Helpers;

use App\Core\Conexion;
use PDO;
use PDOException;

class PermisosModuloVerificar
{
    /**
     * Verificar si el usuario tiene acceso al módulo
     */
    public static function verificarAccesoModulo($nombreModulo)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            error_log("❌ Usuario no logueado");
            return false;
        }

        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        
        // ✅ DEBUG: Mostrar permisos obtenidos
        error_log("🔍 DEBUG verificarAccesoModulo - Módulo: $nombreModulo");
        error_log("🔍 DEBUG verificarAccesoModulo - Permisos: " . print_r($permisos, true));
        
        // ✅ VERIFICAR ACCESO TOTAL O AL MENOS UN PERMISO ESPECÍFICO
        $tieneAcceso = $permisos['acceso_total'] || 
                       $permisos['ver'] || 
                       $permisos['crear'] || 
                       $permisos['editar'] || 
                       $permisos['eliminar'] || 
                       $permisos['exportar'];
        
        error_log("🔍 DEBUG verificarAccesoModulo - Resultado: " . ($tieneAcceso ? 'SÍ' : 'NO'));
        
        return $tieneAcceso;
    }

    /**
     * Verificar si el usuario tiene permiso para una acción específica
     */
    public static function verificarPermisoModuloAccion($nombreModulo, $accion)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            return false;
        }

        $permisos = self::getPermisosUsuarioModulo($nombreModulo);
        
        error_log("🔍 DEBUG verificarPermisoModuloAccion - Módulo: $nombreModulo, Acción: $accion");
        error_log("🔍 DEBUG verificarPermisoModuloAccion - Permisos: " . print_r($permisos, true));
        
        // ✅ SI TIENE ACCESO TOTAL, PERMITIR CUALQUIER ACCIÓN
        if ($permisos['acceso_total']) {
            error_log("✅ Acceso permitido por ACCESO TOTAL");
            return true;
        }
        
        // ✅ VERIFICAR PERMISO ESPECÍFICO
        $resultado = isset($permisos[$accion]) && $permisos[$accion] === true;
        error_log("🔍 DEBUG verificarPermisoModuloAccion - Resultado: " . ($resultado ? 'SÍ' : 'NO'));
        
        return $resultado;
    }

    /**
     * Obtener todos los permisos del usuario para un módulo
     */
    public static function getPermisosUsuarioModulo($nombreModulo)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            error_log("❌ Usuario no logueado en getPermisosUsuarioModulo");
            return self::getPermisosVacios();
        }

        // ✅ USAR LA ESTRUCTURA CORRECTA DE SESIÓN
        $idUsuario = $_SESSION['usuario_id'] ?? $_SESSION['user']['idusuario'] ?? 0;
        $idRol = $_SESSION['user']['idrol'] ?? $_SESSION['rol_id'] ?? 0;

        error_log("🔍 DEBUG getPermisosUsuarioModulo - Usuario: $idUsuario, Rol: $idRol, Módulo: $nombreModulo");

        if ($idUsuario <= 0 || $idRol <= 0) {
            error_log("❌ IDs inválidos - Usuario: $idUsuario, Rol: $idRol");
            return self::getPermisosVacios();
        }

        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // ✅ CONSULTA MEJORADA
            $query = "
                SELECT 
                    m.titulo as modulo_nombre,
                    p.idpermiso,
                    p.nombre_permiso,
                    rmp.activo,
                    rmp.idrol,
                    rmp.idmodulo
                FROM rol_modulo_permisos rmp
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE rmp.idrol = ? 
                AND LOWER(m.titulo) = LOWER(?)
                AND m.estatus = 'activo'
                AND rmp.activo = 1
                ORDER BY p.idpermiso
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$idRol, $nombreModulo]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("🔍 SQL ejecutado: " . str_replace('?', "'$idRol', '$nombreModulo'", $query));
            error_log("🔍 Resultados encontrados: " . count($resultados));
            if (!empty($resultados)) {
                error_log("🔍 Datos encontrados: " . print_r($resultados, true));
            }

            $conexion->disconnect();

            // ✅ INICIALIZAR PERMISOS
            $permisos = self::getPermisosVacios();
            
            // ✅ PROCESAR CADA PERMISO ENCONTRADO
            foreach ($resultados as $resultado) {
                $idPermiso = (int)$resultado['idpermiso'];
                
                error_log("⚙️ Procesando permiso ID: $idPermiso ({$resultado['nombre_permiso']})");
                
                // ✅ MAPEAR SEGÚN LOS PERMISOS DEFINIDOS EN TU BD
                switch ($idPermiso) {
                    case 8: // Acceso Total
                        $permisos['acceso_total'] = true;
                        $permisos['ver'] = true;
                        $permisos['crear'] = true;
                        $permisos['editar'] = true;
                        $permisos['eliminar'] = true;
                        $permisos['exportar'] = true;
                        error_log("✅ ACCESO TOTAL asignado");
                        break;
                        
                    case 1: // Solo Lectura
                        $permisos['ver'] = true;
                        error_log("✅ SOLO LECTURA asignado");
                        break;
                        
                    case 2: // Solo Editar
                        $permisos['editar'] = true;
                        $permisos['ver'] = true; // Para editar necesita ver
                        error_log("✅ SOLO EDITAR asignado (incluye VER)");
                        break;
                        
                    case 3: // Solo Registrar
                        $permisos['crear'] = true;
                        $permisos['ver'] = true; // Para crear necesita ver la lista
                        error_log("✅ SOLO REGISTRAR asignado (incluye VER)");
                        break;
                        
                    case 4: // Registrar y Editar
                        $permisos['crear'] = true;
                        $permisos['editar'] = true;
                        $permisos['ver'] = true; // Para ambas acciones necesita ver
                        error_log("✅ REGISTRAR Y EDITAR asignado (incluye VER)");
                        break;
                        
                    case 5: // Solo Eliminar
                        $permisos['eliminar'] = true;
                        $permisos['ver'] = true; // Para eliminar necesita ver
                        error_log("✅ SOLO ELIMINAR asignado (incluye VER)");
                        break;
                        
                    case 6: // Editar y Eliminar
                        $permisos['editar'] = true;
                        $permisos['eliminar'] = true;
                        $permisos['ver'] = true;
                        error_log("✅ EDITAR Y ELIMINAR asignado (incluye VER)");
                        break;
                        
                    case 7: // Registrar y Eliminar
                        $permisos['crear'] = true;
                        $permisos['eliminar'] = true;
                        $permisos['ver'] = true;
                        error_log("✅ REGISTRAR Y ELIMINAR asignado (incluye VER)");
                        break;
                        
                    default:
                        error_log("❌ Permiso ID $idPermiso NO RECONOCIDO");
                        break;
                }
            }

            error_log("🎯 PERMISOS FINALES CALCULADOS: " . print_r($permisos, true));
            
            return $permisos;

        } catch (Exception $e) {
            error_log("❌ ERROR en getPermisosUsuarioModulo: " . $e->getMessage());
            return self::getPermisosVacios();
        }
    }

    /**
     * Retornar estructura de permisos vacía
     */
    private static function getPermisosVacios()
    {
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
?>
