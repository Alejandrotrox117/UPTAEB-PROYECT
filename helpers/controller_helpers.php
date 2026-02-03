<?php
/**
 * Controller Helpers - Funciones Auxiliares para Controladores Funcionales
 * 
 * Este archivo proporciona funciones de utilidad que reemplazan la funcionalidad
 * de la clase base Controllers para controladores convertidos a funciones.
 */

// =============================================================================
// FUNCIONES DE RENDERIZADO DE VISTAS
// =============================================================================

/**
 * Renderiza una vista para controladores funcionales
 * 
 * @param string $controller Nombre del controlador (ej: 'usuarios')
 * @param string $view Nombre de la vista (ej: 'usuarios', 'crear')
 * @param array $data Datos a pasar a la vista
 */
function renderView($controller, $view, $data = []) {
    // Normalizar nombre del controlador
    $controller = strtolower($controller);
    
    // Para controladores especiales
    if ($controller === 'home') {
        $viewPath = "app/views/home/home.php";
    } elseif ($controller === 'errors') {
        $viewPath = "app/views/Errors/" . $view . ".php";
    } else {
        $viewPath = "app/views/" . $controller . "/" . $view . ".php";
    }
    
    // Extraer datos para que estén disponibles en la vista
    if (is_array($data) && !empty($data)) {
        extract($data);
    }
    
    // Cargar la vista
    if (file_exists($viewPath)) {
        require_once($viewPath);
    } else {
        error_log("Vista no encontrada: $viewPath");
        echo "Error: Vista no encontrada";
    }
}

// =============================================================================
// FUNCIONES DE AUTENTICACIÓN Y PERMISOS
// =============================================================================

/**
 * Verifica que el usuario esté autenticado y tenga acceso al módulo
 * Redirecciona a login si no está autenticado o muestra página de permisos si no tiene acceso
 * 
 * @param string $moduloNombre Nombre del módulo
 * @param bool $checkAuth Si debe verificar autenticación (por defecto true)
 * @return bool True si tiene acceso, redirecciona/muestra error y termina ejecución si no
 */
function verificarAccesoModulo($moduloNombre, $checkAuth = true) {
    // Verificar autenticación
    if ($checkAuth) {
        $BitacoraHelper = new \App\Helpers\BitacoraHelper();
        if (!$BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }
    
    // Verificar acceso al módulo
    if (!\App\Helpers\PermisosModuloVerificar::verificarAccesoModulo($moduloNombre)) {
        renderView('errors', 'permisos');
        exit();
    }
    
    return true;
}

// =============================================================================
// FUNCIONES DE UTILIDAD
// =============================================================================

/**
 * Obtiene el usuario actual de la sesión
 * 
 * @return int|false ID del usuario o false si no hay sesión
 */
function obtenerUsuarioSesion() {
    $BitacoraHelper = new \App\Helpers\BitacoraHelper();
    return $BitacoraHelper->obtenerUsuarioSesion();
}

/**
 * Registra una acción en la bitácora
 * 
 * @param string $modulo Nombre del módulo
 * @param string $accion Acción realizada
 * @param int $idusuario ID del usuario (opcional, se obtiene de sesión si no se proporciona)
 * @param string $detalles Detalles adicionales (opcional)
 * @return bool True si se registró correctamente
 */
function registrarEnBitacora($modulo, $accion, $idusuario = null, $detalles = '') {
    if ($idusuario === null) {
        $idusuario = obtenerUsuarioSesion();
    }
    
    $bitacoraModel = new \App\Models\BitacoraModel();
    return $bitacoraModel->registrarAccion($modulo, $accion, $idusuario, $detalles);
}

/**
 * Registra acceso a un módulo en la bitácora
 * 
 * @param string $modulo Nombre del módulo
 * @param int $idusuario ID del usuario (opcional)
 * @return bool True si se registró correctamente
 */
function registrarAccesoModulo($modulo, $idusuario = null) {
    if ($idusuario === null) {
        $idusuario = obtenerUsuarioSesion();
    }
    
    $bitacoraModel = new \App\Models\BitacoraModel();
    return \App\Helpers\BitacoraHelper::registrarAccesoModulo($modulo, $idusuario, $bitacoraModel);
}

?>
