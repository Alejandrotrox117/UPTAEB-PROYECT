<?php
// Iniciar sesión al comienzo de todo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Requerir archivos de configuración y core
require_once "config/config.php"; // Asegura que las variables de .env se carguen primero
require_once "app/core/Controllers.php";
require_once "vendor/autoload.php";
require_once "helpers/helpers.php";

// Obtener la URL solicitada de forma segura
$url = !empty($_GET['url']) ? $_GET['url'] : 'login'; // Por defecto, ir a 'login'

$arrUrl = explode('/', $url);
$controller = !empty($arrUrl[0]) ? strtolower($arrUrl[0]) : 'login';
$method = 'index';
if (isset($arrUrl[1]) && $arrUrl[1] != "") {
    $method = $arrUrl[1];
}
$params = !empty($arrUrl[2]) ? array_slice($arrUrl, 2) : [];

// --- ESTA ES LA SOLUCIÓN CLAVE ---
// Definir rutas públicas que NO necesitan autenticación
$publicRoutes = ['login', 'home', 'register', 'forgot-password', 'session_test'];

// Verificar sesión SÓLO si la ruta solicitada NO es pública
if (!in_array($controller, $publicRoutes)) {
    // Si la sesión no es válida, redirigir al login
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        // Usamos la constante BASE_URL definida en config.php para una redirección robusta
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

// Cargar y ejecutar el controlador
$controllerFile = "app/controllers/" . ucfirst($controller) . ".php";
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClassName = ucfirst($controller);
    $controllerInstance = new $controllerClassName();

    if (method_exists($controllerInstance, $method)) {
        call_user_func_array(array($controllerInstance, $method), $params);
    } else {
        // Método no encontrado, cargar controlador de error
        require_once "app/controllers/error.php";
        $errorController = new Error();
        $errorController->index();
    }
} else {
    // Controlador no encontrado, cargar controlador de error
    require_once "app/controllers/error.php";
    $errorController = new Error();
    $errorController->index();
}
