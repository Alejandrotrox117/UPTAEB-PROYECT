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
    // Obtener todas las categorías activas
    public function SelectAllCategorias()
    {
        $sql = "SELECT * FROM categoria WHERE estatus = 'activo'";
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
        try {
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
        } catch (PDOException $e) {
            error_log("Error al insertar categoria: " . $e->getMessage());
            return false;
        }
    }

    
    public function deleteCategoria($idcategoria) {
        try {
            $sql = "UPDATE categoria SET estatus = 'INACTIVO' WHERE idcategoria = ?";
            $stmt = $this->db->prepare($sql); 
            return $stmt->execute([$idcategoria]);
        } catch (PDOException $e) {
            error_log("Error al eliminar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Método para actualizar un categoria
    public function updateCategoria($data)
    {
        try {
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
        } catch (PDOException $e) {
            error_log("Error al actualizar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Método para obtener un categoria por ID
    public function getCategoriaById($idcategoria) {
        try {
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
        } catch (PDOException $e) {
            error_log("Error al obtener categoria por ID: " . $e->getMessage());
            return false;
        }
    }
   
}