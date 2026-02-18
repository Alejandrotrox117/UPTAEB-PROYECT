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

$functionalControllers = ['login', 'usuarios', 'roles', 'modulos', 'rolesintegrado', 'notificacionesconfig'];

$controllerFile = "app/Controllers/" . ucfirst($controller) . ".php";
if (!file_exists($controllerFile)) {
    $controllerFile = "app/Controllers/" . strtolower($controller) . ".php";
}

if (file_exists($controllerFile)) {
    
    if (in_array($controller, $functionalControllers)) {
        require_once $controllerFile;
        
        $functionName = $controller . '_' . $method;
        
        if (function_exists($functionName)) {
            call_user_func_array($functionName, $params);
        } else {
            $errorController = new \App\Controllers\Errors();
            $errorController->index();
        }
    } else {
        $controllerClassName = "App\\Controllers\\" . ucfirst(strtolower($controller));
        
        if (!class_exists($controllerClassName)) {
            $controllerClassName = "App\\Controllers\\" . $controller;
        }
        
        if (!class_exists($controllerClassName)) {
            $controllerClassName = "App\\Controllers\\" . strtolower($controller);
        }
        
        if (class_exists($controllerClassName)) {
            $controllerInstance = new $controllerClassName();

            if (method_exists($controllerInstance, $method)) {
                call_user_func_array(array($controllerInstance, $method), $params);
            } else {
                $errorController = new \App\Controllers\Errors();
                $errorController->index();
            }
        } else {
            $errorController = new \App\Controllers\Errors();
            $errorController->index();
        }
    }
} else {
    $errorController = new \App\Controllers\Errors();
    $errorController->index();
}