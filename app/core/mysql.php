<?php

require_once __DIR__ . "/conexion.php";

class Mysql extends Conexion
{
    private $conexionGeneral;
    private $conexionSeguridad;
    private $query;
    private $arrValues;
    private $currentDbName;

    public function set_conexionGeneral($conexionGeneral)
    {
        $this->conexionGeneral = $conexionGeneral;
    }
    
    public function set_conexionSeguridad($conexionSeguridad)
    {
        $this->conexionSeguridad = $conexionSeguridad;
    }
    
    public function set_query($query)
    {
        $this->query = $query;
    }
    
    public function set_arrValues($arrValues)
    {
        $this->arrValues = $arrValues;
    }
    
    public function get_conexionGeneral()
    {
        return $this->conexionGeneral;
    }
    
    public function get_conexionSeguridad()
    {
        return $this->conexionSeguridad;
    }
    
    public function get_query()
    {
        return $this->query;
    }
    
    public function get_arrValues()
    {
        return $this->arrValues;
    }

    public function __construct()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $this->set_conexionGeneral($conexion->get_conectGeneral());
        $this->set_conexionSeguridad($conexion->get_conectSeguridad());
    }

    // ----------- MÉTODOS PARA BASE GENERAL -----------

    public function insert(string $query, array $arrValues)
    {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionGeneral()->prepare($this->get_query());
            
            if ($insert->execute($this->get_arrValues())) {
                $lastInsert = $this->get_conexionGeneral()->lastInsertId();
                if ($lastInsert) {
                    return $lastInsert;
                } else {
                    // Para casos donde no hay AUTO_INCREMENT pero la inserción fue exitosa
                    return true;
                }
            } else {
                $errorInfo = $insert->errorInfo();
                throw new Exception("Error en la consulta INSERT: " . $errorInfo[2]);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en insert: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error en insert: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertPerson(string $query, array $arrValues)
    {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionGeneral()->prepare($this->get_query());
            
            if ($insert->execute($this->get_arrValues())) {
                $idPersona = $arrValues[0];
                return $idPersona;
            } else {
                $errorInfo = $insert->errorInfo();
                throw new Exception("Error en la consulta INSERT PERSON: " . $errorInfo[2]);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en insertPerson: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function update(string $query, array $arrValues)
    {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $update = $this->get_conexionGeneral()->prepare($this->get_query());
            $update->execute($this->get_arrValues());
            return $update->rowCount();
        } catch (PDOException $e) {
            error_log("Error PDO en update: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function updateOne($query)
    {
        try {
            $this->set_query($query);
            $update = $this->get_conexionGeneral()->prepare($this->get_query());
            $update->execute();
            return $update->rowCount();
        } catch (PDOException $e) {
            error_log("Error en updateOne: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function delete(string $query, array $arrValues = [])
    {
        try {
            $this->set_query($query);
            $delete = $this->get_conexionGeneral()->prepare($this->get_query());
            
            if (!empty($arrValues)) {
                $delete->execute($arrValues);
            } else {
                $delete->execute();
            }
            
            return $delete->rowCount();
        } catch (PDOException $e) {
            error_log("Error PDO en delete: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function search(string $query, array $params = [])
    {
        try {
            $this->set_query($query);
            $select = $this->get_conexionGeneral()->prepare($this->get_query());
            $select->execute($params);
            return $select->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error PDO en search: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function searchAll(string $query, array $params = [])
    {
        try {
            $this->set_query($query);
            $select = $this->get_conexionGeneral()->prepare($this->get_query());
            $select->execute($params);
            return $select->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error PDO en searchAll: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function searchAllPersona(string $query, array $params = [])
    {
        return $this->searchAll($query, $params);
    }

    public function searchAllParams(string $query, array $params = [])
    {
        return $this->searchAll($query, $params);
    }

    // ----------- MÉTODOS PARA BASE SEGURIDAD -----------

    public function insertSeguridad(string $query, array $arrValues)
    {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionSeguridad()->prepare($this->get_query());
            $insert->execute($this->get_arrValues());
            return $this->get_conexionSeguridad()->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error PDO en insertSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function updateSeguridad(string $query, array $arrValues)
    {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $update = $this->get_conexionSeguridad()->prepare($this->get_query());
            $update->execute($this->get_arrValues());
            return $update->rowCount();
        } catch (PDOException $e) {
            error_log("Error PDO en updateSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function updateOneSeguridad($query)
    {
        try {
            $this->set_query($query);
            $update = $this->get_conexionSeguridad()->prepare($this->get_query());
            $update->execute();
            return $update->rowCount();
        } catch (PDOException $e) {
            error_log("Error en updateOneSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function deleteSeguridad(string $query, array $arrValues = [])
    {
        try {
            $this->set_query($query);
            $delete = $this->get_conexionSeguridad()->prepare($this->get_query());
            
            if (!empty($arrValues)) {
                $delete->execute($arrValues);
            } else {
                $delete->execute();
            }
            
            return $delete->rowCount();
        } catch (PDOException $e) {
            error_log("Error PDO en deleteSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function searchSeguridad(string $query, array $params = [])
    {
        try {
            $this->set_query($query);
            $select = $this->get_conexionSeguridad()->prepare($this->get_query());
            $select->execute($params);
            return $select->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error PDO en searchSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function searchAllSeguridad(string $query, array $params = [])
    {
        try {
            $this->set_query($query);
            $select = $this->get_conexionSeguridad()->prepare($this->get_query());
            $select->execute($params);
            return $select->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error PDO en searchAllSeguridad: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }

    public function searchSeguridadParams(string $query, array $params = [])
    {
        return $this->searchSeguridad($query, $params);
    }

    public function searchAllSeguridadParams(string $query, array $params = [])
    {
        return $this->searchAllSeguridad($query, $params);
    }

    public function getDbName($conexion = null) 
    {
        if ($conexion) {
            try {
                $stmt = $conexion->query("SELECT DATABASE()");
                return $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Error al obtener dbname de conexión PDO: " . $e->getMessage());
                return null;
            }
        }
        return $this->currentDbName;
    }

    // Método para ejecutar transacciones
    public function executeTransaction(callable $callback)
    {
        $conexion = $this->get_conexionGeneral();
        try {
            $conexion->beginTransaction();
            $result = $callback($this);
            $conexion->commit();
            return $result;
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
    }
}
