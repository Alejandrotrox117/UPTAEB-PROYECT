<?php
require_once "app/models/bitacoraModel.php";
class BitacoraHelper 
{
    /**
     * Registra el acceso a un módulo en la bitácora
     * @param string $modulo Nombre del módulo al que se accede
     * @param int $idusuario ID del usuario que accede
     * @param BitacoraModel $bitacoraModel Instancia del modelo de bitácora
     * @return bool True si se registró correctamente, false si no
     */
    public static function registrarAccesoModulo($modulo, $idusuario, $bitacoraModel)
    {
        if (!$idusuario || !$bitacoraModel) {
            return false;
        }

        try {
            $resultado = $bitacoraModel->registrarAccion($modulo, 'ACCESO_MODULO', $idusuario);
            
            if (!$resultado) {
                error_log("Warning: No se pudo registrar en bitácora el acceso al módulo {$modulo} del usuario ID: {$idusuario}");
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error al registrar acceso al módulo {$modulo}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el ID de usuario de la sesión
     * @return int|null ID del usuario o null si no está logueado
     */
    public static function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['idusuario'])) {
            return $_SESSION['idusuario'];
        } elseif (isset($_SESSION['idUser'])) {
            return $_SESSION['idUser'];
        } elseif (isset($_SESSION['usuario_id'])) {
            return $_SESSION['usuario_id'];
        }

        return null;
    }
}
?>