<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Dashboard extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['page_title'] = "Dashboard";
        $data['page_name'] = "Dashboard";
        $data['page_functions_js'] = "functions_dashboard.js";

        $this->views->getView($this, "dashboard", $data);
    }

    public function getDashboardData()
    {
        $model = new DashboardModel();

        echo json_encode([
            "resumen" => $model->getResumen(),
            "ventas" => $model->getUltimasVentas(),
            "tareas" => $model->getTareasPendientes(),
            "ventasMensuales" => $model->getVentasMensuales(),
        ]);
        exit();
    }
}