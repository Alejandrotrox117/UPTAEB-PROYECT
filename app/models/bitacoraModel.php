<?php


class bitacoraModel 
{
    private $db;

    // Propiedades que corresponden a cada columna de la tabla
    private $idbitacora;
    private $tabla;
    private $accion;
    private $idusuario;
    private $fecha;

    public function __construct()
    {
        $this->db = (new Conexion())->connect();
    }

    // Getters y Setters

    public function getIdbitacora()
    {
        return $this->idbitacora;
    }

    public function setIdbitacora($idbitacora)
    {
        $this->idbitacora = $idbitacora;
    }

    public function getTabla()
    {
        return $this->tabla;
    }

    public function setTabla($tabla)
    {
        $this->tabla = $tabla;
    }

    public function getAccion()
    {
        return $this->accion;
    }

    public function setAccion($accion)
    {
        $this->accion = $accion;
    }

    public function getIdusuario()
    {
        return $this->idusuario;
    }

    public function setIdusuario($idusuario)
    {
        $this->idusuario = $idusuario;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    
   public function insertar()
{
    try {
        $query = "INSERT INTO bitacora (tabla, accion, idusuario, fecha) VALUES (:tabla, :accion, :idusuario, :fecha)";
        $stmt = $this->db->prepare($query);

        // Si no se asigna fecha manualmente, se usará la actual por defecto
        $fecha = $this->getFecha() ?? date("Y-m-d H:i:s");

        $stmt->bindParam(":tabla", $this->tabla);
        $stmt->bindParam(":accion", $this->accion);
        $stmt->bindParam(":idusuario", $this->idusuario);
        $stmt->bindParam(":fecha", $fecha);

        return $stmt->execute();
    } catch (PDOException $e) {
        // Puedes imprimir el error para depuración o guardarlo en logs
        echo "Error al insertar en bitacora: " . $e->getMessage();
        return false;
    }
}





 public function insertar2()
{
    try {
        require_once "app/core/Conexion.php"; 
        $query = "INSERT INTO bitacora (tabla, accion, idusuario, fecha) VALUES (:tabla, :accion, :idusuario, :fecha)";
        $stmt = $this->db->prepare($query);

        // Si no se asigna fecha manualmente, se usará la actual por defecto
        $fecha = $this->getFecha() ?? date("Y-m-d H:i:s");

        $stmt->bindParam(":tabla", $this->tabla);
        $stmt->bindParam(":accion", $this->accion);
        $stmt->bindParam(":idusuario", $this->idusuario);
        $stmt->bindParam(":fecha", $fecha);

        return $stmt->execute();
    } catch (PDOException $e) {
        // Puedes imprimir el error para depuración o guardarlo en logs
        echo "Error al insertar en bitacora: " . $e->getMessage();
        return false;
    }
}

}
