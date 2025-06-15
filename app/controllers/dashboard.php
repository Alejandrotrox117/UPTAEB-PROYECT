<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/Models/DashboardModel.php";

class PDF extends FPDF
{
  // Cabecera de página
  function Header()
  {

    $this->SetFont("Arial", "B", 14);
    $this->Cell(0, 7, utf8_decode("Recuperadora La Pradera de Pavia, C.A."), 0, 1, "C");
    $this->SetFont("Arial", "", 10);
    $this->Cell(0, 5, "RIF: J-40352739-3", 0, 1, "C");
    $this->Cell(0, 5, utf8_decode("Ctra. Vieja a Carora, Km. 12, Pavia, Barquisimeto, Edo. Lara."), 0, 1, "C");
    $this->Cell(0, 5, "Telf: 0426-550.00.30", 0, 1, "C");
    $this->Ln(10);
  }

  function Footer()
  {
    $this->SetY(-15);
    $this->SetFont("Arial", "I", 8);
    $this->Cell(0, 10, utf8_decode("Página ") . $this->PageNo() . "/{nb}", 0, 0, "C");
  }
}

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
    $data["tipos_pago"] = $model->getTiposDePago();
    $data["tipos_egreso"] = ["Compras", "Sueldos", "Otros Egresos"];

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
    $idtipo_pago_ingresos = filter_var(
      $_GET["idtipo_pago_ingresos"] ?? null,
      FILTER_VALIDATE_INT
    ) ?: null;

    $fecha_desde_egresos = $this->validarYFormatearFecha(
      $_GET["fecha_desde_egresos"] ?? null,
      "inicio"
    );
    $fecha_hasta_egresos = $this->validarYFormatearFecha(
      $_GET["fecha_hasta_egresos"] ?? null,
      "fin"
    );
    $idtipo_pago_egresos = filter_var(
      $_GET["idtipo_pago_egresos"] ?? null,
      FILTER_VALIDATE_INT
    ) ?: null;
    $tipo_egreso = filter_var($_GET["tipo_egreso"] ?? null, FILTER_SANITIZE_STRING);
    $tipo_egreso = in_array($tipo_egreso, [
      "Compras",
      "Sueldos",
      "Otros Egresos",
    ])
      ? $tipo_egreso
      : null;

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
        $idtipo_pago_ingresos
      ),
      "reporteEgresos" => $model->getEgresosReporte(
        $fecha_desde_egresos,
        $fecha_hasta_egresos,
        $idtipo_pago_egresos,
        $tipo_egreso
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
    $idtipo_pago = filter_var(
      $_GET["idtipo_pago"] ?? null,
      FILTER_VALIDATE_INT
    ) ?: null;

    if ($fecha_desde > $fecha_hasta) {
      die("Error: Rango de fechas inválido.");
    }

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

    $pdf = new PDF("P", "mm", "Letter");
    $pdf->AliasNbPages();
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

    $pdf->Output("D", "Reporte_Ingresos_Detallado_" . date("Y-m-d") . ".pdf");
  }

  public function descargarEgresosPDF()
  {
    $fecha_desde = $this->validarYFormatearFecha(
      $_GET["fecha_desde"] ?? null,
      "inicio"
    );
    $fecha_hasta = $this->validarYFormatearFecha(
      $_GET["fecha_hasta"] ?? null,
      "fin"
    );
    $idtipo_pago = filter_var(
      $_GET["idtipo_pago"] ?? null,
      FILTER_VALIDATE_INT
    ) ?: null;
    $tipo_egreso = filter_var($_GET["tipo_egreso"] ?? null, FILTER_SANITIZE_STRING);
    $tipo_egreso = in_array($tipo_egreso, [
      "Compras",
      "Sueldos",
      "Otros Egresos",
    ])
      ? $tipo_egreso
      : null;

    if ($fecha_desde > $fecha_hasta) {
      die("Error: Rango de fechas inválido.");
    }

    $model = new DashboardModel();
    $egresosDetallados = $model->getEgresosDetallados(
      $fecha_desde,
      $fecha_hasta,
      $idtipo_pago,
      $tipo_egreso
    );
    $egresosResumen = $model->getEgresosReporte(
      $fecha_desde,
      $fecha_hasta,
      $idtipo_pago,
      $tipo_egreso
    );

    $pdf = new PDF("P", "mm", "Letter");
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", 16);
    $pdf->Cell(0, 10, "Reporte Detallado de Egresos", 0, 1, "C");
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
        $pdf->Cell(75, 7, utf8_decode($pago["descripcion"]), 1, 0);
        $pdf->Cell(30, 7, utf8_decode($pago["tipo_pago"]), 1, 0);
        $pdf->Cell(30, 7, $pago["referencia"], 1, 0);
        $pdf->Cell(
          40,
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
    $pdf->Cell(155, 8, "TOTAL GENERAL", 1, 0, "R");
    $pdf->Cell(
      40,
      8,
      number_format($totalGeneral, 2, ",", ".") . " Bs.",
      1,
      1,
      "R"
    );

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

    $pdf->Output("D", "Reporte_Egresos_Detallado_" . date("Y-m-d") . ".pdf");
  }
}