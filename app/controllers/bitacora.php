<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class bitacora extends Controllers
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

    // Vista principal para gestionar categorias
    public function index()
    {
        $data['page_title'] = "Bitacora";
        $data['page_name'] = "Bitacora";
        $data['page_functions_js'] = "functions_bitacora.js";
        $this->views->getView($this, "bitacora", $data);
    }

    // Obtener datos de categorias para DataTables
    public function getBitacoraData()
    {
        $arrData = $this->get_model()->SelectAllBitacora();

        $response = [
           
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    

    // Obtener una categoría por ID
    public function getBitacoraById($idcategoria)
    {
        try {
            $bitacora = $this->model->getBitacoraById($idcategoria);

            if ($bitacora) {
                echo json_encode(["status" => true, "data" => $bitacora]);
            } else {
                echo json_encode(["status" => false, "message" => "Bitacora no encontrada."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}