<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Empleados extends Controllers
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

    // Método para mostrar la vista principal de gestión de empleados
    public function index()
    {
        $data['page_title'] = "Gestión de Empleados";
        $data['page_name'] = "empleados";
        $data['page_functions_js'] = "functions_empleado.js";
        $this->views->getView($this, "empleados", $data);
    }

    // Método para obtener todos los empleados en formato JSON
    public function getEmpleadoData()
    {
        $arrData = $this->get_model()->SelectAllEmpleados();

        $response = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Método para crear un nuevo empleado
    public function createEmpleado()
    {
        try {
            $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
            $data = json_decode($json, true); // Decodifica los datos JSON

            // Validar que los datos no sean nulos
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            // Extraer los datos del JSON
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $identificacion = trim($data['identificacion'] ?? '');
            $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
            $direccion = trim($data['direccion'] ?? '');
            $correo_electronico = trim($data['correo_electronico'] ?? '');
            $estatus = trim($data['estatus'] ?? '');
            $telefono_principal = trim($data['telefono_principal'] ?? '');
            $observaciones = trim($data['observaciones'] ?? '');
            $genero = trim($data['genero'] ?? '');
            $fecha_inicio = trim($data['fecha_inicio'] ?? '');
            $fecha_fin = trim($data['fecha_fin'] ?? '');
            $puesto = trim($data['puesto'] ?? '');
            $salario = trim($data['salario'] ?? '');

            // Validar campos obligatorios
            if (empty($nombre) || empty($apellido) || empty($identificacion)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }

            // Insertar los datos usando el modelo
            $insertData = $this->model->insertEmpleado([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "identificacion" => $identificacion,
                "fecha_nacimiento" => $fecha_nacimiento,
                "direccion" => $direccion,
                "correo_electronico" => $correo_electronico,
                "estatus" => $estatus,
                "telefono_principal" => $telefono_principal,
                "observaciones" => $observaciones,
                "genero" => $genero,
                "fecha_inicio" => $fecha_inicio,
                "fecha_fin" => $fecha_fin,
                "puesto" => $puesto,
                "salario" => $salario,
            ]);

            // Respuesta al cliente
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "Empleado registrado correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar el empleado. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Método para eliminar lógicamente un empleado
    public function deleteEmpleado()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
        $data = json_decode($json, true); // Decodifica los datos JSON

        // Extraer el ID del empleado a desactivar
        $idempleado = trim($data['idempleado']) ?? null;

        // Validar que el ID no esté vacío
        if (empty($idempleado)) {
            $response = ["status" => false, "message" => "ID de empleado no proporcionado."];
            echo json_encode($response);
            return;
        }

        // Desactivar el empleado usando el modelo
        $deleteData = $this->get_model()->deleteEmpleado($idempleado);

        // Respuesta al cliente
        if ($deleteData) {
            $response = ["status" => true, "message" => "Empleado desactivado correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al desactivar el empleado. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Método para actualizar un empleado existente
    public function updateEmpleado()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por la vista
        $data = json_decode($json, true); // Decodifica los datos JSON

        // Extraer los datos del JSON
        $idempleado = trim($data['idempleado']) ?? null;
        $nombre = trim($data['nombre']) ?? null;
        $apellido = trim($data['apellido']) ?? null;
        $identificacion = trim($data['identificacion']) ?? null;
        $fecha_nacimiento = trim($data['fecha_nacimiento']) ?? null;
        $direccion = trim($data['direccion']) ?? null;
        $correo_electronico = trim($data['correo_electronico']) ?? null;
        $estatus = trim($data['estatus']) ?? null;
        $telefono_principal = trim($data['telefono_principal']) ?? null;
        $observaciones = trim($data['observaciones']) ?? null;
        $genero = trim($data['genero']) ?? null;
        $fecha_modificacion = date('Y-m-d H:i:s');
        $fecha_inicio = trim($data['fecha_inicio']) ?? null;
        $fecha_fin = trim($data['fecha_fin']) ?? null;
        $puesto = trim($data['puesto']) ?? null;
        $salario = trim($data['salario']) ?? null;

        // Validar campos obligatorios
        if (empty($idempleado) || empty($nombre) || empty($apellido) || empty($identificacion)) {
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

        // Actualizar los datos usando el modelo
        $updateData = $this->get_model()->updateEmpleado([
            "idempleado" => $idempleado,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "identificacion" => $identificacion,
            "fecha_nacimiento" => $fecha_nacimiento,
            "direccion" => $direccion,
            "correo_electronico" => $correo_electronico,
            "estatus" => $estatus,
            "telefono_principal" => $telefono_principal,
            "observaciones" => $observaciones,
            "genero" => $genero,
            "fecha_modificacion" => $fecha_modificacion,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "puesto" => $puesto,
            "salario" => $salario,
        ]);

        // Respuesta al cliente
        if ($updateData) {
            $response = ["status" => true, "message" => "Empleado actualizado correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al actualizar el empleado. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Método para obtener un empleado por su ID
    public function getEmpleadoById($idempleado)
    {
        try {
            $empleado = $this->get_model()->getEmpleadoById($idempleado);

            if ($empleado) {
                echo json_encode(["status" => true, "data" => $empleado]);
            } else {
                echo json_encode(["status" => false, "message" => "Empleado no encontrado."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}