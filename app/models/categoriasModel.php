<?php


require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class categoriasModel extends Mysql
{
    private $db;
    private $conexion;

    private $idcategoria;
    private $nombre;
    private $descripcion;
    private $estatus;
    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connect();
        parent::__construct();
    }

    // Métodos Getters y Setters
    public function getIdcategoria() {
        return $this->idcategoria;
    }

    public function setIdcategoria($idcategoria) {
        $this->idcategoria = $idcategoria;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
    }
    
    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }
    // Obtener todas las categorías activas
    public function SelectAllCategorias()
    {
        $sql = "SELECT * FROM categoria WHERE estatus = 'activo'";
        return $this->searchAll($sql);
    }

    public function insertCategoria($data)
    {
        $sql = "INSERT INTO categoria (
                    nombre, descripcion, estatus
                ) VALUES (?, ?, ?)";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['descripcion'],
            $data['estatus']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para eliminar lógicamente un categoria
    public function deleteCategoria($idcategoria) {
        $sql = "UPDATE categoria SET estatus = 'INACTIVO' WHERE idcategoria = ?";
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$idcategoria]); 
    }

    // Método para actualizar un categoria
    public function updateCategoria($data)
    {
        $sql = "UPDATE categoria SET 
                    nombre = ?, 
                    descripcion = ?, 
                    estatus = ? 
                WHERE idcategoria = ?";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['descripcion'],
            $data['estatus'],
            $data['idcategoria']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para obtener un categoria por ID
    public function getCategoriaById($idcategoria) {
        $sql = "SELECT * FROM categoria WHERE idcategoria = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idcategoria]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Asignar los valores a las propiedades del objeto
            $this->setIdcategoria($data['idcategoria']);
            $this->setNombre($data['nombre']);
            $this->setDescripcion($data['descripcion']);
    
            $this->setEstatus($data['estatus']);
        }

        return $data; 
    }
   
}