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
    error_log("🔍 renderView called - Controller: $controller, View: $view");
    
    // Para controladores especiales
    if (strtolower($controller) === 'home') {
        $viewPath = "app/views/home/home.php";
        error_log("🏠 Home view path: $viewPath");
    } elseif (strtolower($controller) === 'errors') {
        $viewPath = "app/views/Errors/" . $view . ".php";
        error_log("❌ Errors view path: $viewPath");
    } else {
        // Intentar con el nombre original primero (puede tener mayúsculas)
        $viewPath = "app/views/" . $controller . "/" . $view . ".php";
        error_log("🔍 Intento 1 (original): $viewPath - " . (file_exists($viewPath) ? "EXISTS" : "NO EXISTE"));
        
        // Si no existe, intentar con minúsculas
        if (!file_exists($viewPath)) {
            $controllerLower = strtolower($controller);
            $viewPath = "app/views/" . $controllerLower . "/" . $view . ".php";
            error_log("🔍 Intento 2 (lowercase): $viewPath - " . (file_exists($viewPath) ? "EXISTS" : "NO EXISTE"));
        }
        
        // Si aún no existe, intentar con primera letra mayúscula
        if (!file_exists($viewPath)) {
            $controllerUcfirst = ucfirst(strtolower($controller));
            $viewPath = "app/views/" . $controllerUcfirst . "/" . $view . ".php";
            error_log("🔍 Intento 3 (ucfirst): $viewPath - " . (file_exists($viewPath) ? "EXISTS" : "NO EXISTE"));
        }
    }
    
    error_log("📂 Vista final a cargar: $viewPath");
    
    // Extraer datos para que estén disponibles en la vista
    if (is_array($data) && !empty($data)) {
        extract($data);
    }
    
    // Cargar la vista
    if (file_exists($viewPath)) {
        error_log("✅ Cargando vista: $viewPath");
        require_once($viewPath);
    } else {
        error_log("❌ Vista no encontrada: $viewPath");
        echo "Error: Vista no encontrada - $viewPath";
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
