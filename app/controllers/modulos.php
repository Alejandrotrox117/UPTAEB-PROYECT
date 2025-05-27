<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Modulos extends Controllers
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
        $data['page_title'] = "Gestión de Módulos";
        $data['page_name'] = "Módulos";
        $data['page_functions_js'] = "functions_modulos.js";
        $this->views->getView($this, "modulos", $data);
    }

    public function getModulosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllModulosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $moduloData = [
                'titulo' => trim($data['titulo'] ?? ''),
                'descripcion' => trim($data['descripcion'] ?? '')
            ];

            if (empty($moduloData['titulo'])) {
                echo json_encode(['status' => false, 'message' => 'El título del módulo es obligatorio']);
                return;
            }

            $request = $this->model->insertModulo($moduloData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getModuloById(int $idmodulo)
    {
        if ($idmodulo > 0) {
            $arrData = $this->model->selectModuloById($idmodulo);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                $idModulo = intval($input['idmodulo'] ?? 0);
                if ($idModulo <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de módulo no válido']);
                    return;
                }

                $dataParaModelo = [
                    'titulo' => trim($input['titulo'] ?? ''),
                    'descripcion' => trim($input['descripcion'] ?? '')
                ];

                if (empty($dataParaModelo['titulo'])) {
                    echo json_encode(['status' => false, 'message' => 'El título del módulo es obligatorio']);
                    return;
                }

                $resultado = $this->model->updateModulo($idModulo, $dataParaModelo);
                
                echo json_encode($resultado);

            } catch (Exception $e) {
                error_log("Error en updateModulo: " . $e->getMessage());
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    public function deleteModulo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idmodulo = isset($data['idmodulo']) ? intval($data['idmodulo']) : 0;

            if ($idmodulo > 0) {
                $requestDelete = $this->model->deleteModuloById($idmodulo);
                if ($requestDelete) {
                    $response = ["status" => true, "message" => "Módulo desactivado correctamente."];
                } else {
                    $response = ["status" => false, "message" => "Error al desactivar el módulo."];
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                 $response = ["status" => false, "message" => "ID de módulo no válido."];
                 echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getControladores()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->getControlladoresDisponibles();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>
