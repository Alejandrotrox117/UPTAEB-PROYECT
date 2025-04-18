<?php
class Mysql extends Conexion {
    private $conexionGeneral;
    private $query;
    private $arrValues;

    public function __construct() {
        $this->set_conexionGeneral((new Conexion())->connectGeneral());
    }

    public function set_conexionGeneral($conexionGeneral) {
        $this->conexionGeneral = $conexionGeneral;
    }

    public function set_query($query) {
        $this->query = $query;
    }

    public function set_arrValues($arrValues) {
        $this->arrValues = $arrValues;
    }

    public function get_conexionGeneral() {
        return $this->conexionGeneral;
    }

    public function get_query() {
        return $this->query;
    }

    public function get_arrValues() {
        return $this->arrValues;
    }

    public function insert(string $query, array $arrValues) {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionGeneral()->prepare($this->get_query());
            if ($insert->execute($this->get_arrValues())) {
                return $this->get_conexionGeneral()->lastInsertId();
            } else {
                throw new Exception("Error al insertar los datos.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return 0;
        }
    }

    public function update(string $query, array $arrValues) {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $update = $this->get_conexionGeneral()->prepare($this->get_query());
            $update->execute($this->get_arrValues());
            return $update->rowCount();
        } catch (PDOException $e) {
            echo "Error en la consulta de actualización: " . $e->getMessage();
            return 0;
        }
    }

    public function delete(string $query) {
        try {
            $this->set_query($query);
            $delete = $this->get_conexionGeneral()->prepare($this->get_query());
            $delete->execute();
            return $delete->rowCount();
        } catch (PDOException $e) {
            echo "Error en la consulta de eliminación: " . $e->getMessage();
            return 0;
        }
    }

    public function searchAll($sql, $params = []) {
        try {
            $query = $this->get_conexionGeneral()->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return [];
        }
    }
}
?>