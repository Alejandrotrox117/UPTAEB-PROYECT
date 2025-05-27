<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class RolesPermisos extends Controllers
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
        $data['page_title'] = "Gestión de Roles y Permisos";
        $data['page_name'] = "Roles y Permisos";
        $data['page_functions_js'] = "functions_rolespermisos.js";
        $this->views->getView($this, "rolespermisos", $data);
    }

    public function getRolesPermisosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRolesPermisosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createRolPermiso()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $rolPermisoData = [
                'idrol' => intval($data['idrol'] ?? 0),
                'idpermiso' => intval($data['idpermiso'] ?? 0)
            ];

            if ($rolPermisoData['idrol'] <= 0) {
                echo json_encode(['status' => false, 'message' => 'Debe seleccionar un rol válido']);
                return;
            }

            if ($rolPermisoData['idpermiso'] <= 0) {
                echo json_encode(['status' => false, 'message' => 'Debe seleccionar un permiso válido']);
                return;
            }

            $request = $this->model->insertRolPermiso($rolPermisoData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getRolPermisoById(int $idrolpermiso)
    {
        if ($idrolpermiso > 0) {
            $arrData = $this->model->selectRolPermisoById($idrolpermiso);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateRolPermiso()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                $idRolPermiso = intval($input['idrolpermiso'] ?? 0);
                if ($idRolPermiso <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de rol-permiso no válido']);
                    return;
                }

                $dataParaModelo = [
                    'idrol' => intval($input['idrol'] ?? 0),
                    'idpermiso' => intval($input['idpermiso'] ?? 0)
                ];

                if ($dataParaModelo['idrol'] <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Debe seleccionar un rol válido']);
                    return;
                }

                if ($dataParaModelo['idpermiso'] <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Debe seleccionar un permiso válido']);
                    return;
                }

                $resultado = $this->model->updateRolPermiso($idRolPermiso, $dataParaModelo);
                
                echo json_encode($resultado);

            } catch (Exception $e) {
                error_log("Error en updateRolPermiso: " . $e->getMessage());
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    public function deleteRolPermiso()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idrolpermiso = isset($data['idrolpermiso']) ? intval($data['idrolpermiso']) : 0;

            if ($idrolpermiso > 0) {
                $requestDelete = $this->model->deleteRolPermisoById($idrolpermiso);
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

    public function getPermisos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllPermisos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>
