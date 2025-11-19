<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class romanaModel extends Mysql
{
    private $conexionObjeto;
    private $db;
    private $query;

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function __construct()
    {
        $this->conexionObjeto = new Conexion();
        $this->db = $this->conexionObjeto->connect();
    }

    public function __destruct()
    {
        if ($this->conexionObjeto) {
            $this->conexionObjeto->disconnect();
        }
    }

  
public function selectAllRomana()
{
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();

    try {
        $this->setQuery("SELECT idromana, peso, fecha, estatus, fecha_creacion FROM historial_romana ORDER BY idromana DESC");
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => true,
            'data' => $result
        ];
    } catch (PDOException $e) {
        error_log("RomanaModel::selectAllRomana - Error: " . $e->getMessage());
        return [
            'status' => false,
            'data' => [],
            'message' => 'Error al obtener los registros'
        ];
    } finally {
        $conexion->disconnect();
    }
}

}
   