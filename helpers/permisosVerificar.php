<?php

require_once "helpers/PermisosHelper.php";

class PermisosVerificar
{
   
    private static function obtenerIdUsuario(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

   
    public static function verificarAccesoModulo(string $modulo): bool
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si hay sesión activa
        $idUsuario = self::obtenerIdUsuario();
        
        if (!$idUsuario || !isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            error_log("permisosVerificar: No hay usuario logueado");
            self::redirigirLogin();
            return false;
        }
        
        // Verificar si tiene acceso al módulo
        if (!PermisosHelper::puedeVer($idUsuario, $modulo)) {
            error_log("permisosVerificar: Usuario $idUsuario no tiene acceso al módulo $modulo");
            self::mostrarErrorPermisos("No tienes acceso al módulo: $modulo");
            return false;
        }

        return true;
    }

    /**
     * Verifica permisos específicos para acciones
     */
    public static function verificarPermisoAccion(string $modulo, string $accion): bool
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = self::obtenerIdUsuario();
        
        if (!$idUsuario || !isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            return false;
        }

        switch (strtolower($accion)) { // Convertir acción a minúsculas para consistencia
            case 'crear':
            case 'registrar':
            case 'solo_registrar': // Nueva acción explícita
                return PermisosHelper::puedeCrear($idUsuario, $modulo);
            
            case 'editar':
            case 'actualizar':
            case 'solo_editar': // Nueva acción explícita
                return PermisosHelper::puedeEditar($idUsuario, $modulo);
            
            case 'eliminar':
            case 'delete':
            case 'solo_eliminar': // Nueva acción explícita
                return PermisosHelper::puedeEliminar($idUsuario, $modulo);
            
            case 'ver':
            case 'listar':
            case 'solo_lectura': // Nueva acción explícita
                return PermisosHelper::puedeVer($idUsuario, $modulo);

            // Nuevas acciones combinadas
            case 'acceso_total':
                // Asume que PermisosHelper::tieneAccesoTotal verifica crear, ver, editar y eliminar
                return PermisosHelper::tieneAccesoTotal($idUsuario, $modulo);

            case 'editar_y_eliminar':
                // Asume que PermisosHelper::puedeEditarYEliminar verifica ambos
                return PermisosHelper::puedeEditarYEliminar($idUsuario, $modulo);

            case 'registrar_y_editar':
                // Asume que PermisosHelper::puedeRegistrarYEditar verifica ambos
                return PermisosHelper::puedeRegistrarYEditar($idUsuario, $modulo);

            case 'registrar_y_eliminar':
                // Asume que PermisosHelper::puedeRegistrarYEliminar verifica ambos
                return PermisosHelper::puedeRegistrarYEliminar($idUsuario, $modulo);
            
            default:
                error_log("PermisosVerificar: Acción desconocida '$accion' para el módulo '$modulo'.");
                return false;
        }
    }

    /**
     * Redirige al login
     */
    private static function redirigirLogin()
    {
        if (function_exists('base_url')) {
            $loginUrl = base_url() . '/login';
        } else {
            $loginUrl = '/project/login';
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Muestra error de permisos
     */
    private static function mostrarErrorPermisos(string $mensaje)
    {
        // Si es una petición AJAX, devolver JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => false,
                'message' => $mensaje
            ]);
            exit;
        }

        // Si no es AJAX, mostrar página de error o redirigir
        echo "<h1>Error de Permisos</h1>";
        echo "<p>$mensaje</p>";
        echo "<a href='/project/'>Volver al inicio</a>";
        exit;
    }
}
?>
