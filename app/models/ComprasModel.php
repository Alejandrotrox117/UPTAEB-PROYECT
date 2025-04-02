<?php

require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
class ComprasModel extends Mysql
{
    private $db;
    private $conexion;
    private $fecha;
    private $id;
    private $inv_inicial;
    private $inv_final;
    private $compras;
    private $ajustes;
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
    public function getInv_inicial()
    {
        return $this->inv_inicial;
    }
    public function getInv_final()
    {
        return $this->inv_final;
    }
    public function getCompra()
    {
        return $this->compras;
    }
    public function getAjustes()
    {
        return $this->ajustes;
    }
    public function getDescuento()
    {
        return $this->descuento;
    }

    //setters
    public function id($id)
    {
        $this->id = $id;
    }
    public function fecha($fecha)
    {
        $this->fecha = $fecha;
    }
    public function inv_inicial($inv_inicial)
    {
        $this->inv_inicial = $inv_inicial;
    }
    public function inv_final($inv_final)
    {
        $this->inv_final = $inv_final;
    }
    public function compras($compras)
    {
        $this->compras = $compras;
    }
    public function ajustes($ajustes)
    {
        $this->ajustes = $ajustes;
    }
    public function descuento($descuento)
    {
        $this->descuento = $descuento;
    }
    //constructor
    public function __construct(){
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connectGeneral();
        parent::__construct();
    }



  

   
    
        public function selectCompras() {
            $sql = "SELECT * FROM compra_materiales";
            return $this->searchAll($sql);
    
    
    
}
}
