<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class DashboardModel extends mysql
{
    private $conexion;
    private $db;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad(); // BD seguridad
    }

    public function getResumen()
    {
        try {
            $comprasHoy = $this->getComprasHoy();
            $ventasHoy = $this->getVentasHoy();
            $inventarioTotal = $this->getInventarioTotal();
            $empleadosActivos = $this->getEmpleadosActivos();
            $tareasActivas = $this->getTareasActivas();

            return [
                "compras_hoy" => $comprasHoy,
                "ventas_hoy" => $ventasHoy,
                "inventario_total" => $inventarioTotal,
                "empleados_activos" => $empleadosActivos,
                "tareas_activas" => $tareasActivas
            ];
        } catch (PDOException $e) {
            error_log("Error en getResumen: " . $e->getMessage());
            return [
                "compras_hoy" => 0,
                "ventas_hoy" => 0,
                "inventario_total" => 0,
                "empleados_activos" => 0,
                "tareas_activas" => 0
            ];
        }
    }

    private function getComprasHoy()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM compra 
                WHERE DATE(fecha) = CURDATE()
                AND estatus_compra NOT IN ('inactivo', 'BORRADOR')
            ");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            error_log("Error en getComprasHoy: " . $e->getMessage());
            return 0;
        }
    }

    private function getVentasHoy()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM venta 
                WHERE DATE(fecha_venta) = CURDATE()
                AND estatus = 'activo'
            ");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            error_log("Error en getVentasHoy: " . $e->getMessage());
            return 0;
        }
    }

    private function getInventarioTotal()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(existencia), 0) 
                FROM producto 
                WHERE estatus = 'activo'
            ");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            error_log("Error en getInventarioTotal: " . $e->getMessage());
            return 0;
        }
    }

    private function getEmpleadosActivos()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM empleado 
                WHERE estatus = 'Activo'
            ");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            error_log("Error en getEmpleadosActivos: " . $e->getMessage());
            return 0;
        }
    }

    private function getTareasActivas()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM tarea_produccion 
                WHERE estado IN ('pendiente', 'en_progreso')
            ");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            error_log("Error en getTareasActivas: " . $e->getMessage());
            return 0;
        }
    }

    public function getUltimasCompras()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.nro_compra,
                    CONCAT(p.nombre, ' ', p.apellido) AS proveedor,
                    DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha,
                    CONCAT(m.codigo_moneda, ' ', FORMAT(c.total_general, 2)) AS total,
                    c.estatus_compra
                FROM compra c
                LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor
                LEFT JOIN monedas m ON c.idmoneda_general = m.idmoneda
                WHERE c.estatus_compra NOT IN ('inactivo', 'BORRADOR')
                ORDER BY c.fecha DESC, c.idcompra DESC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getUltimasCompras: " . $e->getMessage());
            return [];
        }
    }

    public function getUltimasVentas()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    v.nro_venta,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente,
                    DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') AS fecha,
                    CONCAT(m.codigo_moneda, ' ', FORMAT(v.total_general, 2)) AS total,
                    v.estatus
                FROM venta v
                LEFT JOIN cliente c ON v.idcliente = c.idcliente
                LEFT JOIN monedas m ON v.idmoneda = m.idmoneda
                WHERE v.estatus = 'activo'
                ORDER BY v.fecha_venta DESC, v.idventa DESC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getUltimasVentas: " . $e->getMessage());
            return [];
        }
    }

    public function getTareasPendientes()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tp.idtarea,
                    CONCAT(e.nombre, ' ', COALESCE(e.apellido, '')) AS empleado,
                    tp.cantidad_asignada,
                    tp.cantidad_realizada,
                    tp.estado,
                    p.nombre as producto,
                    DATE_FORMAT(tp.fecha_inicio, '%d/%m/%Y') AS fecha_inicio
                FROM tarea_produccion tp
                LEFT JOIN empleado e ON tp.idempleado = e.idempleado
                LEFT JOIN produccion pr ON tp.idproduccion = pr.idproduccion
                LEFT JOIN producto p ON pr.idproducto = p.idproducto
                WHERE tp.estado IN ('pendiente', 'en_progreso')
                ORDER BY tp.fecha_inicio DESC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getTareasPendientes: " . $e->getMessage());
            return [];
        }
    }

    public function getVentasMensuales()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(fecha_venta, '%Y-%m') AS mes,
                    COUNT(*) AS cantidad_ventas,
                    COALESCE(SUM(total_general), 0) AS monto_total,
                    CONCAT(
                        CASE MONTH(fecha_venta)
                            WHEN 1 THEN 'Ene'
                            WHEN 2 THEN 'Feb'
                            WHEN 3 THEN 'Mar'
                            WHEN 4 THEN 'Abr'
                            WHEN 5 THEN 'May'
                            WHEN 6 THEN 'Jun'
                            WHEN 7 THEN 'Jul'
                            WHEN 8 THEN 'Ago'
                            WHEN 9 THEN 'Sep'
                            WHEN 10 THEN 'Oct'
                            WHEN 11 THEN 'Nov'
                            WHEN 12 THEN 'Dic'
                        END,
                        ' ',
                        YEAR(fecha_venta)
                    ) AS mes_texto
                FROM venta 
                WHERE estatus = 'activo'
                  AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
                ORDER BY mes ASC
                LIMIT 12
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getVentasMensuales: " . $e->getMessage());
            return [];
        }
    }

    public function getComprasMensuales()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(fecha, '%Y-%m') AS mes,
                    COUNT(*) AS cantidad_compras,
                    COALESCE(SUM(total_general), 0) AS monto_total,
                    CONCAT(
                        CASE MONTH(fecha)
                            WHEN 1 THEN 'Ene'
                            WHEN 2 THEN 'Feb'
                            WHEN 3 THEN 'Mar'
                            WHEN 4 THEN 'Abr'
                            WHEN 5 THEN 'May'
                            WHEN 6 THEN 'Jun'
                            WHEN 7 THEN 'Jul'
                            WHEN 8 THEN 'Ago'
                            WHEN 9 THEN 'Sep'
                            WHEN 10 THEN 'Oct'
                            WHEN 11 THEN 'Nov'
                            WHEN 12 THEN 'Dic'
                        END,
                        ' ',
                        YEAR(fecha)
                    ) AS mes_texto
                FROM compra 
                WHERE estatus_compra NOT IN ('inactivo', 'BORRADOR')
                  AND fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ORDER BY mes ASC
                LIMIT 12
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getComprasMensuales: " . $e->getMessage());
            return [];
        }
    }

    public function getProductosBajoStock()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    nombre,
                    existencia,
                    CASE 
                        WHEN existencia = 0 THEN 'Sin stock'
                        WHEN existencia <= 5 THEN 'Stock crítico'
                        WHEN existencia <= 10 THEN 'Stock bajo'
                        ELSE 'Stock normal'
                    END as estado_stock
                FROM producto 
                WHERE estatus = 'activo' 
                AND existencia <= 10
                ORDER BY existencia ASC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getProductosBajoStock: " . $e->getMessage());
            return [];
        }
    }

    public function getAlertas()
    {
        try {
            $alertas = [];
            
            // Productos sin stock
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as productos_sin_stock
                FROM producto 
                WHERE existencia = 0 
                AND estatus = 'activo'
            ");
            $stmt->execute();
            $sinStock = $stmt->fetchColumn();
            
            if ($sinStock > 0) {
                $alertas[] = [
                    'tipo' => 'danger',
                    'titulo' => 'Productos sin Stock',
                    'mensaje' => "$sinStock productos no tienen existencias",
                    'icono' => 'fas fa-exclamation-triangle'
                ];
            }
            
            // Productos con stock bajo
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as productos_stock_bajo
                FROM producto 
                WHERE existencia > 0 AND existencia <= 5
                AND estatus = 'activo'
            ");
            $stmt->execute();
            $stockBajo = $stmt->fetchColumn();
            
            if ($stockBajo > 0) {
                $alertas[] = [
                    'tipo' => 'warning',
                    'titulo' => 'Stock Crítico',
                    'mensaje' => "$stockBajo productos tienen stock crítico",
                    'icono' => 'fas fa-exclamation-circle'
                ];
            }
            
            // Tareas pendientes
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as tareas_pendientes
                FROM tarea_produccion 
                WHERE estado = 'pendiente'
            ");
            $stmt->execute();
            $tareasPendientes = $stmt->fetchColumn();
            
            if ($tareasPendientes > 0) {
                $alertas[] = [
                    'tipo' => 'info',
                    'titulo' => 'Tareas Pendientes',
                    'mensaje' => "$tareasPendientes tareas esperan ser procesadas",
                    'icono' => 'fas fa-tasks'
                ];
            }
            
            return $alertas;
        } catch (PDOException $e) {
            error_log("Error en getAlertas: " . $e->getMessage());
            return [];
        }
    }
}
?>