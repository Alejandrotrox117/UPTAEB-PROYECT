<?php 
require_once __DIR__. "/Views.php";

    class Controllers
    {
        protected $model; // Agregamos una propiedad para almacenar el objeto del modelo
        public $views;
    
        public function __construct(){
            $this->load_model();
            $this->views = new Views();
        }
    
        public function load_model(){
            $model = get_class($this)."Model";
            //ruta del archivo
            $routeClass = "app/models/".$model.".php";
            //validamos si el archivo existe
            if(file_exists($routeClass)){
                require_once($routeClass);
                //instanciamos la clase
                $this->model = new $model(); // Asignamos la instancia del modelo a la propiedad $model
            } else {
                throw new Exception("El archivo del modelo no existe: ".$model);
            }
        }
    }
?>