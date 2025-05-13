<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class clientes extends Controllers
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
        $data['page_name'] = "Clientes";
        $data['page_functions_js'] = "functions_clientes.js";
        $this->views->getView($this, "clientes", $data);
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
  


public function createcliente()
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

    public function updatecliente()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por la vista
        $data = json_decode($json, true); // datos del json lo decodifica 

        // Extraer los datos del JSON
        $idcliente = trim($data['idcliente']) ?? null;
        $nombre = trim($data['nombre']) ?? null;
        $apellido = trim($data['apellido']) ?? null;
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
        if (empty($idcliente) || empty($nombre) || empty($apellido) || empty($cedula)) {
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


        $updateData = $this->get_model()->updatecliente([
            "idcliente" => $idcliente,
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
            $response = ["status" => true, "message" => "cliente actualizada correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al actualizar la cliente. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function getclienteById($idcliente)
    {
        try {

            $cliente = $this->get_model()->getclienteById($idcliente);

            if ($cliente) {
                echo json_encode(["status" => true, "data" => $cliente]);
            } else {
                echo json_encode(["status" => false, "message" => "cliente no encontrada."]);
            }
        } catch (Exception $e) {

            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}
