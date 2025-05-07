<?php
require_once("app/core/conexion.php");

class PermisosModel extends Mysql
{
    // Atributos de la tabla permisos
    private $idpermiso;
    private $idrol;
    private $idmodulo;
    private $idusuario;
    private $nombre;
    private $estatus;
    private $fecha_creacion;
    private $ultima_modificacion;

    public  function __construct()
    {
        $this->conexion  = new Conexion();
    }

    // Métodos Getters y Setters para cada propiedad

    public function getIdpermiso()
    {
        return $this->idpermiso;
    }

    public function setIdpermiso($idpermiso)
    {
        $this->idpermiso = $idpermiso;
    }

    public function getIdrol()
    {
        return $this->idrol;
    }

    public function setIdrol($idrol)
    {
        $this->idrol = $idrol;
    }

    public function getIdmodulo()
    {
        return $this->idmodulo;
    }

    public function setIdmodulo($idmodulo)
    {
        $this->idmodulo = $idmodulo;
    }

    public function getIdusuario()
    {
        return $this->idusuario;
    }

    public function setIdusuario($idusuario)
    {
        $this->idusuario = $idusuario;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getUltimaModificacion()
    {
        return $this->ultima_modificacion;
    }

    public function setUltimaModificacion($ultima_modificacion)
    {
        $this->ultima_modificacion = $ultima_modificacion;
    }

   
   
}
?>
