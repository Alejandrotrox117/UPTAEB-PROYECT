<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class tasas extends Controllers
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

    // Vista principal para gestionar categorias
    public function index()
    {
        $data['page_title'] = "Gestión de Historico de Tasas";
        $data['page_name'] = "Tasas BCV";
        $data['page_functions_js'] = "functions_tasas.js";
        $this->views->getView($this, "tasas", $data);
    }

    // Obtener datos de categorias para DataTables
    public function getTasasData()
    {
        $arrData = $this->get_model()->SelectAllTasas();

        $response = [
           
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Crear un nuevo categoria
    public function crearCategoria()
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
            $descripcion = trim($data['descripcion'] ?? '');
            $estatus = trim($data['estatus'] ?? 'ACTIVO');

            // Validar campos obligatorios
            if (empty($nombre)) {
                echo json_encode(["status" => false, "message" => "El nombre de la categoría es obligatorio."]);
                exit();
            }

            // Insertar los datos usando el modelo
            $insertData = $this->model->insertCategoria([
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "estatus" => $estatus,
            ]);

            // Respuesta al cliente
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "Categoría registrada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la categoría. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Actualizar una categoría existente
    public function actualizarCategoria()
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
            $idcategoria = trim($data['idcategoria'] ?? null);
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $estatus = trim($data['estatus'] ?? '');

            // Validar campos obligatorios
            if (empty($idcategoria) || empty($nombre)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }

            // Actualizar los datos usando el modelo
            $updateData = $this->model->updateCategoria([
                "idcategoria" => $idcategoria,
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "estatus" => $estatus,
            ]);

            // Respuesta al cliente
            if ($updateData) {
                echo json_encode(["status" => true, "message" => "Categoría actualizada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar la categoría. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Eliminar una categoría (lógico)
    public function deleteCategoria($idcategoria)
    {
        try {
            // Validar que el ID no esté vacío
            if (empty($idcategoria)) {
                echo json_encode(["status" => false, "message" => "ID de categoría no proporcionado."]);
                return;
            }

            // Desactivar la categoría usando el modelo
            $deleteData = $this->model->deleteCategoria($idcategoria);

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
    public function getCategoriaById($idcategoria)
    {
        try {
            $categoria = $this->model->getCategoriaById($idcategoria);

            if ($categoria) {
                echo json_encode(["status" => true, "data" => $categoria]);
            } else {
                echo json_encode(["status" => false, "message" => "Categoría no encontrada."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}