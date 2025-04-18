<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class personas extends Controllers 
{


     // Método setter para establecer el valor de $model
     public function set_model($model)
     {
         $this->model = $model;
     }
 
     public function get_model()
     {
         return $this->model;
     }

    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function index() {
        $data['page_title'] = "Gestión de Personas";
        $data['page_name'] = "Personas";
        $data['page_functions_js'] = "functions_personas.js";
        $this->views->getView($this, "personas", $data);
    }
    public function getPersonasData() {
        // Obtener los datos del modelo
        $arrData = $this->get_model()->SelectAllPersonas();
    
        // Construir la respuesta en el formato esperado por DataTables
        $response = [
            "draw" => intval($_GET['draw']), // El número de solicitud enviado por DataTables
            "recordsTotal" => count($arrData), // Total de registros sin filtrar
            "recordsFiltered" => count($arrData), // Total de registros después de aplicar filtros
            "data" => $arrData // Los datos reales
        ];
    
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function setPersona() {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
        $data = json_decode($json, true); // Decodifica los datos JSON
    
        // Extraer los datos del JSON
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
        if (empty($nombre) || empty($apellido) || empty($cedula)) {
            $response = array("status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios.");
            echo json_encode($response);
            return;
        }
    
        // Insertar los datos usando el modelo
        $insertData = $this->get_model()->insertPersona([
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
            $response = array("status" => true, "message" => "Persona registrada correctamente.");
        } else {
            $response = array("status" => false, "message" => "Error al registrar la persona. Intenta nuevamente.");
        }
    
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
   
   
}