<?php
// Iniciar sesión al comienzo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "app/core/Controllers.php";
require_once "vendor/autoload.php";
require_once "config/config.php"; // Cargar configuración antes que helpers
require_once "helpers/helpers.php";

// Configurar headers de seguridad CSP
setCSPHeaders();

$url = !empty($_GET['url']) ? $_GET['url'] : 'dashboard/dashboard'; // URL

$arrUrl = explode('/', $url);
$controller = ucfirst($arrUrl[0]); // Controlador
$method = isset($arrUrl[1]) && $arrUrl[1] != "" ? $arrUrl[1] : 'index'; // Método
$params = [];

// Obtener parámetros adicionales
if (isset($arrUrl[2]) && $arrUrl[2] != "") {
    for ($i = 2; $i < count($arrUrl); $i++) {
        $params[] = $arrUrl[$i];
    }
}

// Verificar autenticación excepto para login
if ($controller !== 'Login' && $controller !== 'Error') {
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || !isset($_SESSION['usuario_id'])) {
        header('Location: ' . base_url('login'));
        exit();
    }
}

// Cargar el controlador
$controllerPath = "app/controllers/" . $controller . ".php";

if (file_exists($controllerPath)) {
    require_once($controllerPath);
    
    if (class_exists($controller)) {
        $controllerObj = new $controller();

        if (method_exists($controllerObj, $method)) {
            call_user_func_array([$controllerObj, $method], $params);
        } else {
            echo "Método $method no encontrado en el controlador $controller.";
        }
    } else {
        echo "Clase $controller no encontrada.";
    }
} else {
    echo "Controlador $controller no encontrado.";
}
?>