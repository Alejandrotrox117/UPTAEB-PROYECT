<?php 
require_once "app/core/Controllers.php";

    $url = !empty($_GET['url']) ? $_GET['url'] : 'home/home'; // URL
    $arrUrl = explode('/', $url);
    $controller = ucfirst($arrUrl[0]); // Controlador (primera parte de la URL)
    $method = isset($arrUrl[1]) && $arrUrl[1] != "" ? $arrUrl[1] : 'index'; // Método
    $params = [];

    // Obtener parámetros si los hay
    if (isset($arrUrl[2]) && $arrUrl[2] != "") {
        for ($i = 2; $i < count($arrUrl); $i++) {
            $params[] = $arrUrl[$i];
        }
    }

    // Cargar el controlador
    $controllerPath = "app/controllers/" . $controller . ".php";
    if (file_exists($controllerPath)) {
        require_once($controllerPath);
        $controllerObj = new $controller();

        // Llamar al método con parámetros
        if (method_exists($controllerObj, $method)) {
            call_user_func_array([$controllerObj, $method], $params);
        } else {
            echo "Método $method no encontrado en el controlador $controller.";
        }
    } else {
        echo "Controlador $controller no encontrado.";
    }
?>
