<?php

require_once "app/core/conexion.php";

class DashboardModel
{
    private $db;
    private $dbSeguridad;

    public function __construct()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $this->db = $conexion->get_conectGeneral();
        $this->dbSeguridad = $conexion->get_conectSeguridad();
    }
  public function getResumen()
  {
    $sqlVentas =
      "SELECT COUNT(*) as ventas_totales FROM venta WHERE DATE(fecha_venta) = CURDATE()";
    $ventas = $this->db->query($sqlVentas)->fetch(PDO::FETCH_ASSOC);

    $sqlCompras =
      "SELECT COUNT(*) as compras_totales FROM compra WHERE fecha = CURDATE()";
    $compras = $this->db->query($sqlCompras)->fetch(PDO::FETCH_ASSOC);

    $sqlInventario = "SELECT SUM(existencia) as total_inventario FROM producto";
    $inventario = $this->db->query($sqlInventario)->fetch(PDO::FETCH_ASSOC);

    $sqlEmpleados =
      "SELECT COUNT(*) as empleados_activos FROM empleados WHERE estatus = 'activo'";
    $empleados = $this->db->query($sqlEmpleados)->fetch(PDO::FETCH_ASSOC);

    return [
      "ventas_totales" => $ventas["ventas_totales"] ?? 0,
      "compras_totales" => $compras["compras_totales"] ?? 0,
      "total_inventario" => $inventario["total_inventario"] ?? 0,
      "empleados_activos" => $empleados["empleados_activos"] ?? 0,
    ];
  }

  public function getUltimasVentas()
  {
    $sql = "SELECT v.nro_venta, CONCAT(c.nombre, ' ', c.apellido) as cliente, v.fecha_venta, v.total_general 
                FROM venta v 
                JOIN cliente c ON v.idcliente = c.idcliente 
                ORDER BY v.fecha_venta DESC LIMIT 5";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getTareasPendientes()
  {
    return [];
  }

  public function getVentasMensuales()
  {
    $sql = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes, SUM(total_general) as ventas_totales 
                FROM venta 
                WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mes 
                ORDER BY mes ASC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getIngresosReporte($fecha_desde, $fecha_hasta)
  {
    $sql = "SELECT 
                    tp.nombre AS categoria, 
                    SUM(p.monto) AS total
                FROM pagos p
                JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                WHERE 
                    p.idventa IS NOT NULL
                    AND p.estatus = 'conciliado'
                    AND p.fecha_pago BETWEEN :fecha_desde AND :fecha_hasta
                GROUP BY tp.nombre
                HAVING SUM(p.monto) > 0";

    try {
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(":fecha_desde", $fecha_desde);
      $stmt->bindParam(":fecha_hasta", $fecha_hasta);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("DashboardModel::getIngresosReporte Error: " . $e->getMessage());
      return [];
    }
  }

  public function getEgresosReporte($fecha_desde, $fecha_hasta)
  {
    $sql = "SELECT 
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN 'Compras'
                        WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldos'
                        ELSE 'Otros Egresos'
                    END AS categoria,
                    SUM(p.monto) AS total
                FROM pagos p
                WHERE 
                    p.idventa IS NULL
                    AND p.estatus = 'conciliado'
                    AND p.fecha_pago BETWEEN :fecha_desde AND :fecha_hasta
                GROUP BY categoria
                HAVING SUM(p.monto) > 0";

    try {
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(":fecha_desde", $fecha_desde);
      $stmt->bindParam(":fecha_hasta", $fecha_hasta);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("DashboardModel::getEgresosReporte Error: " . $e->getMessage());
      return [];
    }
  }
}