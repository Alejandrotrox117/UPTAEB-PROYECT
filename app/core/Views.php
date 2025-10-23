<?php 
class Views{
    public function getView($controller,$view,$data=""){
    $controller = get_class($controller);
    //Validamos que el archivo inicial sea el inicio
        if($controller=="Home"){
            $view = "app/views/home/home.php";
        }else{
            // Handle special cases for folder names that don't match class names
            if($controller == "Errors"){
                $folder = "Errors";
            }else{
                $folder = strtolower($controller);
            }
            //Si no es el inicio en el caso contrario sigue con otro controlador
            $view = "app/views/".$folder."/".$view.".php";
        }
        //requerimos el archivo
        require_once($view);
    }
}

    

?>