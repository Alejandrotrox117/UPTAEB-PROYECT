<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class Compras extends Controllers 
{
    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function compras() {
        $data['page_title'] = "Gestión de compras";
        $data['page_name'] = "Compra de materiales";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "compras", $data);
    }

   
}