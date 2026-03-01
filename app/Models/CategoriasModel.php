<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;

class CategoriasModel
{
    private $objModelCategoriasModel;

    private $idcategoria;
    private $nombre;
    private $descripcion;
    private $estatus;

    public function __construct()
    {
        // Constructor vacío según el patrón de doble instancia
    }

    private function getInstanciaModel()
    {
        if ($this->objModelCategoriasModel == null) {
            $this->objModelCategoriasModel = new CategoriasModel();
        }
        return $this->objModelCategoriasModel;
    }

    // Métodos Getters y Setters
    public function getIdcategoria()
    {
        return $this->idcategoria;
    }

    public function setIdcategoria($idcategoria)
    {
        $this->idcategoria = $idcategoria;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    // ==========================================
    // MÉTODOS PÚBLICOS (PROXIES)
    // ==========================================

    public function SelectAllCategorias()
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarSelectAllCategorias();
    }

    public function insertCategoria($data)
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarInsertCategoria($data);
    }

    public function deleteCategoria($idcategoria)
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarDeleteCategoria($idcategoria);
    }

    public function updateCategoria($data)
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarUpdateCategoria($data);
    }

    public function getCategoriaById($idcategoria)
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarGetCategoriaById($idcategoria);
    }

    public function reactivarCategoria($idcategoria)
    {
        $objModelCategoriasModel = $this->getInstanciaModel();
        return $objModelCategoriasModel->ejecutarReactivarCategoria($idcategoria);
    }

    // ==========================================
    // MÉTODOS PRIVADOS (TRABAJADORES)
    // ==========================================

    private function ejecutarSelectAllCategorias()
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "SELECT * FROM categoria ORDER BY idcategoria ASC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CategoriasModel: Error al seleccionar todos las categorias - " . $e->getMessage());
            return [];
        } finally {
            $conexionParams->disconnect();
        }
    }

    private function ejecutarInsertCategoria($data)
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "INSERT INTO categoria (
                        nombre, descripcion, estatus
                    ) VALUES (?, ?, ?)";

            $stmt = $db->prepare($sql);
            $arrValues = [
                $data['nombre'],
                $data['descripcion'],
                strtoupper($data['estatus'])  // Normalizar a mayúsculas
            ];

            return $stmt->execute($arrValues);
        } catch (PDOException $e) {
            error_log("Error al insertar categoria: " . $e->getMessage());
            return false;
        } finally {
            $conexionParams->disconnect();
        }
    }

    private function ejecutarDeleteCategoria($idcategoria)
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "UPDATE categoria SET estatus = 'INACTIVO' WHERE idcategoria = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$idcategoria]);
        } catch (PDOException $e) {
            error_log("Error al eliminar categoria: " . $e->getMessage());
            return false;
        } finally {
            $conexionParams->disconnect();
        }
    }

    private function ejecutarUpdateCategoria($data)
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "UPDATE categoria SET 
                        nombre = ?, 
                        descripcion = ?, 
                        estatus = ? 
                    WHERE idcategoria = ?";

            $stmt = $db->prepare($sql);
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
        } finally {
            $conexionParams->disconnect();
        }
    }

    private function ejecutarGetCategoriaById($idcategoria)
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "SELECT * FROM categoria WHERE idcategoria = ?";
            $stmt = $db->prepare($sql);
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
            error_log("CategoriasModel: Error al obtener categoria por ID - " . $e->getMessage());
            return null;
        } finally {
            $conexionParams->disconnect();
        }
    }

    private function ejecutarReactivarCategoria($idcategoria)
    {
        $conexionParams = new Conexion();
        $conexionParams->connect();
        $db = $conexionParams->get_conectGeneral();

        try {
            $sql = "UPDATE categoria SET estatus = 'ACTIVO' WHERE idcategoria = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$idcategoria]);
        } catch (PDOException $e) {
            error_log("Error al reactivar categoria: " . $e->getMessage());
            return false;
        } finally {
            $conexionParams->disconnect();
        }
    }
}