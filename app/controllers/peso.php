<?php


class Peso extends Controllers 
{
    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function peso() {
        $data['page_title'] = "Gestión de Peso";
        $data['page_name'] = "Peso";
        $this->views->getView($this, "peso", $data);
    }

    public function detalle($id) {
        echo "Detalle del peso con ID: " . $id;
    }
}