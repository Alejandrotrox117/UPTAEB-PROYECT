<?php 
    //load
    $controller = ucwords($controller);
    $controllerFile = 'controllers/'.$controller.'.php';
    //validar si existe el controlador
    if(file_exists($controllerFile)){
        require_once($controllerFile);
        //instanciar el controlador
        $controller = new $controller();
        //validar si existe el metodo y recibe un parametro 
        if(method_exists($controller, $method)){
            $controller->{$method}($params);
        }else{
            require_once("controllers/error.php");
        }
    }else{ 
        require_once("controllers/error.php");
    }
?>