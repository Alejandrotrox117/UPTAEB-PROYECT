<?php 
class Views{
    public function getView($controller,$view,$data=""){
    $controller = get_class($controller);
    //Validamos que el archivo inicial sea el inicio
        if($controller=="Home"){
            $view = "app/views/home/home.php";
        }else{
            //Si no es el inicio en el caso contrario sigue con otro controlador
            $view = "app/views/".$controller."/".$view.".php";
        }
        //requerimos el archivo
        require_once($view);
    }
}

    

?>