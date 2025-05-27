<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/produccionModel.php";

class Produccion extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new produccionModel();
    }

    // Vista principal
    public function index()
    {
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }

    // Obtener listado de producciones para DataTables
    public function getProduccionData()
    {
        try {
            $arrData = $this->model->SelectAllProducciones();

            echo json_encode([
                "draw" => intval($_GET['draw']),
                "recordsTotal" => count($arrData),
                "recordsFiltered" => count($arrData),
                "data" => $arrData
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener los datos: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Obtener detalle de una producción
    public function getDetalleProduccionData($idproduccion)
    {
        try {
            if (!is_numeric($idproduccion)) {
                throw new Exception("ID inválido.");
            }

            $detalle = $this->model->SelectDetalleProduccion($idproduccion);

            echo json_encode([
                "status" => true,
                "data" => $detalle
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener el detalle: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Obtener producción por ID
    public function getProduccionById($idproduccion)
    {
        try {
            if (!is_numeric($idproduccion)) {
                throw new Exception("ID inválido.");
            }

            $produccion = $this->model->getProduccionById($idproduccion);

            if (!$produccion) {
                throw new Exception("Producción no encontrada.");
            }

            echo json_encode([
                "status" => true,
                "data" => $produccion
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }

    // Registrar nueva producción
    public function createProduccion()
    {

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos.");
            }

            // Campos obligatorios
            $requiredFields = ['idproducto', 'cantidad_a_realizar', 'fecha_inicio'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("El campo '$field' es obligatorio.");
                }
            }

            $idempleado = trim($data['idempleado'] ?? '');
            $idproducto = trim($data['idproducto']);
            $cantidad_a_realizar = floatval($data['cantidad_a_realizar']);
            $fecha_inicio = date("Y-m-d", strtotime($data['fecha_inicio']));
            $fecha_fin = $data['fecha_fin'] ? date("Y-m-d", strtotime($data['fecha_fin'])) : null;
            $estado = trim($data['estado'] ?? 'borrador');
            $insumos = $data['insumos'] ?? [];

            if ($cantidad_a_realizar <= 0) {
                throw new Exception("La cantidad debe ser mayor a cero.");
            }

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
                echo json_encode([
                    "status" => true,
                    "message" => "Producción registrada correctamente.",
                    "idproduccion" => $insertId
                ]);
            } else {
                throw new Exception("No se pudo registrar la producción.");
            }

        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }

    // Actualizar producción
    public function updateProduccion()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos.");
            }

            $idproduccion = trim($data['idproduccion']) ?? null;
            if (!$idproduccion || !is_numeric($idproduccion)) {
                throw new Exception("ID de producción inválido.");
            }

            $idproducto = trim($data['idproducto']);
            $cantidad_a_realizar = floatval($data['cantidad_a_realizar']);
            $fecha_inicio = date("Y-m-d", strtotime($data['fecha_inicio']));
            $fecha_fin = $data['fecha_fin'] ? date("Y-m-d", strtotime($data['fecha_fin'])) : null;
            $estado = trim($data['estado']);
            $insumos = $data['insumos'] ?? [];

            if (!$idproducto || $cantidad_a_realizar <= 0 || !$fecha_inicio || !$estado) {
                throw new Exception("Campos incompletos.");
            }

            $result = $this->model->updateProduccion([
                "idproduccion" => $idproduccion,
                "idempleado" => $data['idempleado'] ?? '',
                "idproducto" => $idproducto,
                "cantidad_a_realizar" => $cantidad_a_realizar,
                "fecha_inicio" => $fecha_inicio,
                "fecha_fin" => $fecha_fin,
                "estado" => $estado,
                "fecha_modificacion" => date("Y-m-d H:i:s"),
                "insumos" => $insumos
            ]);

            if ($result) {
                echo json_encode([
                    "status" => true,
                    "message" => "Producción actualizada correctamente."
                ]);
            } else {
                throw new Exception("No se pudo actualizar la producción.");
            }

        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }

    // Eliminar producción
    public function deleteProduccion()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Método no permitido.");
            }

            $idproduccion = $_POST['idproduccion'] ?? '';
            if (!$idproduccion || !is_numeric($idproduccion)) {
                throw new Exception("ID inválido.");
            }

            $result = $this->model->deleteProduccion($idproduccion);

            if ($result) {
                echo json_encode([
                    "status" => true,
                    "message" => "Producción eliminada correctamente."
                ]);
            } else {
                throw new Exception("No se pudo eliminar la producción.");
            }

        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }

    // Buscar empleados
    public function getEmpleado()
    {
        try {
            $empleados = $this->model->SelectAllEmpleado();

            if ($empleados) {
                echo json_encode([
                    "status" => true,
                    "data" => $empleados
                ]);
            } else {
                throw new Exception("No se encontraron empleados.");
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }

    // Buscar productos
    public function getProductos()
    {
        try {
            $productos = $this->model->SelectAllProducto();

            if ($productos) {
                echo json_encode([
                    "status" => true,
                    "data" => $productos
                ]);
            } else {
                throw new Exception("No se encontraron productos.");
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit();
    }
}