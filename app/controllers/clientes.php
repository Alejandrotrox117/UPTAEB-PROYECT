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

        $arrData = $this->get_model()->SelectAllclientes();


        $response = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function createcliente()
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
            $insertData = $this->model->insertcliente([
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
                echo json_encode(["status" => true, "message" => "cliente registrada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la cliente. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
    public function deletecliente()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
        $data = json_decode($json, true); // Decodifica los datos JSON

        // Extraer el ID de la cliente a desactivar
        $idcliente = trim($data['idcliente']) ?? null;

        // Validar que el ID no esté vacío
        if (empty($idcliente)) {
            $response = ["status" => false, "message" => "ID de cliente no proporcionado."];
            echo json_encode($response);
            return;
        }

        // Desactivar la cliente usando el modelo
        $deleteData = $this->get_model()->deletecliente($idcliente);

        // Respuesta al cliente
        if ($deleteData) {
            $response = ["status" => true, "message" => "cliente desactivada correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al desactivar la cliente. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
