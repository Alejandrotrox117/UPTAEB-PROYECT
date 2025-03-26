<?php

class Peso extends Controllers {
    public function __construct() {
        parent::__construct();
    }

    public function peso() {
        $data['page_title'] = "Gestión de Peso";
        $data['page_name'] = "Peso";
        $this->views->getView($this, "peso", $data);
    }

    public function detalle($id) {
        $data['page_title'] = "Detalle de Peso";
        $data['page_name'] = "Detalle Peso";
        $data['id'] = $id;
        $this->views->getView($this, "detalle", $data);
    }
}