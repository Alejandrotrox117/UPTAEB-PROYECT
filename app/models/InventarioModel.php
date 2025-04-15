<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
class InventarioModel extends Mysql
{

    private $db;
    private $conexion;
    private $fecha;
    private $id;
    private $inicial;
    private $final;
    private $material_compras;
    private $ajuste;
    private $descuento;

    //getters
    public function getId()
    {
        return $this->id;
    }
    public function getFecha()
    {
        return $this->fecha;
    }
    public function getInicial()
    {
        return $this->inicial;
    }
    public function getFinal()
    {
        return $this->final;
    }
    public function getMaterial_compras()
    {
        return $this->material_compras;
    }
    public function getAjuste()
    {
        return $this->ajuste;
    }
    public function getDescuento()
    {
        return $this->descuento;
    }

    //setters
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    public function setInicial($inicial)
    {
        $this->inicial = $inicial;
    }
    public function setFinal($final)
    {
        $this->final = $final;
    }
    public function setMaterial_compras($material_compras)
    {
        $this->material_compras = $material_compras;
    }
    public function setAjuste($ajuste)
    {
        $this->ajuste = $ajuste;
    }
    public function setDescuento($descuento)
    {
        $this->descuento = $descuento;
    }


   //constructor
   public function __construct(){
    $this->conexion = new Conexion();
    $this->db = $this->conexion->connectGeneral();
    parent::__construct();
}



    public function selectAllInventario()
    {
        $sql = "SELECT *  FROM movimiento_existencia ORDER BY id_movimiento DESC";
        $request = $this->searchAll($sql);
        return $request;
    }
}
