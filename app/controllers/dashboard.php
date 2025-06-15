<?php

require_once "app/core/Controllers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/helpers.php";

class Dashboard extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar que el usuario tenga acceso al dashboard
        PermisosVerificar::verificarAccesoModulo('dashboard');
    }

    public function index()
    {
        $data['page_title'] = "Dashboard - Panel Principal";
        $data['page_name'] = "dashboard";
        $data['page_functions_js'] = "functions_dashboard.js";
        
        // Obtener información del usuario actual
        $data['usuario_nombre'] = $_SESSION['usuario_nombre'] ?? $_SESSION['user']['nombre'] ?? 'Usuario';

        $this->views->getView($this, "dashboard", $data);
    }

    public function getDashboardData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método no permitido']);
            exit();
        }

        try {
            $resumen = $this->model->getResumen();
            $compras = $this->model->getUltimasCompras();
            $ventas = $this->model->getUltimasVentas();
            $tareas = $this->model->getTareasPendientes();
            $ventasMensuales = $this->model->getVentasMensuales();
            $comprasMensuales = $this->model->getComprasMensuales();
            $alertas = $this->model->getAlertas();
            $stockBajo = $this->model->getProductosBajoStock();

            echo json_encode([
                'status' => true,
                'data' => [
                    'resumen' => $resumen,
                    'compras' => $compras,
                    'ventas' => $ventas,
                    'tareas' => $tareas,
                    'ventasMensuales' => $ventasMensuales,
                    'comprasMensuales' => $comprasMensuales,
                    'alertas' => $alertas,
                    'stockBajo' => $stockBajo
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en getDashboardData: " . $e->getMessage());
            echo json_encode([
                'status' => false, 
                'message' => 'Error al cargar los datos del dashboard'
            ]);
        }
        exit();
    }

    public function getStatsRealTime()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método no permitido']);
            exit();
        }

        try {
            $stats = $this->model->getResumen();
            echo json_encode(['status' => true, 'data' => $stats]);
        } catch (Exception $e) {
            error_log("Error en getStatsRealTime: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error al obtener estadísticas']);
        }
        exit();
    }
}