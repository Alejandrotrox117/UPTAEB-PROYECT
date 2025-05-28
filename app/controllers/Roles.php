<?php
require_once "app/core/Controllers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/helpers.php";

class Roles extends Controllers
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
        // Verificar acceso al módulo
        permisosVerificar::verificarAccesoModulo('Roles');
    }

    public function index()
    {
        // Obtener permisos del usuario para este módulo
        $idUsuario = $_SESSION['usuario_id'];
        $permisos = PermisosHelper::getPermisosDetalle($idUsuario, 'Roles');

        $data['page_title'] = "Gestión de Roles";
        $data['page_name'] = "Roles";
        $data['page_functions_js'] = "functions_roles.js";
        $data['permisos'] = $permisos;
        
        $this->views->getView($this, "roles", $data);
    }

    public function getRolesData()
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver roles',
                'data' => []
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRolesActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createRol()
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'crear')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para crear roles'
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $rolData = [
                'nombre' => trim($data['nombre']),
                'descripcion' => trim($data['descripcion']),
                'estatus' => $data['estatus'] ?? 'ACTIVO'
            ];

            $request = $this->model->insertRol($rolData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getRolById(int $idrol)
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver detalles de roles'
            ]);
            exit();
        }

        if ($idrol > 0) {
            $arrData = $this->model->selectRolById($idrol);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateRol()
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'editar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para editar roles'
            ]);
            exit();
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $idRol = intval($input['idrol'] ?? 0);
            if ($idRol <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                return;
            }

            $dataParaModelo = [
                'nombre' => trim($input['nombre'] ?? ''),
                'descripcion' => trim($input['descripcion'] ?? ''),
                'estatus' => trim($input['estatus'] ?? ''),
            ];

            $resultado = $this->model->updateRol($idRol, $dataParaModelo);
            
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en updateRol: " . $e->getMessage());
            echo json_encode([
                'status' => false, 
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    public function deleteRol()
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'eliminar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para eliminar roles'
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idrol = isset($data['idrol']) ? intval($data['idrol']) : 0;

            if ($idrol > 0) {
                $requestDelete = $this->model->deleteRolById($idrol);
                if ($requestDelete['status']) {
                    $response = ["status" => true, "message" => $requestDelete['message']];
                } else {
                    $response = ["status" => false, "message" => $requestDelete['message']];
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                 $response = ["status" => false, "message" => "ID de rol no válido."];
                 echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getAllRoles()
    {
        if (!permisosVerificar::verificarPermisoAccion('Roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver roles',
                'data' => []
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRolesActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>
