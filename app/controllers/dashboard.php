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

  /**
   * Valida una cadena de fecha.
   * Si es válida, la devuelve. Si no, devuelve un valor por defecto seguro.
   * @param string|null $dateString La fecha a validar.
   * @param string $type 'inicio' para el primer día del mes, 'fin' para el último.
   * @return string La fecha validada en formato Y-m-d.
   */
  private function validarYFormatearFecha($dateString, $type = "inicio")
  {
    if ($dateString) {
      $d = DateTime::createFromFormat("Y-m-d", $dateString);
      // Comprueba si la fecha es válida y si el formato coincide exactamente
      if ($d && $d->format("Y-m-d") === $dateString) {
        return $dateString;
      }
    }
    // Si la validación falla, devuelve un valor por defecto seguro
    return $type === "inicio" ? date("Y-m-01") : date("Y-m-t");
  }

  public function getDashboardData()
  {
    $model = new DashboardModel();

    // 1. Validar el formato de cada fecha individualmente
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

    // 2. Validar el rango y rechazar la petición si es inválido
    if (
      $fecha_desde_ingresos > $fecha_hasta_ingresos ||
      $fecha_desde_egresos > $fecha_hasta_egresos
    ) {
      http_response_code(400); // Bad Request
      echo json_encode(["error" => "El rango de fechas proporcionado es inválido."]);
      exit();
    }

    // 3. Pasar las fechas seguras al modelo y generar la respuesta
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
}