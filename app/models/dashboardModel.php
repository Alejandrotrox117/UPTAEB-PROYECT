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
        return [
            "ventas_totales" => $this->db->query("SELECT COUNT(*) FROM venta WHERE fecha_venta = CURDATE()")->fetchColumn(),
            "compras_totales" => $this->db->query("SELECT COUNT(*) FROM compra WHERE fecha = CURDATE()")->fetchColumn(),
            "total_inventario" => $this->db->query("SELECT SUM(existencia) FROM producto")->fetchColumn(),
            "empleados_activos" => $this->db->query("SELECT COUNT(*) FROM empleado WHERE estatus = 'Activo'")->fetchColumn()
        ];
    }

    public function getUltimasVentas()
    {
        $stmt = $this->db->query("
            SELECT nro_venta, cliente.nombre AS cliente, fecha_venta, total_general 
            FROM venta 
            JOIN cliente ON venta.idcliente = cliente.idcliente
            ORDER BY fecha_venta DESC LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTareasPendientes()
    {
        $stmt = $this->db->query("
            SELECT idtarea, cantidad_asignada, estado, empleado.nombre AS nombre_empleado
            FROM tarea_produccion
            JOIN empleado ON tarea_produccion.idempleado = empleado.idempleado
            WHERE estado = 'pendiente'
            ORDER BY idtarea DESC LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVentasMensuales()
    {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes, COUNT(*) AS ventas_totales
            FROM venta GROUP BY mes ORDER BY mes DESC LIMIT 12
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
}