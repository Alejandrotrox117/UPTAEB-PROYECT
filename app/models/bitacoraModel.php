<?php
require_once("app/core/conexion.php");


class bitacoraModel 
{
    private $db;
    private $conexion;
    private $dbSeguridad;

    
    private $idbitacora;
    private $tabla;
    private $accion;
    private $idusuario;
    private $fecha;

    public function __construct()
    {
       $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
         $this->dbSeguridad = $this->conexion->get_conectSeguridad();
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
    
  public function SelectAllBitacora()
    {
        $sql = "SELECT 
    b.idbitacora,
    b.tabla,
    b.accion,
    b.idusuario,
    CONCAT(p.nombre, ' ', p.apellido) AS nombre_usuario,
    b.fecha
FROM bitacora b
LEFT JOIN usuario u ON b.idusuario = u.idusuario
LEFT JOIN bd_pda.personas p ON u.personaId = p.idpersona
ORDER BY b.fecha DESC;";
        try {
            $stmt = $this->dbSeguridad->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CategoriasModel: Error al seleccionar todos las categorias- " . $e->getMessage());
            return [];
        }
    }
    





 

}
