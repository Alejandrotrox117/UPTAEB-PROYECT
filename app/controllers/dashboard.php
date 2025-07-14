<?php
require_once "app/core/Controllers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/helpers.php";
require_once "app/Models/DashboardModel.php";
// require_once "vendor/pdf/fpdf.php";
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont("Arial", "B", 14);
        $this->Cell(0, 7, mb_convert_encoding("Recuperadora La Pradera de Pavia, C.A.", 'ISO-8859-1', 'UTF-8'), 0, 1, "C");
        $this->SetFont("Arial", "", 10);
        $this->Cell(0, 5, "RIF: J-40352739-3", 0, 1, "C");
        $this->Cell(0, 5, mb_convert_encoding("Ctra. Vieja a Carora, Km. 12, Pavia, Barquisimeto, Edo. Lara.", 'ISO-8859-1', 'UTF-8'), 0, 1, "C");
        $this->Cell(0, 5, "Telf: 0426-550.00.30", 0, 1, "C");
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont("Arial", "I", 8);
        $this->Cell(0, 10, mb_convert_encoding("Página ", 'ISO-8859-1', 'UTF-8') . $this->PageNo() . "/{nb}", 0, 0, "C");
    }
}

class Dashboard extends Controllers
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new DashboardModel();
    }

    /**
     * Helper method to replace deprecated utf8_decode function
     * Converts UTF-8 text to ISO-8859-1 for PDF compatibility
     */
    private function convertTextForPDF($text)
    {
        if (empty($text)) {
            return $text;
        }
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }

    public function index()
    {
        $data = [
            "page_title" => "Dashboard Ejecutivo",
            "page_name" => "Dashboard",
            "page_functions_js" => "functions_dashboard.js",
            
            "tipos_pago" => $this->model->getTiposDePago(),
            "tipos_egreso" => ["Compras", "Sueldos", "Otros Egresos"],
            "proveedores" => $this->model->getProveedoresActivos(),
            "productos" => $this->model->getProductos(),
            
            "empleados" => $this->model->getEmpleadosActivos(),
        ];
        $this->views->getView($this, "dashboard", $data);
    }

    private function validarYFormatearFecha($dateString, $type = "inicio")
    {
        if ($dateString) {
            $d = DateTime::createFromFormat("Y-m-d", $dateString);
            if ($d && $d->format("Y-m-d") === $dateString) {
                return $dateString;
            }
        }
        return $type === "inicio" ? date("Y-m-01") : date("Y-m-t");
    }

    private function validarRangoFechas($desde, $hasta)
    {
        return $desde <= $hasta;
    }

    
    public function getDashboardData()
    {
        
        $fecha_desde_ingresos = $this->validarYFormatearFecha($_GET["fecha_desde_ingresos"] ?? null, "inicio");
        $fecha_hasta_ingresos = $this->validarYFormatearFecha($_GET["fecha_hasta_ingresos"] ?? null, "fin");
        $idtipo_pago_ingresos = filter_var($_GET["idtipo_pago_ingresos"] ?? null, FILTER_VALIDATE_INT) ?: null;

        
        $fecha_desde_egresos = $this->validarYFormatearFecha($_GET["fecha_desde_egresos"] ?? null, "inicio");
        $fecha_hasta_egresos = $this->validarYFormatearFecha($_GET["fecha_hasta_egresos"] ?? null, "fin");
        $idtipo_pago_egresos = filter_var($_GET["idtipo_pago_egresos"] ?? null, FILTER_VALIDATE_INT) ?: null;
        $tipo_egreso = isset($_GET["tipo_egreso"]) ? trim(strip_tags($_GET["tipo_egreso"])) : null;
        $tipo_egreso = in_array($tipo_egreso, ["Compras", "Sueldos", "Otros Egresos"]) ? $tipo_egreso : null;

        if (!$this->validarRangoFechas($fecha_desde_ingresos, $fecha_hasta_ingresos) ||
            !$this->validarRangoFechas($fecha_desde_egresos, $fecha_hasta_egresos)) {
            http_response_code(400);
            echo json_encode(["error" => "El rango de fechas proporcionado es inválido."]);
            exit();
        }

        $response = [
            "resumen" => $this->model->getResumen(),
            "ventas" => $this->model->getUltimasVentas(),
            "ventasMensuales" => $this->model->getVentasMensuales(),
            "reporteIngresos" => $this->model->getIngresosReporte($fecha_desde_ingresos, $fecha_hasta_ingresos, $idtipo_pago_ingresos),
            "reporteEgresos" => $this->model->getEgresosReporte($fecha_desde_egresos, $fecha_hasta_egresos, $idtipo_pago_egresos, $tipo_egreso),
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    
    public function getDashboardAvanzado()
    {
        
        $prod_fecha_desde = $this->validarYFormatearFecha($_GET["prod_fecha_desde"] ?? null, "inicio");
        $prod_fecha_hasta = $this->validarYFormatearFecha($_GET["prod_fecha_hasta"] ?? null, "fin");
        $prod_empleado = filter_var($_GET["prod_empleado"] ?? null, FILTER_VALIDATE_INT) ?: null;
        $prod_estado = isset($_GET["prod_estado"]) ? trim(strip_tags($_GET["prod_estado"])) : '';

        if (!$this->validarRangoFechas($prod_fecha_desde, $prod_fecha_hasta)) {
            http_response_code(400);
            echo json_encode(["error" => "El rango de fechas de producción es inválido."]);
            exit();
        }

        try {
            $response = [
                
                "kpisEjecutivos" => $this->model->getKPIsEjecutivos(),
                
                
                "tendenciasVentas" => $this->model->getTendenciasVentas(),
                "rentabilidadProductos" => $this->model->getRentabilidadProductos(),
                
                
                "eficienciaEmpleados" => $this->model->getEficienciaEmpleados($prod_fecha_desde, $prod_fecha_hasta, $prod_empleado, $prod_estado),
                "estadosProduccion" => $this->model->getEstadosProduccion($prod_fecha_desde, $prod_fecha_hasta),
                "cumplimientoTareas" => $this->model->getCumplimientoTareas($prod_fecha_desde, $prod_fecha_hasta),
                
                
                "topClientes" => $this->model->getTopClientes(),
                "topProveedores" => $this->model->getTopProveedores(),
                
                
                "analisisInventario" => $this->model->getAnalisisInventario(),
                
                
                "kpisTiempoReal" => $this->model->getKPIsTiempoReal(),
            ];

            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getDashboardAvanzado: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Error interno del servidor"]);
        }
        exit();
    }

    
    public function descargarIngresosPDF()
    {
        $fecha_desde = $this->validarYFormatearFecha($_GET["fecha_desde"] ?? null, "inicio");
        $fecha_hasta = $this->validarYFormatearFecha($_GET["fecha_hasta"] ?? null, "fin");
        $idtipo_pago = filter_var($_GET["idtipo_pago"] ?? null, FILTER_VALIDATE_INT) ?: null;

        if (!$this->validarRangoFechas($fecha_desde, $fecha_hasta)) {
            die("Error: Rango de fechas inválido.");
        }

        $ingresosDetallados = $this->model->getIngresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago);
        $ingresosResumen = $this->model->getIngresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago);

        $pdf = new PDF("P", "mm", "Letter");
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont("Arial", "B", 16);
        $pdf->Cell(0, 10, "Reporte Detallado de Ingresos", 0, 1, "C");
        $pdf->SetFont("Arial", "", 12);
        $pdf->Cell(0, 10, "Periodo: $fecha_desde al $fecha_hasta", 0, 1, "C");
        $pdf->Ln(5);

        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(0, 10, "Detalle de Pagos Recibidos", 0, 1, "L");
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(20, 7, "Fecha", 1, 0, "C", true);
        $pdf->Cell(25, 7, "Nro. Venta", 1, 0, "C", true);
        $pdf->Cell(60, 7, "Cliente", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Tipo Pago", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Referencia", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Monto", 1, 1, "C", true);

        $pdf->SetFont("Arial", "", 9);
        $totalGeneral = 0;
        if (empty($ingresosDetallados)) {
            $pdf->Cell(195, 10, "No se encontraron pagos con los filtros aplicados.", 1, 1, "C");
        } else {
            foreach ($ingresosDetallados as $pago) {
                $pdf->Cell(20, 7, $pago["fecha_pago"], 1, 0);
                $pdf->Cell(25, 7, $pago["nro_venta"], 1, 0);
                $pdf->Cell(60, 7, mb_convert_encoding($pago["cliente"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(30, 7, mb_convert_encoding($pago["tipo_pago"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(30, 7, $pago["referencia"], 1, 0);
                $pdf->Cell(30, 7, number_format($pago["monto"], 2, ",", ".") . " Bs.", 1, 1, "R");
                $totalGeneral += $pago["monto"];
            }
        }
        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(165, 8, "TOTAL GENERAL", 1, 0, "R");
        $pdf->Cell(30, 8, number_format($totalGeneral, 2, ",", ".") . " Bs.", 1, 1, "R");

        $pdf->Ln(10);

        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(0, 10, "Resumen por Tipo de Pago", 0, 1, "L");
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(135, 7, "Categoria", 1, 0, "C", true);
        $pdf->Cell(60, 7, "Monto Total", 1, 1, "C", true);

        $pdf->SetFont("Arial", "", 9);
        if (empty($ingresosResumen)) {
            $pdf->Cell(195, 10, "Sin datos para resumir.", 1, 1, "C");
        } else {
            foreach ($ingresosResumen as $resumen) {
                $pdf->Cell(135, 7, mb_convert_encoding($resumen["categoria"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(60, 7, number_format($resumen["total"], 2, ",", ".") . " Bs.", 1, 1, "R");
            }
        }

        $pdf->Output("D", "Reporte_Ingresos_Detallado_" . date("Y-m-d") . ".pdf");
    }

    public function descargarEgresosPDF()
    {
        $fecha_desde = $this->validarYFormatearFecha($_GET["fecha_desde"] ?? null, "inicio");
        $fecha_hasta = $this->validarYFormatearFecha($_GET["fecha_hasta"] ?? null, "fin");
        $idtipo_pago = filter_var($_GET["idtipo_pago"] ?? null, FILTER_VALIDATE_INT) ?: null;
        $tipo_egreso = isset($_GET["tipo_egreso"]) ? trim(strip_tags($_GET["tipo_egreso"])) : null;
        $tipo_egreso = in_array($tipo_egreso, ["Compras", "Sueldos", "Otros Egresos"]) ? $tipo_egreso : null;

        if (!$this->validarRangoFechas($fecha_desde, $fecha_hasta)) {
            die("Error: Rango de fechas inválido.");
        }

        $egresosDetallados = $this->model->getEgresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso);
        $egresosResumen = $this->model->getEgresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso);

        $pdf = new PDF("P", "mm", "Letter");
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont("Arial", "B", 16);
        $pdf->Cell(0, 10, "Reporte Detallado de Egresos", 0, 1, "C");
        $pdf->SetFont("Arial", "", 12);
        $pdf->Cell(0, 10, "Periodo: $fecha_desde al $fecha_hasta", 0, 1, "C");
        $pdf->Ln(5);

        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(0, 10, "Detalle de Egresos Realizados", 0, 1, "L");
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(20, 7, "Fecha", 1, 0, "C", true);
        $pdf->Cell(75, 7, "Descripcion", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Tipo Pago", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Referencia", 1, 0, "C", true);
        $pdf->Cell(40, 7, "Monto", 1, 1, "C", true);

        $pdf->SetFont("Arial", "", 9);
        $totalGeneral = 0;
        if (empty($egresosDetallados)) {
            $pdf->Cell(195, 10, "No se encontraron egresos con los filtros aplicados.", 1, 1, "C");
        } else {
            foreach ($egresosDetallados as $pago) {
                $pdf->Cell(20, 7, $pago["fecha_pago"], 1, 0);
                $pdf->Cell(75, 7, mb_convert_encoding($pago["descripcion"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(30, 7, mb_convert_encoding($pago["tipo_pago"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(30, 7, $pago["referencia"], 1, 0);
                $pdf->Cell(40, 7, number_format($pago["monto"], 2, ",", ".") . " Bs.", 1, 1, "R");
                $totalGeneral += $pago["monto"];
            }
        }
        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(155, 8, "TOTAL GENERAL", 1, 0, "R");
        $pdf->Cell(40, 8, number_format($totalGeneral, 2, ",", ".") . " Bs.", 1, 1, "R");

        $pdf->Ln(10);

        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(0, 10, "Resumen por Tipo de Egreso", 0, 1, "L");
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(135, 7, "Categoria", 1, 0, "C", true);
        $pdf->Cell(60, 7, "Monto Total", 1, 1, "C", true);

        $pdf->SetFont("Arial", "", 9);
        if (empty($egresosResumen)) {
            $pdf->Cell(195, 10, "Sin datos para resumir.", 1, 1, "C");
        } else {
            foreach ($egresosResumen as $resumen) {
                $pdf->Cell(135, 7, mb_convert_encoding($resumen["categoria"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(60, 7, number_format($resumen["total"], 2, ",", ".") . " Bs.", 1, 1, "R");
            }
        }

        $pdf->Output("D", "Reporte_Egresos_Detallado_" . date("Y-m-d") . ".pdf");
    }

    public function getReporteComprasData()
    {
        $fecha_desde = $this->validarYFormatearFecha($_GET["fecha_desde"] ?? null, "inicio");
        $fecha_hasta = $this->validarYFormatearFecha($_GET["fecha_hasta"] ?? null, "fin");
        $idproveedor = filter_var($_GET["idproveedor"] ?? null, FILTER_VALIDATE_INT) ?: null;
        $idproducto = filter_var($_GET["idproducto"] ?? null, FILTER_VALIDATE_INT) ?: null;

        if (!$this->validarRangoFechas($fecha_desde, $fecha_hasta)) {
            http_response_code(400);
            echo json_encode(["error" => "El rango de fechas es inválido."]);
            exit();
        }

        $data = $this->model->getReporteCompras($fecha_desde, $fecha_hasta, $idproveedor, $idproducto);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function descargarReporteComprasPDF()
    {
        $fecha_desde = $this->validarYFormatearFecha($_GET["fecha_desde"] ?? null, "inicio");
        $fecha_hasta = $this->validarYFormatearFecha($_GET["fecha_hasta"] ?? null, "fin");
        $idproveedor = filter_var($_GET["idproveedor"] ?? null, FILTER_VALIDATE_INT) ?: null;
        $idproducto = filter_var($_GET["idproducto"] ?? null, FILTER_VALIDATE_INT) ?: null;

        if (!$this->validarRangoFechas($fecha_desde, $fecha_hasta)) {
            die("Error: Rango de fechas inválido.");
        }

        $compras = $this->model->getReporteCompras($fecha_desde, $fecha_hasta, $idproveedor, $idproducto);

        $pdf = new PDF("L", "mm", "Letter");
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont("Arial", "B", 16);
        $pdf->Cell(0, 10, "Reporte de Compras Finalizadas", 0, 1, "C");
        $pdf->SetFont("Arial", "", 12);
        $pdf->Cell(0, 10, "Periodo: $fecha_desde al $fecha_hasta", 0, 1, "C");
        $pdf->Ln(5);

        $pdf->SetFont("Arial", "B", 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(20, 7, "Fecha", 1, 0, "C", true);
        $pdf->Cell(25, 7, "Nro. Compra", 1, 0, "C", true);
        $pdf->Cell(50, 7, "Proveedor", 1, 0, "C", true);
        $pdf->Cell(60, 7, "Producto", 1, 0, "C", true);
        $pdf->Cell(25, 7, "Cantidad", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Precio Unit.", 1, 0, "C", true);
        $pdf->Cell(30, 7, "Subtotal", 1, 1, "C", true);

        $pdf->SetFont("Arial", "", 8);
        $totalGeneral = 0;
        if (empty($compras)) {
            $pdf->Cell(240, 10, "No se encontraron compras con los filtros aplicados.", 1, 1, "C");
        } else {
            foreach ($compras as $item) {
                $pdf->Cell(20, 7, $item["fecha"], 1, 0);
                $pdf->Cell(25, 7, $item["nro_compra"], 1, 0);
                $pdf->Cell(50, 7, mb_convert_encoding($item["proveedor"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(60, 7, mb_convert_encoding($item["producto"], 'ISO-8859-1', 'UTF-8'), 1, 0);
                $pdf->Cell(25, 7, number_format($item["cantidad"], 2, ",", "."), 1, 0, "R");
                $pdf->Cell(30, 7, number_format($item["precio_unitario_compra"], 2, ",", "."), 1, 0, "R");
                $pdf->Cell(30, 7, number_format($item["subtotal_linea"], 2, ",", "."), 1, 1, "R");
                $totalGeneral += $item["subtotal_linea"];
            }
        }
        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(210, 8, "TOTAL GENERAL", 1, 0, "R");
        $pdf->Cell(30, 8, number_format($totalGeneral, 2, ",", ".") . " Bs.", 1, 1, "R");

        $pdf->Output("D", "Reporte_Compras_" . date("Y-m-d") . ".pdf");
    }

    
    public function descargarReporteEjecutivoPDF()
    {
        $kpis = $this->model->getKPIsEjecutivos();
        $topClientes = $this->model->getTopClientes(5);
        $topProveedores = $this->model->getTopProveedores(5);

        $pdf = new PDF("P", "mm", "Letter");
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->SetFont("Arial", "B", 18);
        $pdf->Cell(0, 15, "Reporte Ejecutivo", 0, 1, "C");
        $pdf->SetFont("Arial", "", 12);
        $pdf->Cell(0, 10, "Generado el: " . date("d/m/Y H:i:s"), 0, 1, "C");
        $pdf->Ln(10);

        
        $pdf->SetFont("Arial", "B", 14);
        $pdf->Cell(0, 10, "Indicadores Clave de Rendimiento (Mes Actual)", 0, 1, "L");
        $pdf->SetFont("Arial", "", 10);
        $pdf->Cell(95, 8, "Margen de Ganancia: " . number_format($kpis['margen_ganancia'] ?? 0, 2) . "%", 1, 0, 'L');
        $pdf->Cell(95, 8, "ROI del Mes: " . number_format($kpis['roi_mes'] ?? 0, 2) . "%", 1, 1, 'L');
        $pdf->Cell(95, 8, "Rotacion de Inventario: " . number_format($kpis['rotacion_inventario'] ?? 0, 0) . " dias", 1, 0, 'L');
        $pdf->Cell(95, 8, "Productividad General: " . number_format($kpis['productividad_general'] ?? 0, 2) . " kg/dia", 1, 1, 'L');
        $pdf->Ln(10);

        
        $pdf->SetFont("Arial", "B", 14);
        $pdf->Cell(0, 10, "Top 5 Clientes (Historico)", 0, 1, "L");
        $pdf->SetFont("Arial", "B", 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(90, 7, "Cliente", 1, 0, "L", true);
        $pdf->Cell(30, 7, "Compras", 1, 0, "C", true);
        $pdf->Cell(35, 7, "Total (Bs.)", 1, 0, "R", true);
        $pdf->Cell(35, 7, "Promedio (Bs.)", 1, 1, "R", true);

        $pdf->SetFont("Arial", "", 8);
        foreach ($topClientes as $cliente) {
            $pdf->Cell(90, 6, mb_convert_encoding($cliente['cliente_nombre'], 'ISO-8859-1', 'UTF-8'), 1, 0);
            $pdf->Cell(30, 6, $cliente['num_compras'], 1, 0, "C");
            $pdf->Cell(35, 6, number_format($cliente['total_comprado'], 2, ',', '.'), 1, 0, "R");
            $pdf->Cell(35, 6, number_format($cliente['ticket_promedio'], 2, ',', '.'), 1, 1, "R");
        }
        $pdf->Ln(10);

        
        $pdf->SetFont("Arial", "B", 14);
        $pdf->Cell(0, 10, "Top 5 Proveedores (Historico)", 0, 1, "L");
        $pdf->SetFont("Arial", "B", 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(120, 7, "Proveedor", 1, 0, "L", true);
        $pdf->Cell(30, 7, "Compras", 1, 0, "C", true);
        $pdf->Cell(40, 7, "Total Comprado (Bs.)", 1, 1, "R", true);

        $pdf->SetFont("Arial", "", 8);
        foreach ($topProveedores as $proveedor) {
            $pdf->Cell(120, 6, mb_convert_encoding($proveedor['proveedor_nombre'], 'ISO-8859-1', 'UTF-8'), 1, 0);
            $pdf->Cell(30, 6, $proveedor['num_compras'], 1, 0, "C");
            $pdf->Cell(40, 6, number_format($proveedor['total_comprado'], 2, ',', '.'), 1, 1, "R");
        }

        $pdf->Output("D", "Reporte_Ejecutivo_" . date("Y-m-d") . ".pdf");
    }
     public function getMovimientosInventarioMes()
    {
        try {
            $data = $this->model->getMovimientosInventarioMes();
            header('Content-Type: application/json');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error interno del servidor"]);
        }
        exit();
    }
}
?>