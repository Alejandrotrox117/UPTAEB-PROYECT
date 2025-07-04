<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class DashboardModel extends mysql
{
private $query;
  private $array;
  private $result;

  public function __construct()
  {
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

   
    public function getResumen(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
    
            $stmt = $db->prepare("SELECT 
                (SELECT COALESCE(SUM(total_general), 0) FROM venta WHERE fecha_venta = CURDATE() AND estatus = 'activo') as ventas_hoy,
                (SELECT COALESCE(SUM(total_general), 0) FROM venta WHERE fecha_venta = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND estatus = 'activo') as ventas_ayer,
                (SELECT COALESCE(SUM(total_general), 0) FROM compra WHERE fecha = CURDATE() AND estatus_compra = 'Pendiente') as compras_hoy,
                (SELECT COALESCE(SUM(total_general), 0) FROM compra WHERE fecha = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND estatus_compra = 'Pendiente') as compras_ayer,
                (SELECT COALESCE(SUM(existencia * precio), 0) FROM producto WHERE estatus = 'activo') as valor_inventario,
                (SELECT COUNT(*) FROM produccion WHERE estado IN ('borrador', 'en_clasificacion', 'empacando')) as producciones_activas,
                (SELECT COUNT(*) FROM producto WHERE estatus = 'activo' AND existencia > 0) as productos_en_rotacion,
                (SELECT COALESCE(AVG(CASE WHEN p.estado = 'realizado' THEN 100 ELSE 50 END), 0) FROM produccion p WHERE p.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as eficiencia_promedio");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getResumen: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getAnalisisInventario()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $response = [];

           
            $stmt_critico = $db->prepare("SELECT 
                (SELECT COUNT(*) FROM producto WHERE estatus = 'activo' AND existencia <= stock_minimo) as critico,
                (SELECT COUNT(*) FROM producto WHERE estatus = 'activo' AND existencia > stock_minimo) as normal");
            $stmt_critico->execute();
            $stock_data = $stmt_critico->fetch(PDO::FETCH_ASSOC);
            $total_productos = $stock_data['critico'] + $stock_data['normal'];
            $response['stock_critico'] = ($total_productos > 0) ? ($stock_data['critico'] / $total_productos * 100) : 0;

           
            $stmt_valor = $db->prepare("SELECT c.nombre, SUM(p.existencia * p.precio) as valor
                FROM producto p
                JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.estatus = 'activo' AND p.existencia > 0
                GROUP BY c.idcategoria, c.nombre
                ORDER BY valor DESC");
            $stmt_valor->execute();
            $categorias = $stmt_valor->fetchAll(PDO::FETCH_ASSOC);
            // Estructurar datos para JavaScript con formato esperado
            $response['valor_por_categoria'] = json_encode(['categorias' => $categorias]);

          
            $stmt_mov = $db->prepare("SELECT 
                SUM(CASE WHEN tipo_movimiento = 'Entrada' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo_movimiento = 'Salida' THEN 1 ELSE 0 END) as salidas
                FROM movimientos_existencia 
                WHERE estatus = 'activo' AND MONTH(fecha_movimiento) = MONTH(CURDATE())");
            $stmt_mov->execute();
            $response['movimientos_mes'] = $stmt_mov->fetch(PDO::FETCH_ASSOC);

      
            $stmt_vendidos = $db->prepare("SELECT p.nombre, SUM(dv.cantidad) as cantidad
                FROM detalle_venta dv
                JOIN producto p ON dv.idproducto = p.idproducto
                JOIN venta v ON dv.idventa = v.idventa
                WHERE v.estatus = 'activo' AND MONTH(v.fecha_venta) = MONTH(CURDATE())
                GROUP BY p.idproducto, p.nombre
                ORDER BY cantidad DESC
                LIMIT 5");
            $stmt_vendidos->execute();
            $productos = $stmt_vendidos->fetchAll(PDO::FETCH_ASSOC);
            // Estructurar datos para JavaScript con formato esperado
            $response['productos_mas_vendidos'] = json_encode(['productos' => $productos]);

            return $response;

        } catch (Exception $e) {
            error_log("Error en getAnalisisInventario: " . $e->getMessage());
            return [
                'stock_critico' => 0, 
                'valor_por_categoria' => json_encode(['categorias' => []]), 
                'movimientos_mes' => ['entradas' => 0, 'salidas' => 0],
                'productos_mas_vendidos' => json_encode(['productos' => []])
            ];
        } finally {
            $conexion->disconnect();
        }
    }
    
 
    public function getUltimasVentas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                v.idventa,
                v.nro_venta,
                v.fecha_venta,
                CONCAT(c.nombre, ' ', c.apellido) as cliente,
                v.total_general,
                v.estatus as estado
                FROM venta v
                JOIN cliente c ON v.idcliente = c.idcliente
                WHERE v.estatus = 'activo'
                ORDER BY v.fecha_venta DESC, v.idventa DESC
                LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getUltimasVentas: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getVentasMensuales()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
                SUM(total_general) as total,
                COUNT(*) as cantidad
                FROM venta 
                WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                AND estatus = 'activo'
                GROUP BY mes 
                ORDER BY mes ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getVentasMensuales: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }


    public function getProveedoresActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT idproveedor, CONCAT(nombre, ' ', apellido) as nombre_completo FROM proveedor WHERE estatus = 'activo' ORDER BY nombre_completo ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getProveedoresActivos: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getProductos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT idproducto, nombre FROM producto WHERE estatus = 'activo' ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getProductos: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getEmpleadosActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT idempleado, CONCAT(nombre, ' ', apellido) as nombre_completo FROM empleado WHERE estatus = 'Activo' ORDER BY nombre_completo ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getEmpleadosActivos: " . $e->getMessage());
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


    public function getReporteCompras($fecha_desde, $fecha_hasta, $idproveedor = null, $idproducto = null)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $sql = "SELECT 
                c.fecha,
                c.nro_compra,
                CONCAT(pr.nombre, ' ', pr.apellido) as proveedor,
                p.nombre as producto,
                dc.cantidad,
                dc.precio_unitario_compra,
                dc.subtotal_linea
                FROM compra c
                JOIN proveedor pr ON c.idproveedor = pr.idproveedor
                JOIN detalle_compra dc ON c.idcompra = dc.idcompra
                JOIN producto p ON dc.idproducto = p.idproducto
                WHERE c.fecha BETWEEN ? AND ?
                AND c.estatus_compra = 'Pendiente'";
            
            $params = [$fecha_desde, $fecha_hasta];
            
            if ($idproveedor) {
                $sql .= " AND c.idproveedor = ?";
                $params[] = $idproveedor;
            }
            
            if ($idproducto) {
                $sql .= " AND dc.idproducto = ?";
                $params[] = $idproducto;
            }
            
            $sql .= " ORDER BY c.fecha DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getReporteCompras: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

   
    public function getKPIsEjecutivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {

            $stmt = $db->prepare("SELECT 
                CAST(COALESCE(
                    (SELECT (SUM(v.total_general) - (SELECT COALESCE(SUM(c.total_general), 0) FROM compra c WHERE MONTH(c.fecha) = MONTH(CURDATE()))) / NULLIF(SUM(v.total_general), 0) * 100
                    FROM venta v WHERE MONTH(v.fecha_venta) = MONTH(CURDATE()) AND v.estatus = 'activo'), 0
                ) AS DECIMAL(10,2)) as margen_ganancia,
                
                CAST(COALESCE(
                    (SELECT ((SUM(total_general) - (SELECT COALESCE(SUM(total_general), 0) FROM compra WHERE MONTH(fecha) = MONTH(CURDATE()))) / 
                    NULLIF((SELECT SUM(total_general) FROM compra WHERE MONTH(fecha) = MONTH(CURDATE())), 0)) * 100
                    FROM venta WHERE MONTH(fecha_venta) = MONTH(CURDATE()) AND estatus = 'activo'), 0
                ) AS DECIMAL(10,2)) as roi_mes,
                
                CAST(COALESCE(
                    (SELECT AVG(existencia) * 30 / NULLIF(COUNT(*), 0) FROM producto WHERE estatus = 'activo'), 0
                ) AS DECIMAL(10,2)) as rotacion_inventario,
                
                CAST(COALESCE(
                    (SELECT SUM(cantidad_a_realizar) / NULLIF(COUNT(*), 0)
                    FROM produccion 
                    WHERE estado = 'realizado' AND MONTH(fecha_inicio) = MONTH(CURDATE())), 0
                ) AS DECIMAL(10,2)) as productividad_general");
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getKPIsEjecutivos: " . $e->getMessage());
            return ['margen_ganancia' => 0, 'roi_mes' => 0, 'rotacion_inventario' => 0, 'productividad_general' => 0];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getTendenciasVentas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                DATE_FORMAT(fecha_venta, '%Y-%m') as periodo,
                SUM(total_general) as total_ventas,
                COUNT(*) as num_ventas,
                AVG(total_general) as ticket_promedio
                FROM venta 
                WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND estatus = 'activo'
                GROUP BY periodo 
                ORDER BY periodo ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getTendenciasVentas: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getRentabilidadProductos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                p.nombre,
                COALESCE(SUM(dv.cantidad * dv.precio_unitario_venta), 0) as ingresos,
                COALESCE(SUM(dc.cantidad * dc.precio_unitario_compra), 0) as costos,
                COALESCE(SUM(dv.cantidad * dv.precio_unitario_venta) - SUM(dc.cantidad * dc.precio_unitario_compra), 0) as ganancia_neta
                FROM producto p
                LEFT JOIN detalle_venta dv ON p.idproducto = dv.idproducto
                LEFT JOIN detalle_compra dc ON p.idproducto = dc.idproducto
                WHERE p.estatus = 'activo'
                GROUP BY p.idproducto, p.nombre
                ORDER BY ganancia_neta DESC
                LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getRentabilidadProductos: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getEficienciaEmpleados($fecha_desde, $fecha_hasta, $idempleado, $estado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $sql = "SELECT 
                CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                COUNT(p.idproduccion) as ordenes_asignadas,
                SUM(CASE WHEN p.estado = 'realizado' THEN 1 ELSE 0 END) as ordenes_completadas,
                COALESCE(SUM(p.cantidad_a_realizar), 0) as cantidad_total_asignada,
                COALESCE(SUM(tp.cantidad_realizada), 0) as cantidad_realizada
                FROM empleado e
                LEFT JOIN produccion p ON e.idempleado = p.idempleado
                LEFT JOIN tarea_produccion tp ON e.idempleado = tp.idempleado
                WHERE e.estatus = 'Activo'";
            
            $params = [];
            
            if ($fecha_desde && $fecha_hasta) {
                $sql .= " AND p.fecha_inicio BETWEEN ? AND ?";
                $params[] = $fecha_desde;
                $params[] = $fecha_hasta;
            }
            
            if (!empty($idempleado)) {
                $sql .= " AND e.idempleado = ?";
                $params[] = $idempleado;
            }
            if (!empty($estado)) {
                $sql .= " AND p.estado = ?";
                $params[] = $estado;
            }
            
            $sql .= " GROUP BY e.idempleado, empleado_nombre ORDER BY ordenes_completadas DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getEficienciaEmpleados: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getEstadosProduccion($fecha_desde, $fecha_hasta)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $sql = "SELECT 
                estado,
                COUNT(*) as cantidad,
                SUM(cantidad_a_realizar) as total_kg
                FROM produccion";
            
            if ($fecha_desde && $fecha_hasta) {
                $sql .= " WHERE fecha_inicio BETWEEN ? AND ?";
                $params = [$fecha_desde, $fecha_hasta];
            } else {
                $params = [];
            }
            
            $sql .= " GROUP BY estado ORDER BY cantidad DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getEstadosProduccion: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getCumplimientoTareas($fecha_desde, $fecha_hasta)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $sql = "SELECT 
                COUNT(*) as total_tareas,
                SUM(CASE WHEN tp.estado = 'completado' THEN 1 ELSE 0 END) as tareas_completadas,
                SUM(CASE WHEN tp.estado = 'en_progreso' THEN 1 ELSE 0 END) as tareas_en_progreso,
                SUM(CASE WHEN tp.estado = 'pendiente' THEN 1 ELSE 0 END) as tareas_pendientes
                FROM tarea_produccion tp
                JOIN produccion p ON tp.idproduccion = p.idproduccion";
            
            if ($fecha_desde && $fecha_hasta) {
                $sql .= " WHERE p.fecha_inicio BETWEEN ? AND ?";
                $params = [$fecha_desde, $fecha_hasta];
            } else {
                $params = [];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getCumplimientoTareas: " . $e->getMessage());
            return ['total_tareas' => 0, 'tareas_completadas' => 0, 'tareas_en_progreso' => 0, 'tareas_pendientes' => 0];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getTopClientes($limit = 10)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                c.idcliente,
                CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                COUNT(v.idventa) as num_compras,
                SUM(v.total_general) as total_comprado,
                AVG(v.total_general) as ticket_promedio
                FROM cliente c
                JOIN venta v ON c.idcliente = v.idcliente
                WHERE c.estatus = 'activo' AND v.estatus = 'activo'
                GROUP BY c.idcliente, cliente_nombre
                HAVING total_comprado > 0
                ORDER BY total_comprado DESC
                LIMIT :limit");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getTopClientes: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getTopProveedores($limit = 10)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                pr.idproveedor,
                CONCAT(pr.nombre, ' ', pr.apellido) as proveedor_nombre,
                COUNT(c.idcompra) as num_compras,
                SUM(c.total_general) as total_comprado
                FROM proveedor pr
                JOIN compra c ON pr.idproveedor = c.idproveedor
                WHERE pr.estatus = 'activo'
                GROUP BY pr.idproveedor, proveedor_nombre
                HAVING total_comprado > 0
                ORDER BY total_comprado DESC
                LIMIT :limit");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getTopProveedores: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function getKPIsTiempoReal()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $stmt = $db->prepare("SELECT 
                'Ventas Totales' as metrica,
                COALESCE(SUM(CASE WHEN fecha_venta = CURDATE() THEN total_general ELSE 0 END), 0) as hoy,
                COALESCE(SUM(CASE WHEN fecha_venta = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN total_general ELSE 0 END), 0) as ayer,
                COALESCE(SUM(CASE WHEN YEARWEEK(fecha_venta, 1) = YEARWEEK(CURDATE(), 1) THEN total_general ELSE 0 END), 0) as esta_semana,
                COALESCE(SUM(CASE WHEN MONTH(fecha_venta) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN total_general ELSE 0 END), 0) as mes_pasado
                FROM venta WHERE estatus = 'activo'
                
                UNION ALL
                
                SELECT 
                'Órdenes de Compra' as metrica,
                COUNT(CASE WHEN fecha = CURDATE() THEN 1 END) as hoy,
                COUNT(CASE WHEN fecha = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as ayer,
                COUNT(CASE WHEN YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1) THEN 1 END) as esta_semana,
                COUNT(CASE WHEN MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1 END) as mes_pasado
                FROM compra WHERE estatus_compra = 'Pendiente'
                
                UNION ALL
                
                SELECT 
                'Producciones Completadas' as metrica,
                COUNT(CASE WHEN fecha_fin = CURDATE() AND estado = 'realizado' THEN 1 END) as hoy,
                COUNT(CASE WHEN fecha_fin = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND estado = 'realizado' THEN 1 END) as ayer,
                COUNT(CASE WHEN YEARWEEK(fecha_fin, 1) = YEARWEEK(CURDATE(), 1) AND estado = 'realizado' THEN 1 END) as esta_semana,
                COUNT(CASE WHEN MONTH(fecha_fin) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND estado = 'realizado' THEN 1 END) as mes_pasado
                FROM produccion");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getKPIsTiempoReal: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

      // --- Métodos Públicos---


  public function getTareasPendientes()
  {
    return []; 
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
}