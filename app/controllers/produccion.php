<?php
require_once "app/core/Controllers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/helpers.php";
require_once "app/models/ProduccionModel.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";
require_once "app/models/PermisosModel.php";

class Produccion extends Controllers
{
    private $produccionModel;

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
        
        $this->produccionModel = new ProduccionModel();
    }

    private function verificarUsuarioLogueado(): bool
    {
        $tieneLogin = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $tieneIdUser = isset($_SESSION['idUser']) && !empty($_SESSION['idUser']);
        $tieneUsuarioId = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
        
        return $tieneLogin && ($tieneIdUser || $tieneUsuarioId);
    }

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

    // ===== VISTA PRINCIPAL =====

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

        $data['page_title'] = "Control de Producción";
        $data['page_name'] = "Producción";
        $data['page_functions_js'] = "functions_produccion.js";
   
        $this->views->getView($this, "produccion", $data);
    }

    // ===== GESTIÓN DE LOTES =====

    public function createLote()
    {
        header('Content-Type: application/json');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos");
            }

            // Validaciones básicas
            $requiredFields = ['numero_lote', 'supervisor', 'meta_total', 'fecha_inicio'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("El campo '$field' es obligatorio");
                }
            }

            $result = $this->produccionModel->createLote($data);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function updateLote()
    {
        header('Content-Type: application/json');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data) || !isset($data['idproduccion'])) {
                throw new Exception("Datos inválidos");
            }

            $result = $this->produccionModel->updateLote($data);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function deleteLote()
    {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Método no permitido");
            }

            $idproduccion = $_POST['idproduccion'] ?? '';
            if (!$idproduccion || !is_numeric($idproduccion)) {
                throw new Exception("ID inválido");
            }

            $result = $this->produccionModel->deleteLote(intval($idproduccion));
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getLotesActivos()
    {
        header('Content-Type: application/json');
        
        try {
            $lotes = $this->produccionModel->getLotesActivos();
            echo json_encode([
                'status' => true,
                'data' => $lotes
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getLoteById($idproduccion)
    {
        header('Content-Type: application/json');
        
        try {
            if (!is_numeric($idproduccion)) {
                throw new Exception("ID inválido");
            }

            $result = $this->produccionModel->getLoteById(intval($idproduccion));
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getDetalleLote($idproduccion)
    {
        header('Content-Type: application/json');
        
        try {
            if (!is_numeric($idproduccion)) {
                throw new Exception("ID inválido");
            }

            $result = $this->produccionModel->getDetalleLote(intval($idproduccion));
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // ===== REGISTRO DIARIO =====

    public function registrarTrabajo()
    {
        header('Content-Type: application/json');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos");
            }

            // Validaciones básicas
            $requiredFields = ['lote', 'proceso', 'empleado', 'producto', 'cantidad_asignada'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("El campo '$field' es obligatorio");
                }
            }

            $result = $this->produccionModel->registrarTrabajo($data);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getRegistrosDiarios()
    {
        header('Content-Type: application/json');
        
        try {
            $fecha = $_GET['fecha'] ?? null;
            $registros = $this->produccionModel->getRegistrosDiarios($fecha);
            
            echo json_encode([
                'status' => true,
                'data' => $registros
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // ===== CONTROL DE CALIDAD =====

    public function registrarControlCalidad()
    {
        header('Content-Type: application/json');
        
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                throw new Exception("Datos inválidos");
            }

            $result = $this->produccionModel->registrarControlCalidad($data);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // ===== ESTADÍSTICAS =====

    public function getEstadisticasDiarias()
    {
        header('Content-Type: application/json');
        
        try {
            $fecha = $_GET['fecha'] ?? null;
            $estadisticas = $this->produccionModel->getEstadisticasDiarias($fecha);
            
            echo json_encode([
                'status' => true,
                'data' => $estadisticas
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // ===== DATOS PARA SELECTS =====

    public function getProcesos()
    {
        header('Content-Type: application/json');
        
        try {
            $procesos = $this->produccionModel->getProcesos();
            echo json_encode([
                'status' => true,
                'data' => $procesos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getEmpleados()
    {
        header('Content-Type: application/json');
        
        try {
            $empleados = $this->produccionModel->getEmpleados();
            echo json_encode([
                'status' => true,
                'data' => $empleados
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getSupervisores()
    {
        header('Content-Type: application/json');
        
        try {
            $supervisores = $this->produccionModel->getSupervisores();
            echo json_encode([
                'status' => true,
                'data' => $supervisores
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getProductos()
    {
        header('Content-Type: application/json');
        
        try {
            $productos = $this->produccionModel->getProductos();
            echo json_encode([
                'status' => true,
                'data' => $productos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function getUnidadMedida($idproceso, $idproducto)
    {
        header('Content-Type: application/json');
        
        try {
            if (!is_numeric($idproceso) || !is_numeric($idproducto)) {
                throw new Exception("IDs inválidos");
            }

            $result = $this->produccionModel->getUnidadMedida(intval($idproceso), intval($idproducto));
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // ===== REPORTES =====

    public function reporteEmpleado()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $data = $this->produccionModel->getReporteEmpleado($fechaInicio, $fechaFin);
            
            // Aquí puedes generar PDF, Excel o mostrar vista de reporte
            // Por ahora, devolver JSON
            header('Content-Type: application/json');
            echo json_encode([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function reporteLote()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $data = $this->produccionModel->getReporteLote($fechaInicio, $fechaFin);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    public function reporteMaterial()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $data = $this->produccionModel->getReporteMaterial($fechaInicio, $fechaFin);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
}
?>