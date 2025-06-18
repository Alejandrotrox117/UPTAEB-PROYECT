<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class RolesIntegrado extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['page_title'] = "Gestión Integral de Permisos";
        $data['page_name'] = "Roles Integrado";
        $data['page_functions_js'] = "functions_rolesintegrado.js";
        $this->views->getView($this, "rolesintegrado", $data);
    }

    public function getRoles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRoles();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getModulosDisponibles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllModulosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPermisosDisponibles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllPermisosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getAsignacionesRol(int $idrol)
    {
        if ($idrol > 0) {
            $arrData = $this->model->selectAsignacionesRolCompletas($idrol);
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
        }
        die();
    }

    public function guardarAsignacionesCompletas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $request = $this->model->guardarAsignacionesRolCompletas($data);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>