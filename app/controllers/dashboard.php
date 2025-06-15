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
    $data["page_title"] = "Dashboard";
    $data["page_name"] = "Dashboard";
    $data["page_functions_js"] = "functions_dashboard.js";

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
        $fecha_hasta_ingresos
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
    $fecha_desde = $this->validarYFormatearFecha(
      $_GET["fecha_desde"] ?? null,
      "inicio"
    );
    $fecha_hasta = $this->validarYFormatearFecha(
      $_GET["fecha_hasta"] ?? null,
      "fin"
    );

    if ($fecha_desde > $fecha_hasta) {
      die("Error: Rango de fechas inválido.");
    }

    $model = new DashboardModel();
    $ingresos = $model->getIngresosReporte($fecha_desde, $fecha_hasta);

    // El código para usar FPDF no cambia, porque el autoloader ya la hizo disponible.
    $pdf = new FPDF("P", "mm", "Letter");
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", 16);
    $pdf->Cell(0, 10, "Reporte de Ingresos Conciliados", 0, 1, "C");
    $pdf->SetFont("Arial", "", 12);
    $pdf->Cell(
      0,
      10,
      "Periodo: " . $fecha_desde . " al " . $fecha_hasta,
      0,
      1,
      "C"
    );
    $pdf->Ln(10);

    $pdf->SetFont("Arial", "B", 12);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(130, 10, "Categoria", 1, 0, "C", true);
    $pdf->Cell(60, 10, "Monto", 1, 1, "C", true);

    $pdf->SetFont("Arial", "", 12);
    $total = 0;
    foreach ($ingresos as $ingreso) {
      $pdf->Cell(130, 10, utf8_decode($ingreso["categoria"]), 1, 0);
      $pdf->Cell(
        60,
        10,
        number_format($ingreso["total"], 2, ",", ".") . " $",
        1,
        1,
        "R"
      );
      $total += $ingreso["total"];
    }

    $pdf->SetFont("Arial", "B", 12);
    $pdf->Cell(130, 10, "TOTAL INGRESOS", 1, 0, "R");
    $pdf->Cell(
      60,
      10,
      number_format($total, 2, ",", ".") . " $",
      1,
      1,
      "R"
    );

    $pdf->Output("D", "Reporte_Ingresos_" . date("Y-m-d") . ".pdf");
  }
}