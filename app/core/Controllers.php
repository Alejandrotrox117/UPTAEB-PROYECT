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
           
            $routeClass = "app/models/".$model.".php";
            
           
            if(!file_exists($routeClass)){
                $modelLower = strtolower(get_class($this))."Model.php";
                $routeClass = "app/models/".$modelLower;
            }
            
          
            if(file_exists($routeClass)){
                require_once($routeClass);
               
                $this->model = new $model();
            } else {
                throw new Exception("El archivo del modelo no existe: ".$model);
            }
        }
    }
?>