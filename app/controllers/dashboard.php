<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/Models/DashboardModel.php";

class Dashboard extends Controllers
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    $model = new DashboardModel();
    $data["page_title"] = "Dashboard";
    $data["page_name"] = "Dashboard";
    $data["page_functions_js"] = "functions_dashboard.js";
    // NUEVO: Pasar los tipos de pago a la vista para llenar el filtro
    $data["tipos_pago"] = $model->getTiposDePago();

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

  public function getDashboardData()
  {
    $model = new DashboardModel();
    $fecha_desde_ingresos = $this->validarYFormatearFecha(
      $_GET["fecha_desde_ingresos"] ?? null,
      "inicio"
    );
    $fecha_hasta_ingresos = $this->validarYFormatearFecha(
      $_GET["fecha_hasta_ingresos"] ?? null,
      "fin"
    );
    $fecha_desde_egresos = $this->validarYFormatearFecha(
      $_GET["fecha_desde_egresos"] ?? null,
      "inicio"
    );
    $fecha_hasta_egresos = $this->validarYFormatearFecha(
      $_GET["fecha_hasta_egresos"] ?? null,
      "fin"
    );

    // MODIFICADO: Recoger y validar el filtro de tipo de pago
    $idtipo_pago = filter_var(
      $_GET["idtipo_pago"] ?? null,
      FILTER_VALIDATE_INT
    );
    $idtipo_pago = $idtipo_pago ?: null; // Convierte 0 o false a null

    if (
      $fecha_desde_ingresos > $fecha_hasta_ingresos ||
      $fecha_desde_egresos > $fecha_hasta_egresos
    ) {
      http_response_code(400);
      echo json_encode(["error" => "El rango de fechas proporcionado es inválido."]);
      exit();
    }

    echo json_encode([
      "resumen" => $model->getResumen(),
      "ventas" => $model->getUltimasVentas(),
      "tareas" => $model->getTareasPendientes(),
      "ventasMensuales" => $model->getVentasMensuales(),
      "reporteIngresos" => $model->getIngresosReporte(
        $fecha_desde_ingresos,
        $fecha_hasta_ingresos,
        $idtipo_pago // Pasar el nuevo filtro
      ),
      "reporteEgresos" => $model->getEgresosReporte(
        $fecha_desde_egresos,
        $fecha_hasta_egresos
      ),
    ]);
    exit();
  }

  public function descargarIngresosPDF()
  {
    // 1. Validar fechas
    $fecha_desde = $this->validarYFormatearFecha(
      $_GET["fecha_desde"] ?? null,
      "inicio"
    );
    $fecha_hasta = $this->validarYFormatearFecha(
      $_GET["fecha_hasta"] ?? null,
      "fin"
    );

    // MODIFICADO: Recoger y validar el filtro de tipo de pago
    $idtipo_pago = filter_var(
      $_GET["idtipo_pago"] ?? null,
      FILTER_VALIDATE_INT
    );
    $idtipo_pago = $idtipo_pago ?: null;

    if ($fecha_desde > $fecha_hasta) {
      die("Error: Rango de fechas inválido.");
    }

    // 2. Obtener datos con el filtro aplicado
    $model = new DashboardModel();
    $ingresosDetallados = $model->getIngresosDetallados(
      $fecha_desde,
      $fecha_hasta,
      $idtipo_pago
    );
    $ingresosResumen = $model->getIngresosReporte(
      $fecha_desde,
      $fecha_hasta,
      $idtipo_pago
    );

    // 3. Generar PDF
    $pdf = new FPDF("P", "mm", "Letter");
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", 16);
    $pdf->Cell(0, 10, "Reporte Detallado de Ingresos", 0, 1, "C");
    $pdf->SetFont("Arial", "", 12);
    $pdf->Cell(
      0,
      10,
      "Periodo: " . $fecha_desde . " al " . $fecha_hasta,
      0,
      1,
      "C"
    );
    $pdf->Ln(5);

    // Tabla de Pagos Detallados
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
        $pdf->Cell(60, 7, utf8_decode($pago["cliente"]), 1, 0);
        $pdf->Cell(30, 7, utf8_decode($pago["tipo_pago"]), 1, 0);
        $pdf->Cell(30, 7, $pago["referencia"], 1, 0);
        $pdf->Cell(
          30,
          7,
          number_format($pago["monto"], 2, ",", ".") . " Bs.",
          1,
          1,
          "R"
        );
        $totalGeneral += $pago["monto"];
      }
    }
    $pdf->SetFont("Arial", "B", 10);
    $pdf->Cell(165, 8, "TOTAL GENERAL", 1, 0, "R");
    $pdf->Cell(
      30,
      8,
      number_format($totalGeneral, 2, ",", ".") . " Bs.",
      1,
      1,
      "R"
    );

    $pdf->Ln(10);

    // Tabla de Resumen por Categoría
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
        $pdf->Cell(135, 7, utf8_decode($resumen["categoria"]), 1, 0);
        $pdf->Cell(
          60,
          7,
          number_format($resumen["total"], 2, ",", ".") . " Bs.",
          1,
          1,
          "R"
        );
      }
    }

    // 4. Enviar PDF al navegador
    $pdf->Output("D", "Reporte_Ingresos_Detallado_" . date("Y-m-d") . ".pdf");
  }
}