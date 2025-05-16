<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class Ventas extends Controllers
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
        $data['page_title'] = "Listado de Ventas";
        $data['page_name'] = "Ventas";
        $data['page_functions_js'] = "functions_ventas.js";
        $this->views->getView($this, "ventas", $data);
    }
    
   
public function getventasData()
{
    try {
        // Obtener los datos del modelo
        $arrData = $this->get_model()->SelectAllventas();

        // Formatear los datos en un arreglo asociativo
        $data = [];
        foreach ($arrData as $venta) {
            $data[] = [
                'idventa' => $venta->getIdventa(),
                'nombre_producto' => $venta->getIdProducto(),
                'fecha' => $venta->getFecha(),
                'cantidad' => $venta->getCantidad(),
                'estatus' => $venta->getEstatus(),
                'descuento' => $venta->getDescuento(),
                'total' => $venta->getTotal(),
                'fecha_creacion' => $venta->getFechaCreacion(),
            ];
        }

        // Preparar la respuesta para el DataTable
        $response = [
            "draw" => intval($_GET['draw'] ?? 1),
            "recordsTotal" => count($data),
            "recordsFiltered" => count($data),
            "data" => $data
        ];

        // Enviar la respuesta como JSON
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // Manejo de errores
        echo json_encode([
            "status" => false,
            "message" => "Error al obtener los datos: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit();
}
  


public function createventa()
{
    try {
        // Leer los datos JSON enviados por el frontend
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Depuración: Verifica los datos recibidos
        error_log("Datos recibidos en createventa:");
        error_log(print_r($data, true));

        // Validar que los datos no sean nulos
        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
            exit();
        }

        // Validar campos obligatorios
        if (empty($data['cedula'])) {
            echo json_encode(["status" => false, "message" => "El campo 'cedula' es obligatorio."]);
            exit();
        }

        // Usar los métodos set del modelo para establecer los valores
        $model = $this->get_model();
        $model->setNombre(trim($data['nombre'] ?? ''));
        $model->setApellido(trim($data['apellido'] ?? ''));
        $model->setCedula(trim($data['cedula'] ?? ''));
        $model->setTelefonoPrincipal(trim($data['telefono_principal'] ?? ''));
        $model->setDireccion(trim($data['direccion'] ?? ''));
        $model->setEstatus(trim($data['estatus'] ?? ''));
        $model->setObservaciones(trim($data['observaciones'] ?? ''));

        // Insertar los datos usando el modelo
        $insertData = $model->insertventa();

        // Respuesta al venta
        if ($insertData) {
            echo json_encode(["status" => true, "message" => "venta registrado correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al registrar el venta. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}




public function deleteventa()
{
    try {
        // Leer los datos JSON enviados por el frontend
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Validar que los datos sean válidos
        if (!$data || !isset($data['idventa'])) {
            echo json_encode([
                "status" => false,
                "message" => "ID de venta no proporcionado o datos inválidos."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Extraer el ID del venta
        $idventa = trim($data['idventa']);

        // Validar que el ID no esté vacío
        if (empty($idventa)) {
            echo json_encode([
                "status" => false,
                "message" => "El ID del venta no puede estar vacío."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Desactivar el venta usando el modelo
        $deleteData = $this->get_model()->deleteventa($idventa);

        // Respuesta al venta
        if ($deleteData) {
            echo json_encode([
                "status" => true,
                "message" => "venta eliminado correctamente."
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Error al eliminar el venta. Intenta nuevamente."
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        // Manejo de errores inesperados
        echo json_encode([
            "status" => false,
            "message" => "Error inesperado: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit();
}

public function updateventa()
{
    try {
        // Leer los datos JSON enviados por el frontend
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Validar que los datos sean válidos
        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
            return;
        }

        // Validar campos obligatorios
        if (empty($data['idventa']) || empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula'])) {
            echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
            return;
        }

       

        // Usar los métodos set del modelo para establecer los valores
        $model = $this->get_model();
        $model->setIdventa(trim($data['idventa']));
        $model->setNombre(trim($data['nombre']));
        $model->setApellido(trim($data['apellido']));
        $model->setCedula(trim($data['cedula']));
        $model->setFechaNacimiento(trim($data['fecha_nacimiento'] ?? ''));
        $model->setTelefonoPrincipal(trim($data['telefono_principal'] ?? ''));
        
        $model->setDireccion(trim($data['direccion'] ?? ''));
     
        $model->setEstatus(trim($data['estatus'] ?? ''));

        // Actualizar los datos usando el modelo
        $updateData = $model->updateventa();

        // Respuesta al venta
        if ($updateData) {
            echo json_encode(["status" => true, "message" => "venta actualizado correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al actualizar el venta. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

    
    public function getventaById($idventa)
    {
        try {
            // Validar que el ID del venta sea válido
            if (empty($idventa) || !is_numeric($idventa)) {
                echo json_encode(["status" => false, "message" => "ID de venta no válido."]);
                return;
            }

            // Obtener los datos del venta desde el modelo
            $venta = $this->get_model()->getventaById($idventa);

            // Validar si se encontró el venta
            if ($venta) {
                echo json_encode(["status" => true, "data" => $venta], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(["status" => false, "message" => "venta no encontrado."], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    // public function guardarventa()
    // {
    //     try {
    //         // Leer los datos JSON enviados por el frontend
    //         $json = file_get_contents('php://input');
    //         $data = json_decode($json, true);

    //         // Validar que los datos sean válidos
    //         if (!$data || !is_array($data)) {
    //             echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
    //             return;
    //         }

    //         // Validar campos obligatorios
    //         if (empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula'])) {
    //             echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
    //             return;
    //         }

    //         // Obtener el modelo
    //         $model = $this->get_model();
    //             $model->setTelefonoPrincipal(trim($data['telefono_principal'] ?? ''));
    //             $model->setEstatus(trim($data['estatus'] ?? 'ACTIVO'));
    //             $model->setObservaciones(trim($data['observaciones'] ?? ''));

    //             $result = $model->insertventa();
    //         } else {
    //             // Lógica para actualizar un venta existente
    //             $model->setIdventa(trim($data['idventa']));
    //             $model->setCedula(trim($data['cedula']));
    //             $model->setNombre(trim($data['nombre']));
    //             $model->setApellido(trim($data['apellido']));
    //             $model->setDireccion(trim($data['direccion'] ?? ''));
    //             $model->setTelefonoPrincipal(trim($data['telefono_principal'] ?? ''));
    //             $model->setEstatus(trim($data['estatus'] ?? 'ACTIVO'));
    //             $model->setObservaciones(trim($data['observaciones'] ?? ''));

    //             $result = $model->updateventa();
    //         }

    //         // Respuesta al venta
    //         if ($result) {
    //             echo json_encode(["status" => true, "message" => "Operación realizada correctamente."]);
    //         } else {
    //             echo json_encode(["status" => false, "message" => "Error al guardar los datos del venta."]);
    //         }
    //     } catch (Exception $e) {
    //         echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    //     }
    //     exit();
    // }
}
