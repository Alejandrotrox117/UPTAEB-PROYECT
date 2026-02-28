<?php

// Inicia la sesión si no ha sido iniciada previamente

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "vendor/autoload.php";

require_once "config/config.php";

$url = !empty($_GET['url']) ? $_GET['url'] : 'login';

$arrUrl = explode('/', $url);
$controller = !empty($arrUrl[0]) ? strtolower($arrUrl[0]) : 'login';
$method = 'index';
if (isset($arrUrl[1]) && $arrUrl[1] != "") {
    $method = $arrUrl[1];
}
$params = !empty($arrUrl[2]) ? array_slice($arrUrl, 2) : [];


$publicRoutes = ['login', 'home', 'register', 'forgot-password', 'session_test', 'notificaciones'];


if (!in_array($controller, $publicRoutes)) {

    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

        header('Location: ' . base_url('login'));
        exit();
    }
}

$controllerFile = "app/Controllers/" . ucfirst($controller) . ".php";
if (!file_exists($controllerFile)) {
    $controllerFile = "app/Controllers/" . strtolower($controller) . ".php";
}

if (file_exists($controllerFile)) {
    require_once $controllerFile;

    $functionName = $controller . '_' . $method;
    error_log("🔍 Router - Controller: $controller, Method: $method, Function: $functionName, File: $controllerFile");

    if (function_exists($functionName)) {
        error_log("✅ Router - Function exists, calling it.");
        call_user_func_array($functionName, $params);
    } else {
        error_log("❌ Router - Function '$functionName' DOES NOT exist in " . get_included_files()[count(get_included_files()) - 1]);
        // Fallback or error
        if (file_exists("app/Controllers/Errors.php")) {
            require_once "app/Controllers/Errors.php";
            if (function_exists("errors_index")) {
                errors_index();
            } else {
                echo "Error: function errors_index not found";
            }
        } else {
            echo "Error: Controller file not found and Errors controller missing";
        }
    }
} else {
    if (file_exists("app/Controllers/Errors.php")) {
        require_once "app/Controllers/Errors.php";
        if (function_exists("errors_index")) {
            errors_index();
        }
    }
}