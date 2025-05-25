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
                    throw new Exception("No se pudo obtener el ID insertado.");
                }
            } else {
                $errorInfo = $insert->errorInfo();
                throw new Exception("Error en la consulta: " . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return 0;
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
                throw new Exception("Error en la consulta: " . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return 0;
        }
    }

    public function update(string $query, array $arrValues)
    {
        $this->set_query($query);
        $this->set_arrValues($arrValues);
        $update = $this->get_conexionGeneral()->prepare($this->get_query());
        $update->execute($this->get_arrValues());
        return $update->rowCount();
    }

    public function updateOne($query)
    {
        try {
            $this->set_query($query);
            $update = $this->get_conexionGeneral()->prepare($this->get_query());
            $update->execute();
            return $update->rowCount();
        } catch (PDOException $e) {
            echo "Error en la consulta de actualización: " . $e->getMessage();
            return false;
        }
    }

    public function delete(string $query)
    {
        $this->set_query($query);
        $delete = $this->get_conexionGeneral()->prepare($this->get_query());
        $delete->execute();
        return $delete->rowCount();
    }

    public function search(string $query)
    {
        $this->set_query($query);
        $select = $this->get_conexionGeneral()->prepare($this->get_query());
        $select->execute();
        return $select->fetch(PDO::FETCH_ASSOC);
    }

    public function searchAll(string $query)
    {
        $this->set_query($query);
        $select = $this->get_conexionGeneral()->prepare($this->get_query());
        $select->execute();
        return $select->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchAllPersona(string $query, array $params = [])
    {
        $this->set_query($query);
        $select = $this->get_conexionGeneral()->prepare($this->get_query());
        $select->execute($params);
        return $select->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------- MÉTODOS PARA BASE SEGURIDAD -----------

    public function insertSeguridad(string $query, array $arrValues)
    {
        $this->set_query($query);
        $this->set_arrValues($arrValues);
        $insert = $this->get_conexionSeguridad()->prepare($this->get_query());
        $insert->execute($this->get_arrValues());
        return $this->get_conexionSeguridad()->lastInsertId();
    }

    public function updateSeguridad(string $query, array $arrValues)
    {
        $this->set_query($query);
        $this->set_arrValues($arrValues);
        $update = $this->get_conexionSeguridad()->prepare($this->get_query());
        $update->execute($this->get_arrValues());
        return $update->rowCount();
    }

    public function updateOneSeguridad($query)
    {
        try {
            $this->set_query($query);
            $update = $this->get_conexionSeguridad()->prepare($this->get_query());
            $update->execute();
            return $update->rowCount();
        } catch (PDOException $e) {
            echo "Error en la consulta de actualización: " . $e->getMessage();
            return false;
        }
    }

    public function deleteSeguridad(string $query)
    {
        $this->set_query($query);
        $delete = $this->get_conexionSeguridad()->prepare($this->get_query());
        $delete->execute();
        return $delete->rowCount();
    }

    public function searchSeguridad(string $query)
    {
        $this->set_query($query);
        $select = $this->get_conexionSeguridad()->prepare($this->get_query());
        $select->execute();
        return $select->fetch(PDO::FETCH_ASSOC);
    }

    public function searchAllSeguridad(string $query)
    {
        $this->set_query($query);
        $select = $this->get_conexionSeguridad()->prepare($this->get_query());
        $select->execute();
        return $select->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchSeguridadParams(string $query, array $params = [])
    {
        $this->set_query($query);
        $select = $this->get_conexionSeguridad()->prepare($this->get_query());
        $select->execute($params);
        return $select->fetch(PDO::FETCH_ASSOC);
    }

    public function searchAllSeguridadParams(string $query, array $params = [])
    {
        $this->set_query($query);
        $select = $this->get_conexionSeguridad()->prepare($this->get_query());
        $select->execute($params);
        return $select->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDbName($conexion = null) {
        if ($conexion) {
            try {
                $stmt = $conexion->query("SELECT DATABASE()");
                return $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Error al obtener dbname de conexión PDO: " . $e->getMessage());
                return null; // No se pudo determinar
            }
        }
        return $this->currentDbName;
    }
}
