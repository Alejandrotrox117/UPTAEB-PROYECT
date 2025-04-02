<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class Compras extends Controllers 
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

    public function compras() {
        $data['page_title'] = "Gestión de compras";
        $data['page_name'] = "Compra de materiales";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "compras", $data);
    }


    public function app()
    {
        $arrData = $this->model->selectCompras();
        for ($i = 0; $i < count($arrData); $i++) {
            // $arrData[$i]['options'] = '<div class="text-center"> 
            //     <button class="btn btn-primary btn-sm btnEditCompras" onClick="fntEditCompras(' . $arrData[$i]['id'] . ')" title="Editar"><i class="fas fa-pencil-alt"></i></button>
            //     <button class="btn btn-danger btn-sm btnDelCompras" onClick="fntDelCompras(' . $arrData[$i]['id'] . ')" title="Eliminar"><i class="far fa-trash-alt"></i></button>
            // </div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        
    }

   
}