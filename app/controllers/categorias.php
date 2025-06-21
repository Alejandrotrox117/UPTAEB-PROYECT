<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class categorias extends Controllers
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
        $data['page_title'] = "Gestión de categorias";
        $data['page_name'] = "categorias";
        $data['page_functions_js'] = "functions_categorias.js";
        $this->views->getView($this, "categorias", $data);
    }

    
    public function getCategoriasData()
    {
        $arrData = $this->get_model()->SelectAllCategorias();

        $response = [
           
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    
    public function crearCategoria()
    {
        try {
            $json = file_get_contents('php://input');//input');//input');
            $data = json_decode($json, true);

            
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $estatus = trim($data['estatus'] ?? 'ACTIVO');

            
            if (empty($nombre)) {
                echo json_encode(["status" => false, "message" => "El nombre de la categoría es obligatorio."]);
                exit();
            }

            
            $insertData = $this->model->insertCategoria([
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "estatus" => $estatus,
            ]);

            
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

    
    public function actualizarCategoria()
    {
        try {
            $json = file_get_contents('php://input');//input');
            $data = json_decode($json, true);

            
            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
                exit();
            }

            
            $idcategoria = trim($data['idcategoria'] ?? null);
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $estatus = trim($data['estatus'] ?? '');

            
            if (empty($idcategoria) || empty($nombre)) {
                echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
                exit();
            }

            
            $updateData = $this->model->updateCategoria([
                "idcategoria" => $idcategoria,
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "estatus" => $estatus,
            ]);

            
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

    
    public function deleteCategoria($idcategoria)
    {
        try {
            
            if (empty($idcategoria)) {
                echo json_encode(["status" => false, "message" => "ID de categoría no proporcionado."]);
                return;
            }

            
            $deleteData = $this->model->deleteCategoria($idcategoria);

            
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