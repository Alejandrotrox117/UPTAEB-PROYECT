<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
/* require_once "app/models/RolesModel.php"; // Asegúrate de tener esto */

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

        if (empty($data['nombre']) || empty($data['estatus']) || empty($data['descripcion'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        try {
            // Usar setters del modelo
            $this->model->setNombre($data['nombre']);
            $this->model->setDescripcion($data['descripcion']);
            $this->model->setEstatus($data['estatus']);

            $resultado = $this->model->guardarRol();

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Rol guardado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el rol.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el rol: ' . $e->getMessage()]);
        }
    }

    public function ConsultarRol()
    {
        header("Content-Type: application/json");

        // Aquí puedes obtener el rol del usuario desde la sesión o algún parámetro
        $userRole = $_SESSION['usuario']['rol_id'] ?? 1; // Por defecto 1 si no existe

        $roles = $this->model->getRoles($userRole);
        echo json_encode($roles);
    }

    public function consultarunrol()
    {
        header("Content-Type: application/json");

        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        $rol = $this->model->getRolById($_GET['id']);
        echo json_encode($rol ?: ['success' => false, 'message' => 'Rol no encontrado.']);
    }

    public function eliminar()
    {
        header("Content-Type: application/json");

        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        $result = $this->model->eliminarRol($_GET['id']);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Rol eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el rol.']);
        }
    }
}
?>
