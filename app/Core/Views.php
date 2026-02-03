<?php
namespace App\Core;

class Views{
    public function getView($controller,$view,$data=""){
        // Soporte para controladores funcionales (cuando $controller es null)
        if ($controller === null) {
            // Inferir el folder y view del parámetro $view
            $folder = $view;
            $view = "app/views/".$view."/".$view.".php";
        } else {
            // Comportamiento original para controladores de clase
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
        }
        
        // Extraer el array $data para que las variables estén disponibles en la vista
        if (is_array($data) && !empty($data)) {
            extract($data);
        }
        
        require_once($view);
    }
}

    

?>