<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class Romana extends Controllers
{
    public function set_model($model)
    {
        $this->model = $model;
    }

    public function get_model()
    {
        return $this->model;
    }

    public function __construct()
    {
        parent::__construct();
    }

    // Vista principal para gestionar productos
    public function index()
    {
        $data['page_title'] = "Gestión de Romanas";
        $data['page_name'] = "Romana";
        $data['page_functions_js'] = "functions_romana.js";
        $this->views->getView($this, "romana", $data);
    }

    // Obtener datos de Pesos de Romana para DataTables
    public function getRomanaData()
    {
        $arrData = $this->get_model()->SelectAllRomana();

        $response = [
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

}