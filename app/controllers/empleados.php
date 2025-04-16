<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class empleados extends Controllers 
{


     // Método setter para establecer el valor de $model
     public function set_model($model)
     {
         $this->model = $model;
     }
 
     public function get_model()
     {
         return $this->model;
     }

    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function index() {
        $data['page_title'] = "Gestión de empleados temporales";
        $data['page_name'] = "Empleados Temporales";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "empleados", $data);
    }

   

}