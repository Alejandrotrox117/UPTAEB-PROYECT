<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class RolesAsignaciones extends Controllers
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
        $data['page_title'] = "Gestión de Asignaciones de Roles";
        $data['page_name'] = "Asignaciones de Roles";
        $data['page_functions_js'] = "functions_rolesasignaciones.js";
        $this->views->getView($this, "rolesasignaciones", $data);
    }

    // ==================== OBTENER DATOS PARA SELECTORES ====================
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

    public function getPermisos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllPermisos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ==================== OBTENER ASIGNACIONES POR ROL ====================
    public function getAsignacionesByRol(int $idrol)
    {
        if ($idrol > 0) {
            $arrData = $this->model->selectAsignacionesCompletas($idrol);
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'ID de rol no válido'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getModulosByRol(int $idrol)
    {
        if ($idrol > 0) {
            $arrData = $this->model->selectModulosByRol($idrol);
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'ID de rol no válido'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPermisosByRol(int $idrol)
    {
        if ($idrol > 0) {
            $arrData = $this->model->selectPermisosByRol($idrol);
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'ID de rol no válido'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ==================== GUARDAR ASIGNACIONES ====================
    public function guardarAsignaciones()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                $idrol = intval($data['idrol'] ?? 0);
                $modulos = $data['modulos'] ?? [];
                $permisos = $data['permisos'] ?? [];

                if ($idrol <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Debe seleccionar un rol válido']);
                    return;
                }

                // Validar que los módulos sean números enteros
                $modulosValidos = array_filter(array_map('intval', $modulos), function($id) {
                    return $id > 0;
                });

                // Validar que los permisos sean números enteros
                $permisosValidos = array_filter(array_map('intval', $permisos), function($id) {
                    return $id > 0;
                });

                $request = $this->model->asignarModulosYPermisos($idrol, $modulosValidos, $permisosValidos);
                echo json_encode($request, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en guardarAsignaciones: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }
}
?>