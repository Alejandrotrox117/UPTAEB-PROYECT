<?php

require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
class ComprasModel extends Mysql
{

    private $db;
    private $conexion;
    private $numero;
    private $fecha;
    private $id;
    private $proveedor;
    private $peso_vehiculo;
    private $peso_bruto;
    private $peso_neto;
    private $precio_kg;
    private $descuento;
    private $subtotal;
    private $total;

    //getters
    public function getid()
    {
        return $this->id;
    }
    public function getfecha()
    {
        return $this->fecha;
    }
    public function getnumero()
    {
        return $this->numero;
    }
    
    public function getproveedor()
    {
        return $this->proveedor;
    }
    public function getpeso_vehiculo()
    {
        return $this->peso_vehiculo;
    }
    public function getcompra()
    {
        return $this->peso_bruto;
    }
    public function getpeso_neto()
    {
        return $this->peso_neto;
    }
    public function getprecio_kg()
    {
        return $this->precio_kg;
    }
    public function getdescuento()
    {
        return $this->descuento;
    }
    public function getsubtotal()
    {
        return $this->subtotal;
    }
    public function gettotal()
    {
        return $this->total;
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
    public function numero($numero)
    {
        $this->numero = $numero;
    }
    public function proveedor($proveedor)
    {
        $this->proveedor = $proveedor;
    }
    public function peso_vehiculo($peso_vehiculo)
    {
        $this->peso_vehiculo = $peso_vehiculo;
    }
    public function peso_bruto($peso_bruto)
    {
        $this->peso_bruto = $peso_bruto;
    }
    public function peso_neto($peso_neto)
    {
        $this->peso_neto = $peso_neto;
    }
    public function precio_kg($precio_kg)
    {
        $this->precio_kg = $precio_kg;
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

    public function SelectAllCompras() {
        $sql = "SELECT * FROM compras";
        return $this->searchAll($sql);
    }

public function insertCompra($data) {
    $query = "INSERT INTO compras (idproveedor, idmaterial, peso_bruto, peso_neto, peso_vehiculo, subtotal, descuento_porcentaje, total)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $this->db->prepare($query); // Prepara la consulta
    $arrValues = [
        $data['proveedor'], 
        $data['tipo_material'], 
        $data['peso_bruto'], 
        $data['peso_neto'], 
        $data['peso_vehiculo'], 
        $data['subtotal'], 
        $data['porcentaje_descuento'], 
        $data['total']
    ];
    return $stmt->execute($arrValues); // Ejecuta la consulta con los valores
}
    
    
}
