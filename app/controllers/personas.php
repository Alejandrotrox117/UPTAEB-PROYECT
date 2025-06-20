<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Personas extends Controllers
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
        $data['page_title'] = "Gestión de Personas";
        $data['page_name'] = "Personas";
        $data['page_functions_js'] = "functions_personas.js";
        $this->views->getView($this, "personas", $data);
    }

    public function getPersonasData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $arrData = $this->model->selectAllPersonasActivas();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function createPersona()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $personaData = [
                'nombre' => trim($data['persona']['nombre']),
                'apellido' => trim($data['persona']['apellido']),
                'cedula' => trim($data['persona']['identificacion']),
                'genero' => $data['persona']['genero'] ?? null,
                'fecha_nacimiento' => $data['persona']['fecha_nacimiento'] ?: null,
                'correo_electronico_persona' => trim($data['persona']['correo_electronico'] ?? ''), 
                'direccion' => trim($data['persona']['direccion'] ?? ''), 
                'observaciones' => trim($data['persona']['observaciones'] ?? ''),
                'telefono_principal' => trim($data['persona']['telefono_principal']),
                'crear_usuario' => $data['crear_usuario_flag'] ?? '0',
                'correo_electronico_usuario' => trim($data['usuario']['correo_login'] ?? ''), 
                'clave_usuario' => $data['usuario']['clave'] ?? '',
                'idrol_usuario' => $data['usuario']['idrol'] ?? null
            ];

            $request = $this->model->insertPersonaConUsuario($personaData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPersonaById(int $idpersona_pk)
    {
        if ($idpersona_pk > 0) {
            $arrData = $this->model->selectPersonaById($idpersona_pk);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                if (!empty($arrData['persona_fecha'])) {
                    $arrData['fecha_nacimiento_formato'] = date('Y-m-d', strtotime($arrData['persona_fecha']));
                } else {
                    $arrData['fecha_nacimiento_formato'] = '';
                }
                $response = ["status" => true, "data" => $arrData];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updatePersona()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $idPersona = intval($input['idpersona_pk'] ?? 0);
            if ($idPersona <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de persona no válido']);
                return;
            }

            
            $dataParaModelo = [];
            
            if (isset($input['persona'])) {
                $dataParaModelo = array_merge($dataParaModelo, [
                    'nombre' => trim($input['persona']['nombre'] ?? ''),
                    'apellido' => trim($input['persona']['apellido'] ?? ''),
                    'identificacion' => trim($input['persona']['identificacion'] ?? ''),
                    'genero' => trim($input['persona']['genero'] ?? ''),
                    'fecha_nacimiento' => trim($input['persona']['fecha_nacimiento'] ?? ''),
                    'correo_electronico_persona' => trim($input['persona']['correo_electronico'] ?? ''),
                    'direccion' => trim($input['persona']['direccion'] ?? ''),
                    'observaciones' => trim($input['persona']['observaciones'] ?? ''),
                    'telefono_principal' => trim($input['persona']['telefono_principal'] ?? ''),
                ]);
            }

            if (isset($input['actualizar_usuario_flag']) && $input['actualizar_usuario_flag'] == "1" && isset($input['usuario'])) {
                $dataParaModelo = array_merge($dataParaModelo, [
                    'actualizar_usuario' => "1",
                    'correo_electronico_usuario' => trim($input['usuario']['correo_electronico_usuario'] ?? ''),
                    'clave_usuario' => trim($input['usuario']['clave_usuario'] ?? ''),
                    'idrol_usuario' => trim($input['usuario']['idrol_usuario'] ?? ''),
                ]);
            }

            $resultado = $this->model->updatePersonaConUsuario($idPersona, $dataParaModelo);
            
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en updatePersona: " . $e->getMessage());
            echo json_encode([
                'status' => false, 
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    public function deletePersona()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idpersona_pk = isset($data['idpersona_pk']) ? intval($data['idpersona_pk']) : 0;

            if ($idpersona_pk > 0) {
                $requestDelete = $this->model->deletePersonaById($idpersona_pk);
                if ($requestDelete) {
                    $response = ["status" => true, "message" => "Persona desactivada correctamente."];
                } else {
                    $response = ["status" => false, "message" => "Error al desactivar la persona."];
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                 $response = ["status" => false, "message" => "ID de persona no válido."];
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
}
?>
