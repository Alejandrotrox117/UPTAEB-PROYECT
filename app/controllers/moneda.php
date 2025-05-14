<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class moneda extends Controllers
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

    // Vista principal para gestionar monedas
    public function index()
    {
        $data['page_title'] = "Gestión de moneda";
        $data['page_name'] = "moneda";
        $data['page_functions_js'] = "functions_moneda.js";
        $this->views->getView($this, "moneda", $data);
    }

    // Obtener datos de monedas para DataTables
    public function getMonedaData()
    {
        $arrData = $this->get_model()->SelectAllMoneda();

        $response = [
           
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Crear un nuevo moneda
    public function crearMoneda()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Validar que los datos no sean nulos
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            // Extraer los datos del JSON
            $nombre = trim($data['nombre'] ?? '');
            $valor = trim($data['valor'] ?? '');
            $estatus = trim($data['estatus'] ?? 'ACTIVO');

            // Validar campos obligatorios
            if (empty($nombre)) {
                echo json_encode(["status" => false, "message" => "El nombre de la moneda es obligatorio."]);
                exit();
            }

            // Insertar los datos usando el modelo
            $insertData = $this->model->insertMoneda([
                "nombre" => $nombre,
                "valor" => $valor,
                "estatus" => $estatus,
            ]);

            // Respuesta al cliente
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "moneda registrada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la moneda. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Actualizar una categoría existente
    public function actualizarMoneda()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Validar que los datos no sean nulos
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            // Extraer los datos del JSON
            $idmoneda = trim($data['idmoneda'] ?? null);
            $nombre = trim($data['nombre'] ?? '');
            $valor = trim($data['valor'] ?? '');
            $estatus = trim($data['estatus'] ?? '');

            // Validar campos obligatorios
            if (empty($idmoneda) || empty($nombre)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }

            // Actualizar los datos usando el modelo
            $updateData = $this->model->updateMoneda([
                "idmoneda" => $idmoneda,
                "nombre_moneda" => $nombre,
                "valor" => $valor,
                "estatus" => $estatus,
            ]);

            // Respuesta al cliente
            if ($updateData) {
                echo json_encode(["status" => true, "message" => "Moneda actualizada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar la moneda. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Eliminar una categoría (lógico)
    public function deleteMoneda($idmoneda)
    {
        try {
            // Validar que el ID no esté vacío
            if (empty($idmoneda)) {
                echo json_encode(["status" => false, "message" => "ID de categoría no proporcionado."]);
                return;
            }

            // Desactivar la categoría usando el modelo
            $deleteData = $this->model->deleteMoneda($idmoneda);

            // Respuesta al cliente
            if ($deleteData) {
                echo json_encode(["status" => true, "message" => "Categoría desactivada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al desactivar la categoría. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Obtener una categoría por ID
    public function getMonedaById($idmoneda)
    {
        try {
            $moneda = $this->model->getMonedaById($idmoneda);

            if ($moneda) {
                echo json_encode(["status" => true, "data" => $moneda]);
            } else {
                echo json_encode(["status" => false, "message" => "Moneda no encontrada."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}