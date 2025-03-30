<?php
// filepath: app/controllers/peso.php

require_once "app/core/Controllers.php";
require_once "helpers\helpers.php";
class Peso extends Controllers
{
    public function __construct()
    {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function app()
    {
        // Implementación del método app
        //echo "Método app ejecutado correctamente.";
    }
    public function peso($params = null) {
        $data['page_id'] = 1;
        $data["page_title"] = "Pagina principal";
        $data["tag_page"] = "La pradera de pavia";
        $data["page_name"] = "Pesaje";
        
        // Verifica si hay parámetros
        if ($params) {
            echo "Parámetros recibidos: " . $params;
        }
    
        $this->views->getView($this, "peso", $data);
    }
}
?>