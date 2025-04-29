<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Roles extends Controllers
{
    protected $model;
    public function __construct()
    {
        parent::__construct();

        $this->model = new RolesModel();

    }

    public function index()
    {
        $this->views->getView($this, "roles");
    }

    public function guardarRol()
    {
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: Content-Type");

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Error al procesar los datos JSON: ' . json_last_error_msg()]);
            return;
        }

        // Validación de los datos recibidos
        if (empty($data['nombre']) || empty($data['estatus']) || empty($data['descripcion'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        // Intentar insertar los datos en la base de datos
        try {
            $resultado = $this->model->guardarRol();
            echo json_encode(['success' => true, 'message' => 'Rol guardado correctamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el rol: ' . $e->getMessage()]);
        }
    }


    public function ConsultarRol()
    {
        echo $this->model->get_Roles();
    }

    public function consultarunrol()
    {
        echo $this->model->rol();
    }



}
?>