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
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
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
    // Obtener todas las categorías
    public function SelectAllCategorias()
    {
        $sql = "SELECT * FROM categoria ORDER BY idcategoria ASC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CategoriasModel: Error al seleccionar todos las categorias- " . $e->getMessage());
            return [];
        }
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
            strtoupper($data['estatus'])  // Normalizar a mayúsculas
        ];
    
        return $stmt->execute($arrValues);
    }

    
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
            strtoupper($data['estatus']),  // Normalizar a mayúsculas
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

    // Método para reactivar una categoría (cambiar estatus de INACTIVO a ACTIVO)
    public function reactivarCategoria($idcategoria) {
        $sql = "UPDATE categoria SET estatus = 'ACTIVO' WHERE idcategoria = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idcategoria]);
    }
   
}