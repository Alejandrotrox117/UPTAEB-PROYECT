<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class Productos extends Controllers
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

    // Vista principal para gestionar productos
    public function index()
    {
        $data['page_title'] = "Gestión de Productos";
        $data['page_name'] = "Productos";
        $data['page_functions_js'] = "functions_productos.js";
        $this->views->getView($this, "productos", $data);
    }

    // Obtener datos de productos para DataTables
    public function getProductosData()
    {
        $arrData = $this->get_model()->SelectAllProductos();

        $response = [
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Crear un nuevo producto
    public function createProducto()
    {
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
        $nombre = trim($data['nombre'] ?? null);
        $descripcion = trim($data['descripcion'] ?? null);
        $unidad_medida = trim($data['unidad_medida'] ?? null);
        $precio = trim($data['precio'] ?? null);
        $existencia = trim($data['existencia'] ?? null);
        $idcategoria = trim($data['idcategoria'] ?? null);
        $moneda = trim($data['moneda'] ?? null);
        $estatus = trim($data['estatus'] ?? 'ACTIVO');
    
        // Validar campos obligatorios
        if (empty($nombre) || empty($precio) || empty($existencia) || empty($idcategoria)) {
            echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios. insertar"]);
            exit();
        }
    
        // Insertar los datos usando el modelo
        $insertData = $this->model->insertProducto([
            "nombre" => $nombre,
            "descripcion" => $descripcion,
            "unidad_medida" => $unidad_medida,
            "precio" => $precio,
            "existencia" => $existencia,
            "idcategoria" => $idcategoria,
            "moneda" => $moneda,
            "estatus" => $estatus,
        ]);
    
        // Respuesta al cliente
        if ($insertData) {
            echo json_encode(["status" => true, "message" => "Producto registrado correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al registrar el producto. Intenta nuevamente.insertar "]);
        }
        exit();
    }

    // Eliminar un producto (lógico)
    public function deleteProducto()
    {
        $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
        $data = json_decode($json, true); // Decodifica los datos JSON

        // Extraer el ID del producto a desactivar
        $idproducto = trim($data['idproducto']) ?? null;

        // Validar que el ID no esté vacío
        if (empty($idproducto)) {
            $response = ["status" => false, "message" => "ID de producto no proporcionado."];
            echo json_encode($response);
            return;
        }

        // Desactivar el producto usando el modelo
        $deleteData = $this->get_model()->deleteProducto($idproducto);

        // Respuesta al cliente
        if ($deleteData) {
            $response = ["status" => true, "message" => "Producto desactivado correctamente."];
        } else {
            $response = ["status" => false, "message" => "Error al desactivar el producto. Intenta nuevamente."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    public function getCategorias()
{
    $categorias = $this->model->SelectAllCategorias();

    if ($categorias) {
        echo json_encode(["status" => true, "data" => $categorias]);
    } else {
        echo json_encode(["status" => false, "message" => "No se encontraron categorías."]);
    }
    exit(); // Asegura que la respuesta termine aquí
}
    // Actualizar un producto existente
    public function updateProducto()
{
    $json = file_get_contents('php://input'); // Lee los datos JSON enviados por el frontend
    $data = json_decode($json, true); // Decodifica los datos JSON

    // Extraer los datos del JSON
    $idproducto = trim($data['idproducto']) ?? null;
    $nombre = trim($data['nombre']) ?? null;
    $descripcion = trim($data['descripcion']) ?? null;
    $unidad_medida = trim($data['unidad_medida']) ?? null;
    $precio = trim($data['precio']) ?? null;
    $existencia = trim($data['existencia']) ?? null;
    $idcategoria = trim($data['idcategoria']) ?? null;
    $moneda = trim($data['moneda']) ?? null;
    $estatus = trim($data['estatus']) ?? 'ACTIVO';

    // Validar campos obligatorios
    if (empty($idproducto) || empty($nombre) || empty($precio) || empty($existencia) || empty($idcategoria)) {
        echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios. actualizar"]);
        exit();
    }

    // Actualizar los datos usando el modelo
    $updateData = $this->model->updateProducto([
        "idproducto" => $idproducto,
        "nombre" => $nombre,
        "descripcion" => $descripcion,
        "unidad_medida" => $unidad_medida,
        "precio" => $precio,
        "existencia" => $existencia,
        "idcategoria" => $idcategoria,
        "moneda" => $moneda,
        "estatus" => $estatus,
    ]);

    // Respuesta al cliente
    if ($updateData) {
        echo json_encode(["status" => true, "message" => "Producto actualizado correctamente."]);
    } else {
        echo json_encode(["status" => false, "message" => "Error al actualizar el producto. Intenta nuevamente. actualizar"]);
    }
    exit();
}

    // Obtener un producto por ID
    public function getProductoById($idproducto)
    {
        try {
            $producto = $this->model->getProductoById($idproducto);
    
            if ($producto) {
                echo json_encode(["status" => true, "data" => $producto]);
            } else {
                echo json_encode(["status" => false, "message" => "Producto no encontrado."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit(); // Asegura que la respuesta termine aquí
    }
}