<?php


require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class monedaModel extends \Mysql
{
    private $db;
    private $conexion;

    private $idmoneda;
    private $nombre;
    private $valor;
    private $estatus;
    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connectGeneral();
        parent::__construct();
    }

    // Métodos Getters y Setters
    public function getIdmoneda() {
        return $this->idmoneda;
    }

    public function setIdmoneda($idmoneda) {
        $this->idmoneda = $idmoneda;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getValor() {
        return $this->valor;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }
    
    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }
    // Obtener todas las categorías activas
    public function SelectAllMoneda()
    {
        $sql = "SELECT * FROM monedas WHERE estatus = 'activo'";
        return $this->searchAll($sql);
    }

    public function insertMoneda($data)
    {
        $sql = "INSERT INTO monedas (
                    nombre_moneda, valor, estatus
                ) VALUES (?, ?, ?)";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['valor'],
            $data['estatus']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para eliminar lógicamente un categoria
    public function deleteMoneda($idmoneda) {
        $sql = "UPDATE monedas SET estatus = 'INACTIVO' WHERE idmoneda = ?";
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$idmoneda]); 
    }

    // Método para actualizar un categoria
    public function updateMoneda($data)
    {
        $sql = "UPDATE monedas SET 
                    nombre_moneda = ?, 
                    valor = ?, 
                    estatus = ? 
                WHERE idmoneda = ?";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre_moneda'],
            $data['valor'],
            $data['estatus'],
            $data['idmoneda']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para obtener un categoria por ID
    public function getMonedaById($idmoneda) {
        $sql = "SELECT * FROM monedas WHERE idmoneda = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idmoneda]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Asignar los valores a las propiedades del objeto
            $this->setIdmoneda($data['idmoneda']);
            $this->setNombre($data['nombre_moneda']);
            $this->setValor($data['valor']);
    
            $this->setEstatus($data['estatus']);
        }

        return $data; 
    }
   
}