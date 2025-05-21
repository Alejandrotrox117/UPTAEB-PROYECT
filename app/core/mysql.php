<?php
require_once __DIR__ . "/conexion.php";

class Mysql extends Conexion{
    private $conexionGeneral;
    private $conexionSeguridad;
    private $query;
    private $arrValues;

    public function set_conexionGeneral($conexionGeneral){
        $this->conexionGeneral = $conexionGeneral;
    }

   // Cierra la conexión a la base de datos
    public function __destruct()
    {
        $this->conexionGeneral = null; // Cierra la conexión a la base de datos
    }

    public function set_query($query){
        $this->query = $query;
    }

    public function set_arrValues($arrValues){
        $this->arrValues = $arrValues;
    }

    public function get_conexionGeneral(){
        return $this->conexionGeneral;
    }

   

    public function get_query(){
        return $this->query;
    }

    public function get_arrValues(){
        return $this->arrValues;
    }

    public function __construct(){
        $this->set_conexionGeneral((new Conexion())->connect());
        }

    public function insert(string $query, array $arrValues) {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionGeneral()->prepare($this->get_query());
    
            // Ejecutar la consulta y verificar si se realizó correctamente
            if ($insert->execute($this->get_arrValues())) {
                // Obtener el ID insertado correctamente
                $lastInsert = $this->get_conexionGeneral()->lastInsertId();
                if ($lastInsert) {
                    return $lastInsert;
                } else {
                    throw new Exception("No se pudo obtener el ID insertado.");
                }
            } else {
                // Obtener información detallada del error
                $errorInfo = $insert->errorInfo();
                throw new Exception("Error en la consulta: " . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            // Manejo de errores
            echo "Error: " . $e->getMessage();
            return 0; // Retorna 0 si hubo un error
        }
    }

    public function insertPerson(
        string $query,
        array $arrValues
    ) {
        try {
            $this->set_query($query);
            $this->set_arrValues($arrValues);
            $insert = $this->get_conexionGeneral()->prepare($this->get_query());
    
            // Ejecutar la consulta y verificar si se realizó correctamente
            if ($insert->execute($this->get_arrValues())) {
                // Retornar el ID que acabas de insertar
                $idPersona = $arrValues[0]; // Suponiendo que el ID está en la primera posición de $arrValues
                return $idPersona;
            } else {
                // Obtener información detallada del error
                $errorInfo = $insert->errorInfo();
                throw new Exception("Error en la consulta: " . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            // Manejo de errores
            echo "Error: " . $e->getMessage();
            return 0; // Retorna 0 si hubo un error
        }
    }

    

    public function update(string $query, array $arrValues){
        $this->set_query($query);
        $this->set_arrValues($arrValues);
        $update = $this->get_conexionGeneral()->prepare($this->get_query());
        $update->execute($this->get_arrValues());
        $rowCount = $update->rowCount();
        return $rowCount;
    }

    //actualizar un campo
    public function updateOne($query) {
        try {
            $this->set_query($query);
            $update = $this->get_conexionGeneral()->prepare($this->get_query());
            $update->execute();
            return $update->rowCount();
        } catch(PDOException $e) {
            echo "Error en la consulta de actualización: " . $e->getMessage();
            return false;
        }
    }
    
    
    public function delete(string $query){
        $this->set_query($query);
        $delete = $this->get_conexionGeneral()->prepare($this->get_query());
        $delete->execute();
        $rowCount = $delete->rowCount();
        return $rowCount;
    }

   
    

    public function searchAll(string $query)
    {
        try {
            $this->set_query($query);
            $select = $this->get_conexionGeneral()->prepare($this->get_query());
            $select->execute();
            $data = $select->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } finally {
            $this->conexionGeneral = null; // Cierra la conexión
        }
    }

   
    
   
   
//Esta es una funcion que sirve para buscar con parametros a un cliente, proveedor o usuario en los controladores
public function searchAllParams(string $query, array $params = [])
{
    try {
        $this->set_query($query);
        $select = $this->get_conexionGeneral()->prepare($this->get_query());
        $select->execute($params);
        $data = $select->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    } finally {
        $this->conexionGeneral = null; // Cierra la conexión
    }
}

}
?>