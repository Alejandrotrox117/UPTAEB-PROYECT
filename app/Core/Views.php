<?php
namespace App\Core;

class Views{
    public function getView($controller,$view,$data=""){
    $controllerClass = get_class($controller);
    $controller = str_replace('App\\Controllers\\', '', $controllerClass);
    
        if($controller=="Home"){
            $view = "app/views/home/home.php";
        }else{
            if($controller == "Errors"){
                $folder = "Errors";
            }else{
                $folder = strtolower($controller);
            }
            $view = "app/views/".$folder."/".$view.".php";
        }
        require_once($view);
    }
}

    

?>