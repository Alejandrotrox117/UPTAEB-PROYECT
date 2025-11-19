<?php
namespace App\Core;

class Controllers
    {
        protected $model; // Agregamos una propiedad para almacenar el objeto del modelo
        public $views;
    
        public function __construct(){
            $this->load_model();
            $this->views = new Views();
        }
    
        public function load_model(){
            $className = get_class($this);
            $baseClassName = str_replace('App\\Controllers\\', '', $className);
            $modelClassName = "App\\Models\\" . $baseClassName . "Model";
            
            if(class_exists($modelClassName)){
                $this->model = new $modelClassName();
            } else {
                $modelClassNameLower = "App\\Models\\" . strtolower($baseClassName) . "Model";
                if(class_exists($modelClassNameLower)){
                    $this->model = new $modelClassNameLower();
                }
            }
        }
    }
?>