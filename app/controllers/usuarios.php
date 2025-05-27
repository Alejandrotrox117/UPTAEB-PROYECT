<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Usuarios extends Controllers
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
        $data['page_title'] = "Gestión de Usuarios";
        $data['page_name'] = "Usuarios";
        $data['page_functions_js'] = "functions_usuarios.js";
        $this->views->getView($this, "usuarios", $data);
    }

    public function getUsuariosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllUsuariosActivos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $usuarioData = [
                'usuario' => trim($data['usuario']),
                'clave' => $data['clave'],
                'correo' => trim($data['correo']),
                'idrol' => $data['idrol'],
                'personaId' => !empty($data['personaId']) ? trim($data['personaId']) : null
            ];

            $request = $this->model->insertUsuario($usuarioData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getUsuarioById(int $idusuario)
    {
        if ($idusuario > 0) {
            $arrData = $this->model->selectUsuarioById($idusuario);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                $idUsuario = intval($input['idusuario'] ?? 0);
                if ($idUsuario <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de usuario no válido']);
                    return;
                }

                $dataParaModelo = [
                    'usuario' => trim($input['usuario'] ?? ''),
                    'correo' => trim($input['correo'] ?? ''),
                    'idrol' => intval($input['idrol'] ?? 0),
                    'personaId' => !empty($input['personaId']) ? trim($input['personaId']) : null,
                    'clave' => trim($input['clave'] ?? '')
                ];

                $resultado = $this->model->updateUsuario($idUsuario, $dataParaModelo);
                
                echo json_encode($resultado);

            } catch (Exception $e) {
                error_log("Error en updateUsuario: " . $e->getMessage());
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    public function deleteUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idusuario = isset($data['idusuario']) ? intval($data['idusuario']) : 0;

            if ($idusuario > 0) {
                $requestDelete = $this->model->deleteUsuarioById($idusuario);
                if ($requestDelete) {
                    $response = ["status" => true, "message" => "Usuario desactivado correctamente."];
                } else {
                    $response = ["status" => false, "message" => "Error al desactivar el usuario."];
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                 $response = ["status" => false, "message" => "ID de usuario no válido."];
                 echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getRoles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllRoles();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPersonas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllPersonasActivas();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
?>
