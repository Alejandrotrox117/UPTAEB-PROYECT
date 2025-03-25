<?php
require_once "app/core/Controllers.php";

$url = !empty($_GET['url']) ? $_GET['url'] : 'home/home'; // URL
//echo "URL: $url<br>"; // Depuración

$arrUrl = explode('/', $url);
$controller = ucfirst($arrUrl[0]); // Controlador
$method = isset($arrUrl[1]) && $arrUrl[1] != "" ? $arrUrl[1] : 'index'; // Método
$params = [];

// Depuración
//echo "Controlador: $controller<br>";
//echo "Método: $method<br>";

// Obtener parámetros adicionales
if (isset($arrUrl[2]) && $arrUrl[2] != "") {
    for ($i = 2; $i < count($arrUrl); $i++) {
        $params[] = $arrUrl[$i];
    }
}
//echo "Parámetros: " . implode(', ', $params) . "<br>";

// Cargar el controlador
$controllerPath = "app/controllers/" . $controller . ".php";
//echo "Ruta del controlador: $controllerPath<br>";

if (file_exists($controllerPath)) {
    //echo "El controlador existe.<br>";
    require_once($controllerPath);
    $controllerObj = new $controller();

    // Llamar al método con parámetros
    if (method_exists($controllerObj, $method)) {
        //echo "Método $method encontrado en el controlador $controller.<br>";
        call_user_func_array([$controllerObj, $method], $params);
    } else {
        //echo "Método $method no encontrado en el controlador $controller.<br>";
    }
} else {
    echo "Controlador $controller no encontrado.<br>";
}
?>