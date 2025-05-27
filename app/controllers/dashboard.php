<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class dashboard extends Controllers
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
        $data['page_title'] = "Listado de  clientes";
        $data['page_name'] = "dashboard";
        $data['page_functions_js'] = "functions_dashboard.js";
        $this->views->getView($this, "dashboard", $data);
    }


    public function getClientesData()
    {
        // Obtener los datos del modelo
        $arrData = $this->get_model()->SelectAllclientes();

        // Formatear los datos en un arreglo asociativo
        $data = [];
        foreach ($arrData as $cliente) {
            $data[] = [
                'idcliente' => $cliente->getIdcliente(),
                'cedula' => $cliente->getCedula(),
                'nombre' => $cliente->getNombre(),
                'apellido' => $cliente->getApellido(),
                'direccion' => $cliente->getDireccion(),
                'estatus' => $cliente->getEstatus(),
                'telefono_principal' => $cliente->getTelefonoPrincipal(),
                'observaciones' => $cliente->getObservaciones(),
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
        exit();
    }



    public function createCliente()
    {
        try {
            // Leer los datos JSON enviados por el frontend
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Depuración: Verifica los datos recibidos
            error_log("Datos recibidos en createcliente:");
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
            $insertData = $model->insertCliente();

            // Respuesta al cliente
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "Cliente registrado correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar el cliente. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }




    public function deletecliente()
    {
        try {
            // Leer los datos JSON enviados por el frontend
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Validar que los datos sean válidos
            if (!$data || !isset($data['idcliente'])) {
                echo json_encode([
                    "status" => false,
                    "message" => "ID de cliente no proporcionado o datos inválidos."
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Extraer el ID del cliente
            $idcliente = trim($data['idcliente']);

            // Validar que el ID no esté vacío
            if (empty($idcliente)) {
                echo json_encode([
                    "status" => false,
                    "message" => "El ID del cliente no puede estar vacío."
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Desactivar el cliente usando el modelo
            $deleteData = $this->get_model()->deleteCliente($idcliente);

            // Respuesta al cliente
            if ($deleteData) {
                echo json_encode([
                    "status" => true,
                    "message" => "Cliente eliminado correctamente."
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    "status" => false,
                    "message" => "Error al eliminar el cliente. Intenta nuevamente."
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

    public function updateCliente()
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
            if (empty($data['idcliente']) || empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula'])) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                return;
            }



            // Usar los métodos set del modelo para establecer los valores
            $model = $this->get_model();
            $model->setIdcliente(trim($data['idcliente']));
            $model->setNombre(trim($data['nombre']));
            $model->setApellido(trim($data['apellido']));
            $model->setCedula(trim($data['cedula']));
            $model->setFechaNacimiento(trim($data['fecha_nacimiento'] ?? ''));
            $model->setTelefonoPrincipal(trim($data['telefono_principal'] ?? ''));

            $model->setDireccion(trim($data['direccion'] ?? ''));

            $model->setEstatus(trim($data['estatus'] ?? ''));

            // Actualizar los datos usando el modelo
            $updateData = $model->updateCliente();

            // Respuesta al cliente
            if ($updateData) {
                echo json_encode(["status" => true, "message" => "Cliente actualizado correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar el cliente. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }


    public function getclienteById($idcliente)
    {
        try {
            // Validar que el ID del cliente sea válido
            if (empty($idcliente) || !is_numeric($idcliente)) {
                echo json_encode(["status" => false, "message" => "ID de cliente no válido."]);
                return;
            }

            // Obtener los datos del cliente desde el modelo
            $cliente = $this->get_model()->getClienteById($idcliente);

            // Validar si se encontró el cliente
            if ($cliente) {
                echo json_encode(["status" => true, "data" => $cliente], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(["status" => false, "message" => "Cliente no encontrado."], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

   
    

public function buscar()
{
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['criterio'])) {
        $criterio = trim($_GET['criterio']);
        $modelo = $this->get_model();
        $clientes = $modelo->buscarClientes($criterio);

        // Adaptar el resultado para el JS
        $data = [];
        foreach ($clientes as $cliente) {
            $data[] = [
                'id' => $cliente['idcliente'],
                'nombre' => $cliente['nombre'],
                'apellido' => $cliente['apellido'],
                'cedula' => $cliente['cedula'],
            ];
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([]);
    }
    exit();
}
}