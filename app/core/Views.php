<?php 
class Views{
    public function getView($controller,$view,$data=""){
    $controller = get_class($controller);
    //Validamos que el archivo inicial sea el inicio
        if($controller=="Home"){
            $view = "public/index.php";
        }else{
            //Si no es el inicio en el caso contrario sigue con otro controlador
            $view = "public/views/".$controller."/".$view.".php";
        }
        //requerimos el archivo
        require_once($view);
    }
}

    

?>