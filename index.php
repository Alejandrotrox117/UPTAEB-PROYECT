<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require_once "config/config.php"; 
require_once "app/core/Controllers.php";
require_once "vendor/autoload.php";
require_once "helpers/helpers.php";


$url = !empty($_GET['url']) ? $_GET['url'] : 'login'; 

$arrUrl = explode('/', $url);
$controller = !empty($arrUrl[0]) ? strtolower($arrUrl[0]) : 'login';
$method = 'index';
if (isset($arrUrl[1]) && $arrUrl[1] != "") {
    $method = $arrUrl[1];
}
$params = !empty($arrUrl[2]) ? array_slice($arrUrl, 2) : [];


$publicRoutes = ['login', 'home', 'register', 'forgot-password', 'session_test'];


if (!in_array($controller, $publicRoutes)) {
  
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
      
        header('Location: ' . base_url('login'));
        exit();
    }
}


$controllerFile = "app/controllers/" . ucfirst($controller) . ".php";
if (!file_exists($controllerFile)) {
    $controllerFile = "app/controllers/" . strtolower($controller) . ".php";
}

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClassName = ucfirst($controller);
    $controllerInstance = new $controllerClassName();

    if (method_exists($controllerInstance, $method)) {
        call_user_func_array(array($controllerInstance, $method), $params);
    } else {
        
        require_once "app/controllers/error.php";
        $errorController = new Errors();
        $errorController->index();
    }
} else {
   
    require_once "app/controllers/error.php";
    $errorController = new Errors();
    $errorController->index();
}
