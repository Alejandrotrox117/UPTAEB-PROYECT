<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class romanaModel extends Mysql
{
    private $conexionObjeto;
    private $db;

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

    public function SelectAllRomana() {
        $sql = "SELECT `idromana`, `peso`, `fecha`, `fecha_creacion` FROM `historial_romana` ORDER BY `idromana` DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductosModel: Error al seleccionar todos los productos con categoría - " . $e->getMessage());
            return [];
        }
    }

}
   