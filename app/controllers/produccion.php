<?php
require_once "app/core/Controllers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/helpers.php";
require_once "app/models/produccionModel.php";
require_once "app/models/tareaProduccionModel.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";
require_once "app/models/PermisosModel.php";
class Produccion extends Controllers
{
    private $tarea_model;
    public function __construct()
    {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }
        PermisosModuloVerificar::verificarAccesoModulo('Produccion');
        
        
        $this->model = new produccionModel();
        $this->tarea_model = new TareaProduccionModel();
    }

 private function verificarUsuarioLogueado(): bool
    {
        
        $tieneLogin = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $tieneIdUser = isset($_SESSION['idUser']) && !empty($_SESSION['idUser']);
        $tieneUsuarioId = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
        
        return $tieneLogin && ($tieneIdUser || $tieneUsuarioId);
    }

    /**
     * Obtiene el ID del usuario de la sesión
     */
    private function obtenerIdUsuario(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }
private function redirigirLogin()
    {
        if (function_exists('base_url')) {
            $loginUrl = base_url() . '/login';
        } else {
            $loginUrl = '/project/login';
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }
    public function index()
    {
         
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }

        
        $idUsuario = $this->obtenerIdUsuario();
        
        if (!$idUsuario) {
            error_log("Produccion::index - No se pudo obtener ID de usuario");
            $this->redirigirLogin();
            return;
        }

      
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_functions_js'] = "functions_produccion.js";
   
        $this->views->getView($this, "produccion", $data);
    }

    
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

    
    public function createProduccion()
    {

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos.");
            }

            
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
    public function getEstadisticas()
    {
        header('Content-Type: application/json');

        try {
            $total = $this->model->getTotalProducciones();
            $clasificacion = $this->model->getProduccionesEnClasificacion();
            $finalizadas = $this->model->getProduccionesFinalizadas();

            echo json_encode([
                "status" => true,
                "data" => compact("total", "clasificacion", "finalizadas")
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => $e->getMessage()
            ]);
        }

        exit();
    }
    public function getTareasByProduccion($idproduccion)
    {
        header('Content-Type: application/json');

        try {
            $tareaModel = new TareaProduccionModel();
            $tareas = $tareaModel->getTareasByProduccion($idproduccion);

            echo json_encode(["status" => true, "data" => $tareas]);
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error interno"]);
        }

        exit();
    }


    public function updateTarea()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !is_array($data)) {
        echo json_encode(["status" => false, "message" => "Datos inválidos."]);
        exit();
    }

    try {
        

        $tareaModel = new TareaProduccionModel();
        $result = $tareaModel->updateTarea($data);

        echo json_encode([
            "status" => $result,
            "message" => $result ? "Tarea actualizada." : "No se pudo actualizar."
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error interno: " . $e->getMessage()]);
    }

    exit();
}



    public function asignarTarea()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "Datos inválidos."]);
            exit();
        }

        try {
            $tareaModel = new TareaProduccionModel();
            $result = $tareaModel->insertTarea($data);

            if ($result) {
                echo json_encode(["status" => true, "message" => "Tarea asignada correctamente."]);
            } else {
                echo json_encode(["status" => false, "message" => "No se pudo asignar la tarea."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => $e->getMessage()]);
        }

        exit();
    }
}
