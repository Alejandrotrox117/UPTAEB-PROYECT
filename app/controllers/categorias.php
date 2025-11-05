<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class categorias extends Controllers
{
    // Categorias del sistema que no se pueden eliminar
    const CATEGORIAS_SISTEMA = [1, 2, 3]; // 1=Pacas, 2=Materiales, 3=Consumibles

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
        $data['page_title'] = "Gestion de categorias";
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
                echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
                exit();
            }

            
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $estatus = trim($data['estatus'] ?? 'ACTIVO');

            
            if (empty($nombre)) {
                echo json_encode(["status" => false, "message" => "El nombre de la categoria es obligatorio."]);
                exit();
            }

            
            $insertData = $this->model->insertCategoria([
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "estatus" => $estatus,
            ]);

            
            if ($insertData) {
                echo json_encode(["status" => true, "message" => "Categoria registrada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al registrar la categoria. Intenta nuevamente."]);
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
                echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
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
                echo json_encode(["status" => true, "message" => "Categoria actualizada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al actualizar la categoria. Intenta nuevamente."]);
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
                echo json_encode(["status" => false, "message" => "ID de categoria no proporcionado."]);
                return;
            }

            // Validar que no sea una categoria del sistema
            if (in_array((int)$idcategoria, self::CATEGORIAS_SISTEMA)) {
                echo json_encode([
                    "status" => false, 
                    "message" => "No se puede eliminar esta categoria porque es una categoria del sistema."
                ]);
                return;
            }

            
            $deleteData = $this->model->deleteCategoria($idcategoria);

            
            if ($deleteData) {
                echo json_encode(["status" => true, "message" => "Categoria desactivada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al desactivar la categoria. Intenta nuevamente."]);
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
                echo json_encode(["status" => false, "message" => "Categoria no encontrada."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    /**
     * Reactivar una categoria inactiva
     */
    public function reactivarCategoria()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
                exit();
            }

            $idcategoria = intval($data['idcategoria'] ?? 0);
            
            if ($idcategoria <= 0) {
                echo json_encode(["status" => false, "message" => "ID de categoria invalido."]);
                exit();
            }

            // Llamar al modelo para reactivar
            $reactivarData = $this->model->reactivarCategoria($idcategoria);

            if ($reactivarData) {
                echo json_encode(["status" => true, "message" => "Categoria reactivada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al reactivar la categoria. Intenta nuevamente."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
}
