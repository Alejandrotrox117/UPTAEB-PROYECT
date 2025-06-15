<?php

require_once "app/core/conexion.php";

class DashboardModel
{
  // Propiedades privadas para encapsular el estado del modelo
  private $query;
  private $array;
  private $result;

  public function __construct()
  {
    // El constructor está vacío, la conexión se maneja en cada método privado
  }

  // --- Getters y Setters ---
  public function getQuery()
  {
    return $this->query;
  }

  public function setQuery(string $query)
  {
    $this->query = $query;
  }

  public function getArray()
  {
    return $this->array ?? [];
  }

  public function setArray(array $array)
  {
    $this->array = $array;
  }

  public function getResult()
  {
    return $this->result;
  }

  public function setResult($result)
  {
    $this->result = $result;
  }

  // --- Métodos Privados de Ejecución ---

  private function ejecutarGetResumen()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();

    try {
      $sqlVentas = "SELECT COUNT(*) as ventas_totales FROM venta WHERE DATE(fecha_venta) = CURDATE()";
      $ventas = $db->query($sqlVentas)->fetch(PDO::FETCH_ASSOC);

      $sqlCompras = "SELECT COUNT(*) as compras_totales FROM compra WHERE fecha = CURDATE()";
      $compras = $db->query($sqlCompras)->fetch(PDO::FETCH_ASSOC);

      $sqlInventario = "SELECT SUM(existencia) as total_inventario FROM producto";
      $inventario = $db->query($sqlInventario)->fetch(PDO::FETCH_ASSOC);

      $sqlEmpleados = "SELECT COUNT(*) as empleados_activos FROM empleados WHERE estatus = 'activo'";
      $empleados = $db->query($sqlEmpleados)->fetch(PDO::FETCH_ASSOC);

      return [
        "ventas_totales" => $ventas["ventas_totales"] ?? 0,
        "compras_totales" => $compras["compras_totales"] ?? 0,
        "total_inventario" => $inventario["total_inventario"] ?? 0,
        "empleados_activos" => $empleados["empleados_activos"] ?? 0,
      ];
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetResumen - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetUltimasVentas()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $this->setQuery("SELECT v.nro_venta, CONCAT(c.nombre, ' ', c.apellido) as cliente, v.fecha_venta, v.total_general FROM venta v JOIN cliente c ON v.idcliente = c.idcliente ORDER BY v.fecha_venta DESC LIMIT 5");
      $stmt = $db->prepare($this->getQuery());
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetUltimasVentas - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetVentasMensuales()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $this->setQuery("SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes, SUM(total_general) as ventas_totales FROM venta WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes ASC");
      $stmt = $db->prepare($this->getQuery());
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetVentasMensuales - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetTiposDePago()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $this->setQuery("SELECT idtipo_pago, nombre FROM tipos_pagos WHERE estatus = 'activo' ORDER BY nombre ASC");
      $stmt = $db->prepare($this->getQuery());
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetTiposDePago - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetIngresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago)
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $baseSql = "SELECT tp.nombre AS categoria, SUM(p.monto) AS total FROM pagos p JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago WHERE p.idventa IS NOT NULL AND p.estatus = 'conciliado' AND p.fecha_pago BETWEEN ? AND ?";
      $this->setArray([$fecha_desde, $fecha_hasta]);

      if (!empty($idtipo_pago)) {
        $baseSql .= " AND p.idtipo_pago = ?";
        $this->setArray(array_merge($this->getArray(), [$idtipo_pago]));
      }
      $baseSql .= " GROUP BY tp.nombre HAVING SUM(p.monto) > 0";
      $this->setQuery($baseSql);

      $stmt = $db->prepare($this->getQuery());
      $stmt->execute($this->getArray());
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetIngresosReporte - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetIngresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago)
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $baseSql = "SELECT p.fecha_pago, v.nro_venta, CONCAT(c.nombre, ' ', c.apellido) AS cliente, tp.nombre AS tipo_pago, p.referencia, p.monto FROM pagos p JOIN venta v ON p.idventa = v.idventa JOIN cliente c ON v.idcliente = c.idcliente JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago WHERE p.idventa IS NOT NULL AND p.estatus = 'conciliado' AND p.fecha_pago BETWEEN ? AND ?";
      $this->setArray([$fecha_desde, $fecha_hasta]);

      if (!empty($idtipo_pago)) {
        $baseSql .= " AND p.idtipo_pago = ?";
        $this->setArray(array_merge($this->getArray(), [$idtipo_pago]));
      }
      $baseSql .= " ORDER BY p.fecha_pago ASC";
      $this->setQuery($baseSql);

      $stmt = $db->prepare($this->getQuery());
      $stmt->execute($this->getArray());
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetIngresosDetallados - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetEgresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso)
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $baseSql = "SELECT CASE WHEN p.idcompra IS NOT NULL THEN 'Compras' WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldos' ELSE 'Otros Egresos' END AS categoria, SUM(p.monto) AS total FROM pagos p WHERE p.idventa IS NULL AND p.estatus = 'conciliado' AND p.fecha_pago BETWEEN ? AND ?";
      $this->setArray([$fecha_desde, $fecha_hasta]);

      if (!empty($idtipo_pago)) {
        $baseSql .= " AND p.idtipo_pago = ?";
        $this->setArray(array_merge($this->getArray(), [$idtipo_pago]));
      }
      if (!empty($tipo_egreso)) {
        if ($tipo_egreso === "Compras") $baseSql .= " AND p.idcompra IS NOT NULL";
        elseif ($tipo_egreso === "Sueldos") $baseSql .= " AND p.idsueldotemp IS NOT NULL";
        elseif ($tipo_egreso === "Otros Egresos") $baseSql .= " AND p.idcompra IS NULL AND p.idsueldotemp IS NULL";
      }
      $baseSql .= " GROUP BY categoria HAVING SUM(p.monto) > 0";
      $this->setQuery($baseSql);

      $stmt = $db->prepare($this->getQuery());
      $stmt->execute($this->getArray());
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetEgresosReporte - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetEgresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso)
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $baseSql = "SELECT p.fecha_pago, CASE WHEN p.idcompra IS NOT NULL THEN CONCAT('Compra #', c.nro_compra) WHEN p.idsueldotemp IS NOT NULL THEN 'Pago de Sueldo' ELSE p.observaciones END AS descripcion, tp.nombre AS tipo_pago, p.referencia, p.monto FROM pagos p JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago LEFT JOIN compra c ON p.idcompra = c.idcompra WHERE p.idventa IS NULL AND p.estatus = 'conciliado' AND p.fecha_pago BETWEEN ? AND ?";
      $this->setArray([$fecha_desde, $fecha_hasta]);

      if (!empty($idtipo_pago)) {
        $baseSql .= " AND p.idtipo_pago = ?";
        $this->setArray(array_merge($this->getArray(), [$idtipo_pago]));
      }
      if (!empty($tipo_egreso)) {
        if ($tipo_egreso === "Compras") $baseSql .= " AND p.idcompra IS NOT NULL";
        elseif ($tipo_egreso === "Sueldos") $baseSql .= " AND p.idsueldotemp IS NOT NULL";
        elseif ($tipo_egreso === "Otros Egresos") $baseSql .= " AND p.idcompra IS NULL AND p.idsueldotemp IS NULL";
      }
      $baseSql .= " ORDER BY p.fecha_pago ASC";
      $this->setQuery($baseSql);

      $stmt = $db->prepare($this->getQuery());
      $stmt->execute($this->getArray());
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetEgresosDetallados - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetProveedoresActivos()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $this->setQuery("SELECT idproveedor, CONCAT(nombre, ' ', apellido) as nombre_completo FROM proveedor WHERE estatus = 'activo' ORDER BY nombre_completo ASC");
      $stmt = $db->prepare($this->getQuery());
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetProveedoresActivos - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetProductos()
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $this->setQuery("SELECT idproducto, nombre FROM producto WHERE estatus = 'activo' ORDER BY nombre ASC");
      $stmt = $db->prepare($this->getQuery());
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetProductos - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  private function ejecutarGetReporteCompras($fecha_desde, $fecha_hasta, $idproveedor, $idproducto)
  {
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
      $baseSql = "SELECT 
                        c.fecha, 
                        c.nro_compra, 
                        CONCAT(pr.nombre, ' ', pr.apellido) as proveedor, 
                        p.nombre as producto, 
                        dc.cantidad, 
                        dc.precio_unitario_compra, 
                        dc.subtotal_linea
                    FROM compra c
                    JOIN detalle_compra dc ON c.idcompra = dc.idcompra
                    JOIN proveedor pr ON c.idproveedor = pr.idproveedor
                    JOIN producto p ON dc.idproducto = p.idproducto
                    WHERE c.estatus_compra = 'PAGADA'
                    AND c.fecha BETWEEN ? AND ?";

      $this->setArray([$fecha_desde, $fecha_hasta]);

      if (!empty($idproveedor)) {
        $baseSql .= " AND c.idproveedor = ?";
        $this->setArray(array_merge($this->getArray(), [$idproveedor]));
      }
      if (!empty($idproducto)) {
        $baseSql .= " AND dc.idproducto = ?";
        $this->setArray(array_merge($this->getArray(), [$idproducto]));
      }

      $baseSql .= " ORDER BY c.fecha ASC, c.nro_compra ASC";
      $this->setQuery($baseSql);

      $stmt = $db->prepare($this->getQuery());
      $stmt->execute($this->getArray());
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("DashboardModel::ejecutarGetReporteCompras - Error: " . $e->getMessage());
      return [];
    } finally {
      $conexion->disconnect();
    }
  }

  // --- Métodos Públicos (API del Modelo) ---

  public function getResumen()
  {
    return $this->ejecutarGetResumen();
  }

  public function getUltimasVentas()
  {
    return $this->ejecutarGetUltimasVentas();
  }

  public function getTareasPendientes()
  {
    return [];
  }

  public function getVentasMensuales()
  {
    return $this->ejecutarGetVentasMensuales();
  }

  public function getTiposDePago()
  {
    return $this->ejecutarGetTiposDePago();
  }

  public function getIngresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago = null)
  {
    return $this->ejecutarGetIngresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago);
  }

  public function getIngresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago = null)
  {
    return $this->ejecutarGetIngresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago);
  }

  public function getEgresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago = null, $tipo_egreso = null)
  {
    return $this->ejecutarGetEgresosReporte($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso);
  }

  public function getEgresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago = null, $tipo_egreso = null)
  {
    return $this->ejecutarGetEgresosDetallados($fecha_desde, $fecha_hasta, $idtipo_pago, $tipo_egreso);
  }

  public function getProveedoresActivos()
  {
    return $this->ejecutarGetProveedoresActivos();
  }

  public function getProductos()
  {
    return $this->ejecutarGetProductos();
  }

  public function getReporteCompras($fecha_desde, $fecha_hasta, $idproveedor = null, $idproducto = null)
  {
    return $this->ejecutarGetReporteCompras($fecha_desde, $fecha_hasta, $idproveedor, $idproducto);
  }
}