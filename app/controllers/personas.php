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

            // Validaciones básicas en el controlador
            if (empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula']) || empty($data['telefono_principal'])) {
                $response = ["status" => false, "message" => "Campos obligatorios (nombre, apellido, cédula, teléfono) no pueden estar vacíos."];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }
            if (isset($data['crear_usuario']) && $data['crear_usuario'] == '1') {
                if (empty($data['correo_electronico']) || empty($data['clave']) || empty($data['rol'])) {
                     $response = ["status" => false, "message" => "Para crear usuario, se requiere correo, clave y rol."];
                     echo json_encode($response, JSON_UNESCAPED_UNICODE);
                     die();
                }
            }

            $personaData = [
                'nombre' => trim($data['nombre']),
                'apellido' => trim($data['apellido']),
                'cedula' => trim($data['cedula']), // Este es el que se usará como 'identificacion' en tabla personas y 'personaId' en tabla usuario
                'rif' => trim($data['rif'] ?? ''),
                'genero' => $data['genero'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
                'correo_electronico_persona' => trim($data['correo_electronico_persona'] ?? ''), // Correo informativo de la persona
                'direccion' => trim($data['direccion'] ?? ''), // Si tienes este campo en el form
                'estado_residencia' => trim($data['estado'] ?? ''),
                'ciudad_residencia' => trim($data['ciudad'] ?? ''),
                'pais_residencia' => trim($data['pais'] ?? ''),
                'tipo_persona' => $data['tipo'] ?? null,
                'observaciones' => trim($data['observaciones'] ?? ''),
                'telefono_principal' => trim($data['telefono_principal']),
                'crear_usuario' => $data['crear_usuario'] ?? '0',
                'correo_electronico_usuario' => trim($data['correo_electronico'] ?? ''), // Correo para login y tabla usuario
                'clave_usuario' => $data['clave'] ?? '',
                'idrol_usuario' => $data['rol'] ?? null
            ];

            $request = $this->model->insertPersonaConUsuario($personaData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPersonaById(int $idpersona_pk) // Recibe el PK de la tabla personas
    {
        if ($idpersona_pk > 0) {
            $arrData = $this->model->selectPersonaById($idpersona_pk);
            if (empty($arrData)) {
                $response = ["status" => false, "message" => "Datos no encontrados."];
            } else {
                if (!empty($arrData['persona_fecha'])) { // Ajustado al alias del modelo
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (empty($data['idpersona_pk']) || empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula']) || empty($data['telefono_principal'])) {
                $response = ["status" => false, "message" => "ID y campos obligatorios no pueden estar vacíos."];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }

            $personaData = [
                'idpersona_pk' => intval($data['idpersona_pk']), // PK de la tabla personas
                'nombre' => trim($data['nombre']),
                'apellido' => trim($data['apellido']),
                'cedula' => trim($data['cedula']), // Nueva cédula
                'cedula_original' => trim($data['cedula_original'] ?? $data['cedula']), // Cédula original para buscar usuario
                'rif' => trim($data['rif'] ?? ''),
                'genero' => $data['genero'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
                'correo_electronico_persona' => trim($data['correo_electronico_persona'] ?? ''),
                'direccion' => trim($data['direccion'] ?? ''),
                'estado_residencia' => trim($data['estado'] ?? ''),
                'ciudad_residencia' => trim($data['ciudad'] ?? ''),
                'pais_residencia' => trim($data['pais'] ?? ''),
                'tipo_persona' => $data['tipo'] ?? null,
                'observaciones' => trim($data['observaciones'] ?? ''),
                'telefono_principal' => trim($data['telefono_principal']),
                'correo_electronico_usuario' => trim($data['correo_electronico_usuario'] ?? ''),
                'clave_usuario' => $data['clave_usuario'] ?? '',
                'idrol_usuario' => $data['idrol_usuario'] ?? null
            ];

            $request = $this->model->updatePersonaConUsuario($personaData);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function deletePersona()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $json = file_get_contents('php://input');
             $data = json_decode($json, true);
             $idpersona_pk = isset($data['idpersona_pk']) ? intval($data['idpersona_pk']) : 0; // PK de la tabla personas

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
}
?>
