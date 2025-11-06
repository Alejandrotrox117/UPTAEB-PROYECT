<?php
namespace App\Helpers;

use App\Models\BitacoraModel;

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
            error_log("Error al registrar acceso a módulo {$modulo}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra una acción específica en la bitácora
     * @param string $modulo Nombre del módulo
     * @param string $accion Tipo de acción realizada
     * @param int $idusuario ID del usuario que realiza la acción
     * @param BitacoraModel $bitacoraModel Instancia del modelo de bitácora
     * @param string $detalle Detalle adicional de la acción (opcional)
     * @param int|null $idRegistro ID del registro afectado (opcional)
     * @return bool True si se registró correctamente, false si no
     */
    public static function registrarAccion($modulo, $accion, $idusuario, $bitacoraModel, $detalle = '', $idRegistro = null)
    {
        if (!$idusuario || !$bitacoraModel) {
            error_log("BitacoraHelper::registrarAccion - Parámetros inválidos: usuario=$idusuario, modelo=" . (is_object($bitacoraModel) ? 'OK' : 'NULL'));
            return false;
        }

        try {
            // Preparar los datos para el registro
            $datosAccion = [
                'modulo' => $modulo,
                'accion' => strtoupper($accion),
                'idusuario' => $idusuario,
                'detalle' => $detalle,
                'id_registro' => $idRegistro,
                'fecha' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];

            // Registrar en la bitácora
            $resultado = $bitacoraModel->registrarAccion($modulo, $datosAccion['accion'], $idusuario, $detalle, $idRegistro);
            
            if (!$resultado) {
                error_log("Warning: No se pudo registrar en bitácora - Módulo: {$modulo}, Acción: {$accion}, Usuario: {$idusuario}");
            } else {
                error_log("✅ Bitácora: {$modulo} - {$accion} - Usuario: {$idusuario}" . ($detalle ? " - {$detalle}" : ""));
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("❌ Error al registrar acción en bitácora - Módulo: {$modulo}, Acción: {$accion}, Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el ID del usuario de la sesión actual
     * @return int|null ID del usuario o null si no está logueado
     */
    public function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Intentar obtener el ID del usuario de diferentes posibles ubicaciones en la sesión
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        }
        
        if (isset($_SESSION['user']['idusuario']) && !empty($_SESSION['user']['idusuario'])) {
            return intval($_SESSION['user']['idusuario']);
        }
        
        if (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }

        return null;
    }

    /**
     * Registra un error en la bitácora
     * @param string $modulo Nombre del módulo donde ocurrió el error
     * @param string $error Descripción del error
     * @param int|null $idusuario ID del usuario (opcional)
     * @param BitacoraModel $bitacoraModel Instancia del modelo de bitácora
     * @return bool True si se registró correctamente, false si no
     */
    public static function registrarError($modulo, $error, $idusuario = null, $bitacoraModel = null)
    {
        if (!$bitacoraModel) {
            $bitacoraModel = new BitacoraModel();
        }

        if (!$idusuario) {
            $helper = new self();
            $idusuario = $helper->obtenerUsuarioSesion() ?? 0;
        }

        return self::registrarAccion($modulo, 'ERROR', $idusuario, $bitacoraModel, $error);
    }

    /**
     * Registra un login exitoso
     * @param int $idusuario ID del usuario que se loguea
     * @param BitacoraModel $bitacoraModel Instancia del modelo de bitácora
     * @return bool True si se registró correctamente, false si no
     */
    public static function registrarLogin($idusuario, $bitacoraModel)
    {
        return self::registrarAccion('Sistema', 'LOGIN', $idusuario, $bitacoraModel, 'Inicio de sesión exitoso');
    }

    /**
     * Registra un logout
     * @param int $idusuario ID del usuario que hace logout
     * @param BitacoraModel $bitacoraModel Instancia del modelo de bitácora
     * @return bool True si se registró correctamente, false si no
     */
    public static function registrarLogout($idusuario, $bitacoraModel)
    {
        return self::registrarAccion('Sistema', 'LOGOUT', $idusuario, $bitacoraModel, 'Cierre de sesión');
    }
}
?>