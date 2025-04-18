<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
class InventarioModel extends Mysql
{

    private $db;
    private $conexion;
    private $idmovimiento;
    private $nombre_material;
    private $tipo_movimiento;
    private $descuento;
    private $inventario;
    private $nr_documento;
    private $desde;
    private $hasta;
    private $estado;
    private $fecha_movimiento;

    // Getters
    public function getIdMovimiento()
    {
        return $this->idmovimiento;
    }
    public function getNombreMaterial()
    {
        return $this->nombre_material;
    }
    public function getTipoMovimiento()
    {
        return $this->tipo_movimiento;
    }
    public function getDescuento()
    {
        return $this->descuento;
    }
    public function getInventario()
    {
        return $this->inventario;
    }
    public function getNrDocumento()
    {
        return $this->nr_documento;
    }
    public function getDesde()
    {
        return $this->desde;
    }
    public function getHasta()
    {
        return $this->hasta;
    }
    public function getEstado()
    {
        return $this->estado;
    }
    public function getFechaMovimiento()
    {
        return $this->fecha_movimiento;
    }

    // Setters
    public function setIdMovimiento($idmovimiento)
    {
        $this->idmovimiento = $idmovimiento;
    }
    public function setNombreMaterial($nombre_material)
    {
        $this->nombre_material = $nombre_material;
    }
    public function setTipoMovimiento($tipo_movimiento)
    {
        $this->tipo_movimiento = $tipo_movimiento;
    }
    public function setDescuento($descuento)
    {
        $this->descuento = $descuento;
    }
    public function setInventario($inventario)
    {
        $this->inventario = $inventario;
    }
    public function setNrDocumento($nr_documento)
    {
        $this->nr_documento = $nr_documento;
    }
    public function setDesde($desde)
    {
        $this->desde = $desde;
    }
    public function setHasta($hasta)
    {
        $this->hasta = $hasta;
    }
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }
    public function setFechaMovimiento($fecha_movimiento)
    {
        $this->fecha_movimiento = $fecha_movimiento;
    }

    // Constructor
    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connectGeneral();
        parent::__construct();
    }

    // Método para obtener todos los datos del inventario
    public function selectAllInventario()
    {
        $sql = "SELECT 
                    me.idmovimiento, 
                    tm.nombre AS nombre_material, 
                    me.tipo_movimiento, 
                    me.descuento, 
                    me.inventario, 
                    me.nr_documento, 
                    me.desde, 
                    me.hasta, 
                    me.estado, 
                    me.fecha AS fecha_movimiento
                FROM movimientos_existencia me
                LEFT JOIN tipo_materiales tm ON me.idmaterial = tm.idmaterial
                ORDER BY me.idmovimiento ASC
                LIMIT 10 OFFSET 0;";
    
        $request = $this->searchAll($sql);
        return $request;
    }
}
