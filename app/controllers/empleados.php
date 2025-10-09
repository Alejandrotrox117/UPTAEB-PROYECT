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

    
    public function index()
    {
        $data['page_title'] = "Gestión de Empleados";
        $data['page_name'] = "empleados";
        $data['page_functions_js'] = "functions_empleado.js";
        $this->views->getView($this, "empleados", $data);
    }

    
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

    
    public function createEmpleado()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true); 

            
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $identificacion = trim($data['identificacion'] ?? '');
            $tipo_empleado = trim($data['tipo_empleado'] ?? 'OPERARIO');
            $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
            $direccion = trim($data['direccion'] ?? '');
            $correo_electronico = trim($data['correo_electronico'] ?? '');
            $estatus = trim($data['estatus'] ?? 'activo');
            $telefono_principal = trim($data['telefono_principal'] ?? '');
            $observaciones = trim($data['observaciones'] ?? '');
            $genero = trim($data['genero'] ?? '');
            $fecha_inicio = trim($data['fecha_inicio'] ?? '');
            $fecha_fin = trim($data['fecha_fin'] ?? '');
            $puesto = trim($data['puesto'] ?? '');
            $salario = trim($data['salario'] ?? '0.00');

            
            if (empty($nombre) || empty($apellido) || empty($identificacion)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }

            
            $insertData = $this->model->insertEmpleado([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "identificacion" => $identificacion,
                "tipo_empleado" => $tipo_empleado,
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

    
    public function deleteEmpleado()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true); 

        
        $idempleado = trim($data['idempleado']) ?? null;

        
        if (empty($idempleado)) {
            $response = ["status" => false, "message" => "ID de empleado no proporcionado."];
            echo json_encode($response);
            return;
        }

        
        $deleteData = $this->get_model()->deleteEmpleado($idempleado);

        
        if ($deleteData) {
            $response = ["status" => true, "message" => "Empleado desactivado correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al desactivar el empleado. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    
    public function updateEmpleado()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true); 

            
            $idempleado = trim($data['idempleado'] ?? '');
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $identificacion = trim($data['identificacion'] ?? '');
            $tipo_empleado = trim($data['tipo_empleado'] ?? 'OPERARIO');
            $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
            $direccion = trim($data['direccion'] ?? '');
            $correo_electronico = trim($data['correo_electronico'] ?? '');
            $estatus = trim($data['estatus'] ?? 'activo');
            $telefono_principal = trim($data['telefono_principal'] ?? '');
            $observaciones = trim($data['observaciones'] ?? '');
            $genero = trim($data['genero'] ?? '');
            $fecha_modificacion = date('Y-m-d H:i:s');
            $fecha_inicio = trim($data['fecha_inicio'] ?? '');
            $fecha_fin = trim($data['fecha_fin'] ?? '');
            $puesto = trim($data['puesto'] ?? '');
            $salario = trim($data['salario'] ?? '0.00');

            
            if (empty($idempleado) || empty($nombre) || empty($apellido) || empty($identificacion)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Nombre, apellido e identificación son obligatorios."]);
                exit();
            }

            
            if (!empty($correo_electronico) && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(["status" => false, "message" => "El correo electrónico no es válido."]);
                exit();
            }

            
            $updateData = $this->get_model()->updateEmpleado([
                "idempleado" => $idempleado,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "identificacion" => $identificacion,
                "tipo_empleado" => $tipo_empleado,
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

            
            if ($updateData) {
                echo json_encode(["status" => true, "message" => "Empleado actualizado correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar el empleado. Intenta nuevamente."]);
            }
        } catch (PDOException $e) {
            // Error específico de base de datos
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'tipo_empleado') !== false || strpos($errorMsg, 'Unknown column') !== false) {
                echo json_encode([
                    "status" => false, 
                    "message" => "Error: El campo 'tipo_empleado' no existe en la base de datos. Debes ejecutar la migración SQL."
                ]);
            } else {
                echo json_encode([
                    "status" => false, 
                    "message" => "Error de base de datos: " . $errorMsg
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    
    public function getEmpleadoById($idempleado)
    {
        try {
            // Si $idempleado es un array, tomar el primer elemento
            if (is_array($idempleado)) {
                $idempleado = $idempleado[0];
            }
            
            // Validar que sea un número
            if (!is_numeric($idempleado)) {
                echo json_encode(["status" => false, "message" => "ID de empleado inválido."]);
                exit();
            }
            
            $empleado = $this->get_model()->getEmpleadoById($idempleado);

            if ($empleado) {
                echo json_encode(["status" => true, "data" => $empleado]);
            } else {
                echo json_encode([
                    "status" => false, 
                    "message" => "Empleado no encontrado con ID: {$idempleado}"
                ]);
            }
        } catch (PDOException $e) {
            // Error específico de base de datos
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'tipo_empleado') !== false) {
                echo json_encode([
                    "status" => false, 
                    "message" => "Error: El campo 'tipo_empleado' no existe en la base de datos. Debes ejecutar la migración SQL."
                ]);
            } else {
                echo json_encode([
                    "status" => false, 
                    "message" => "Error de base de datos: " . $errorMsg
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}