<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class RolesModulos extends Controllers
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

    public function index()
    {
        $data['page_title'] = "Gestión de Roles y Módulos";
        $data['page_name'] = "Roles y Módulos";
        $data['page_functions_js'] = "functions_rolesmodulos.js";
        $this->views->getView($this, "rolesmodulos", $data);
    }

    public function getRolesModulosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRolesModulosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createRolModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');//input');
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $rolModuloData = [
                'idrol' => intval($data['idrol'] ?? 0),
                'idmodulo' => intval($data['idmodulo'] ?? 0)
            ];

            if ($rolModuloData['idrol'] <= 0) {
                echo json_encode(['status' => false, 'message' => 'Debe seleccionar un rol válido']);
                return;
            }

            if ($rolModuloData['idmodulo'] <= 0) {
                echo json_encode(['status' => false, 'message' => 'Debe seleccionar un módulo válido']);
                return;
            }

            $request = $this->model->insertRolModulo($rolModuloData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getRolModuloById(int $idrolmodulo)
    {
        if ($idrolmodulo > 0) {
            $arrData = $this->model->selectRolModuloById($idrolmodulo);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateRolModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                $idRolModulo = intval($input['idrolmodulo'] ?? 0);
                if ($idRolModulo <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de rol-módulo no válido']);
                    return;
                }

                $dataParaModelo = [
                    'idrol' => intval($input['idrol'] ?? 0),
                    'idmodulo' => intval($input['idmodulo'] ?? 0)
                ];

                if ($dataParaModelo['idrol'] <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Debe seleccionar un rol válido']);
                    return;
                }

                if ($dataParaModelo['idmodulo'] <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Debe seleccionar un módulo válido']);
                    return;
                }

                $resultado = $this->model->updateRolModulo($idRolModulo, $dataParaModelo);
                
                echo json_encode($resultado);

            } catch (Exception $e) {
                error_log("Error en updateRolModulo: " . $e->getMessage());
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    public function deleteRolModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idrolmodulo = isset($data['idrolmodulo']) ? intval($data['idrolmodulo']) : 0;

            if ($idrolmodulo > 0) {
                $requestDelete = $this->model->deleteRolModuloById($idrolmodulo);
                if ($requestDelete) {
                    $response = ["status" => true, "message" => "Asignación eliminada correctamente."];
                } else {
                    $response = ["status" => false, "message" => "Error al eliminar la asignación."];
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                 $response = ["status" => false, "message" => "ID de asignación no válido."];
                 echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getRoles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRolesActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getModulos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllModulosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>