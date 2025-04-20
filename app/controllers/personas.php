<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class personas extends Controllers
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

        $arrData = $this->get_model()->SelectAllPersonas();


        $response = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function createPersona()
    {
        try {
            $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
            $data = json_decode($json, true); // Decodifica los datos JSON
    
            // Depuración: Imprime los datos recibidos
            error_log(print_r($data, true));
    
            // Validar que los datos no sean nulos
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }
    
            // Extraer los datos del JSON
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $cedula = trim($data['cedula'] ?? '');
            $rif = trim($data['rif'] ?? '');
            $tipo = trim($data['tipo'] ?? '');
            $genero = trim($data['genero'] ?? '');
            $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
            $telefono_principal = trim($data['telefono_principal'] ?? '');
            $correo_electronico = trim($data['correo_electronico'] ?? '');
            $direccion = trim($data['direccion'] ?? '');
            $ciudad = trim($data['ciudad'] ?? '');
            $estado = trim($data['estado'] ?? '');
            $pais = trim($data['pais'] ?? '');
            $estatus = trim($data['estatus'] ?? '');
    
            // Validar campos obligatorios
            if (empty($nombre) || empty($apellido) || empty($cedula)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }
    
            // Insertar los datos usando el modelo
            $insertData = $this->model->insertPersona([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "cedula" => $cedula,
                "rif" => $rif,
                "tipo" => $tipo,
                "genero" => $genero,
                "fecha_nacimiento" => $fecha_nacimiento,
                "telefono_principal" => $telefono_principal,
                "correo_electronico" => $correo_electronico,
                "direccion" => $direccion,
                "ciudad" => $ciudad,
                "estado" => $estado,
                "pais" => $pais,
                "estatus" => $estatus,
            ]);
    
            // Respuesta al cliente
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "Persona registrada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la persona. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
    public function deletePersona()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
        $data = json_decode($json, true); // Decodifica los datos JSON

        // Extraer el ID de la persona a desactivar
        $idpersona = trim($data['idpersona']) ?? null;

        // Validar que el ID no esté vacío
        if (empty($idpersona)) {
            $response = ["status" => false, "message" => "ID de persona no proporcionado."];
            echo json_encode($response);
            return;
        }

        // Desactivar la persona usando el modelo
        $deleteData = $this->get_model()->deletePersona($idpersona);

        // Respuesta al cliente
        if ($deleteData) {
            $response = ["status" => true, "message" => "Persona desactivada correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al desactivar la persona. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function updatePersona()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por la vista
        $data = json_decode($json, true); // datos del json lo decodifica 

        // Extraer los datos del JSON
        $idpersona = trim($data['idpersona']) ?? null;
        $nombre = trim($data['nombre']) ?? null;
        $apellido = trim($data['apellido']) ?? null;
        $cedula = trim($data['cedula']) ?? null;
        $rif = trim($data['rif']) ?? null;
        $tipo = trim($data['tipo']) ?? null;
        $genero = trim($data['genero']) ?? null;
        $fecha_nacimiento = trim($data['fecha_nacimiento']) ?? null;
        $telefono_principal = trim($data['telefono_principal']) ?? null;
        $correo_electronico = trim($data['correo_electronico']) ?? null;
        $direccion = trim($data['direccion']) ?? null;
        $ciudad = trim($data['ciudad']) ?? null;
        $estado = trim($data['estado']) ?? null;
        $pais = trim($data['pais']) ?? null;
        $estatus = trim($data['estatus']) ?? null;

        // Validar campos obligatorios
        if (empty($idpersona) || empty($nombre) || empty($apellido) || empty($cedula)) {
            $response = ["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."];
            echo json_encode($response);
            return;
        }

        // Validar formato del correo electrónico
        if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $response = ["status" => false, "message" => "El correo electrónico no es válido."];
            echo json_encode($response);
            return;
        }


        $updateData = $this->get_model()->updatePersona([
            "idpersona" => $idpersona,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "cedula" => $cedula,
            "rif" => $rif,
            "tipo" => $tipo,
            "genero" => $genero,
            "fecha_nacimiento" => $fecha_nacimiento,
            "telefono_principal" => $telefono_principal,
            "correo_electronico" => $correo_electronico,
            "direccion" => $direccion,
            "ciudad" => $ciudad,
            "estado" => $estado,
            "pais" => $pais,
            "estatus" => $estatus,
        ]);

        // Respuesta al cliente
        if ($updateData) {
            $response = ["status" => true, "message" => "Persona actualizada correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al actualizar la persona. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function getPersonaById($idpersona)
    {
        try {

            $persona = $this->get_model()->getPersonaById($idpersona);

            if ($persona) {
                echo json_encode(["status" => true, "data" => $persona]);
            } else {
                echo json_encode(["status" => false, "message" => "Persona no encontrada."]);
            }
        } catch (Exception $e) {

            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}