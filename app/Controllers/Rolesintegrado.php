<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\RolesintegradoModel;
use Exception;

class Rolesintegrado extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new RolesintegradoModel();
    }

    public function index()
    {
        try {
            error_log("RolesIntegrado::index - Iniciando");
            
            $data['page_title'] = "Gestión Integral de Permisos";
            $data['page_name'] = "Roles Integrado";
            $data['page_functions_js'] = "functions_rolesintegrado.js";
            
            error_log("RolesIntegrado::index - Datos preparados, cargando vista");
            $this->views->getView($this, "rolesintegrado", $data);
            
        } catch (Exception $e) {
            error_log("RolesIntegrado::index - Error: " . $e->getMessage());
            throw $e;
        }
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