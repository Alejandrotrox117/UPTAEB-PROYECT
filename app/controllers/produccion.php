<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/produccionModel.php"; // Ajusta la ruta según tu estructura

class Produccion extends Controllers
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
        // Inicializa el modelo correspondiente
        $this->set_model(new produccionModel());
    }

    // Método para mostrar la vista principal de gestión de producción
    public function index()
    {
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }

    // Método para obtener datos de producciones para DataTables
    public function getProduccionData()
    {
        $arrData = $this->get_model()->SelectAllProducciones();

        $response = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function getDetalleProduccionData($idproduccion)
    {
        try {
            $produccion = $this->get_model()->SelectDetalleProduccion($idproduccion);

            if ($produccion) {
                echo json_encode(["status" => true, "data" => $produccion]);
            } else {
                echo json_encode(["status" => false, "message" => "Produccion no encontrado."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
   
    // Método para registrar una nueva producción
    public function createProduccion()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "Datos inválidos."]);
            exit();
        }

        // Extraer campos básicos
        $idempleado = trim($data['idempleado'] ?? '');
        $idproducto = trim($data['idproducto'] ?? '');
        $cantidad_a_realizar = trim($data['cantidad_a_realizar'] ?? '');
        $fecha_inicio = trim($data['fecha_inicio'] ?? '');
        $fecha_fin = trim($data['fecha_fin'] ?? '');
        $estado = trim($data['estado'] ?? 'borrador');

        // Validaciones
        if (empty($idproducto) || empty($cantidad_a_realizar) || empty($fecha_inicio)) {
            echo json_encode(["status" => false, "message" => "Campos incompletos."]);
            exit();
        }

        // Obtener insumos del arreglo POST
        $insumos = [];

        if (!empty($data['idproducto_insumo']) && is_array($data['idproducto_insumo'])) {
            foreach ($data['idproducto_insumo'] as $i => $idproductoInsumo) {
                $insumos[] = [
                    'idproducto' => $idproductoInsumo,
                    'cantidad' => $data['cantidad_insumo'][$i] ?? 0,
                    'cantidad_utilizada' => $data['cantidad_utilizada'][$i] ?? 0,
                    'observaciones' => ''
                ];
            }
        }

        // Insertar producción
        $insertId = $this->model->insertProduccion([
            "idempleado" => $idempleado,
            "idproducto" => $idproducto,
            "cantidad_a_realizar" => $cantidad_a_realizar,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "estado" => $estado,
            "insumos" => $insumos
        ]);

        if ($insertId) {
            echo json_encode(["status" => true, "message" => "Producción registrada.", "idproduccion" => $insertId]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al registrar producción."]);
        }

    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit();
}

    // Método para actualizar una producción existente
   public function updateProduccion()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !is_array($data)) {
        echo json_encode(["status" => false, "message" => "Datos inválidos."]);
        exit();
    }

    $idproduccion = trim($data['idproduccion']) ?? null;
    $idempleado = trim($data['idempleado']) ?? null;
    $idproducto = trim($data['idproducto']) ?? null;
    $cantidad_a_realizar = trim($data['cantidad_a_realizar']) ?? null;
    $fecha_inicio = trim($data['fecha_inicio']) ?? null;
    $fecha_fin = trim($data['fecha_fin']) ?? null;
    $estado = trim($data['estado']) ?? null;
    $insumos = $data['insumos'] ?? [];
    $fecha_modificacion = date("Y-m-d H:i:s");

    if (empty($idproduccion) || empty($idproducto) || empty($cantidad_a_realizar) || empty($fecha_inicio) || empty($estado)) {
        echo json_encode(["status" => false, "message" => "Datos incompletos."]);
        exit();
    }

    if (!is_numeric($cantidad_a_realizar) || $cantidad_a_realizar <= 0) {
        echo json_encode(["status" => false, "message" => "La cantidad debe ser un número positivo."]);
        exit();
    }

    // Actualizar producción
    $result = $this->model->updateProduccion([
        "idproduccion" => $idproduccion,
        "idempleado" => $idempleado,
        "idproducto" => $idproducto,
        "cantidad_a_realizar" => $cantidad_a_realizar,
        "fecha_inicio" => $fecha_inicio,
        "fecha_fin" => $fecha_fin,
        "estado" => $estado,
        "fecha_modificacion" => $fecha_modificacion,
        "insumos" => $insumos
    ]);

    if ($result) {
        echo json_encode(["status" => true, "message" => "Producción actualizada."]);
    } else {
        echo json_encode(["status" => false, "message" => "Error al actualizar."]);
    }
    exit();
}

    // Método para eliminar una producción
    public function deleteProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idproduccion = $_POST['idproduccion'];

            // Eliminar la producción de la base de datos
            $result = $this->get_model()->deleteProduccion($idproduccion);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Producción eliminada exitosamente"]);
            } else {
                echo json_encode(["status" => false, "message" => "Error al eliminar la producción"]);
            }
        }
    }

    // Método para cambiar el estado de una producción
    public function getProduccionById($idproduccion)
    {
        try {
            $produccion = $this->get_model()->getProduccionById($idproduccion);

            if ($produccion) {
                echo json_encode(["status" => true, "data" => $produccion]);
            } else {
                echo json_encode(["status" => false, "message" => "Produccion no encontrado."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }
    public function getEmpleado()
    {
        $empleados = $this->model->SelectAllEmpleado();

        if ($empleados) {
            echo json_encode(["status" => true, "data" => $empleados]);
        } else {
            echo json_encode(["status" => false, "message" => "No se encontraron categorías."]);
        }
        exit(); // Asegura que la respuesta termine aquí
    }
     public function getProductos()
    {
        $producto = $this->model->SelectAllProducto();

        if ($producto) {
            echo json_encode(["status" => true, "data" => $producto]);
        } else {
            echo json_encode(["status" => false, "message" => "No se encontraron categorías."]);
        }
        exit(); // Asegura que la respuesta termine aquí
    }
}
