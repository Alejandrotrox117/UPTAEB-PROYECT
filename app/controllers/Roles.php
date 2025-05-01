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

    $userRole = $_SESSION['usuario']['rol_id'] ?? 1;
    $roles = $this->model->getRoles($userRole);

    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
}


public function consultarunrol()
{
    header("Content-Type: application/json");

    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
        return;
    }

    $rol = $this->model->getRolById($_GET['id']);

    if ($rol) {
        echo json_encode(['success' => true, 'rol' => $rol]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rol no encontrado.']);
    }
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



    public function actualizar()
    {
        header("Content-Type: application/json");
    
        // Recibir y decodificar los datos JSON del cuerpo de la solicitud
        $data = json_decode(file_get_contents('php://input'), true);
    
        // Validación de los datos recibidos
        if (!isset($data['id'], $data['nombre'], $data['estatus'], $data['descripcion'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            return;
        }
    
        // Validación de campos vacíos
        $nombre = trim($data['nombre']);
        $descripcion = trim($data['descripcion']);
        if (empty($nombre) || empty($descripcion)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            return;
        }
    
        // Validación de nombre (solo letras y espacios permitidos)
        $soloLetrasRegex = '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/';
        if (!preg_match($soloLetrasRegex, $nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol solo debe contener letras.']);
            return;
        }
    
        // Validación de descripción (solo letras, números, espacios, punto y coma, coma, y punto)
        $descripcionRegex = '/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9.,\s]+$/';
        if (!preg_match($descripcionRegex, $descripcion)) {
            echo json_encode(['success' => false, 'message' => 'La descripción solo puede contener letras, números, espacios, punto y coma.']);
            return;
        }
    
        // Usar los métodos SET para asignar los valores de los datos recibidos
        $this->model->setIdrol($data['id']);           // Asignar el id del rol
        $this->model->setNombre($nombre);              // Asignar el nombre del rol
        $this->model->setEstatus($data['estatus']);    // Asignar el estatus del rol
        $this->model->setDescripcion($descripcion);    // Asignar la descripción del rol
    
        // Llamar al método actualizarrol() para realizar la actualización en la base de datos
        $resultado = $this->model->actualizarrol();
    
        // Verificar si la actualización fue exitosa y enviar la respuesta
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Rol actualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
        }
    }
    

}
?>
