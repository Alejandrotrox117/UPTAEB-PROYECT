<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/produccionModel.php"; // Ajusta la ruta según tu estructura

class Produccion extends Controllers
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
        // Inicializa el modelo correspondiente
        $this->set_model(new produccionModel());
    }

    // Método para mostrar la vista principal de gestión de producción
    public function index()
    {
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }

    // Método para obtener datos de producciones para DataTables
    public function getProduccionData()
    {
        $arrData = $this->get_model()->SelectAllProducciones();

        $response = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
       
    }

    // Método para registrar una nueva producción
    public function createProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                "idempleado" => $_POST['idempleado'],
                "idproducto" => $_POST['idproducto'],
                "cantidad_a_realizar" => $_POST['cantidad_a_realizar'],
                "fecha_inicio" => $_POST['fecha_inicio'],
                "fecha_fin" => $_POST['fecha_fin'] ?? null,
                "estado" => $_POST['estado']
            ];

            // Insertar la producción en la base de datos
            $result = $this->get_model()->insertProduccion($data);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Producción registrada exitosamente"]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la producción"]);
            }
        }
    }

    // Método para actualizar una producción existente
    public function updateProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idproduccion = $_POST['idproduccion'];
            $data = [
                "idempleado" => $_POST['idempleado'],
                "idproducto" => $_POST['idproducto'],
                "cantidad_a_realizar" => $_POST['cantidad_a_realizar'],
                "fecha_inicio" => $_POST['fecha_inicio'],
                "fecha_fin" => $_POST['fecha_fin'] ?? null,
                "estado" => $_POST['estado'],
                "fecha_modificacion" => date("Y-m-d H:i:s") // Fecha actual
            ];

            // Actualizar la producción en la base de datos
            $result = $this->get_model()->updateProduccion($data);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Producción actualizada exitosamente"]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar la producción"]);
            }
        }
    }

    // Método para eliminar una producción
    public function deleteProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idproduccion = $_POST['idproduccion'];

            // Eliminar la producción de la base de datos
            $result = $this->get_model()->deleteProduccion($idproduccion);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Producción eliminada exitosamente"]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al eliminar la producción"]);
            }
        }
    }

    // Método para cambiar el estado de una producción
    public function cambiarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idproduccion = $_POST['idproduccion'];
            $estado = $_POST['estado'];

            // Cambiar el estado de la producción
            $result = $this->get_model()->updateProduccion([
                "idproduccion" => $idproduccion,
                "estado" => $estado,
                "fecha_modificacion" => date("Y-m-d H:i:s") // Fecha actual
            ]);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Estado actualizado exitosamente"]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar el estado"]);
            }
        }
    }
}