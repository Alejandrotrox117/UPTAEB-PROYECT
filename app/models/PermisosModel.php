<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
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

    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->connect();
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


 public function obtenerTodosLosPermisos()
{
    $sql = "SELECT
  u.idusuario,
  p.idpermiso,
  p.estatus,
  u.correo AS usuario_correo,
  r.nombre AS rol,
  m.titulo AS modulo,
  p.nombre AS permiso_nombre,
  p.estatus AS permiso_estatus,
  p.fecha_creacion,
  p.ultima_modificacion
FROM permisos p
JOIN usuarios u ON p.idusuario = u.idusuario
JOIN roles r ON p.idrol = r.idrol
JOIN modulos m ON p.idmodulo = m.idmodulo
ORDER BY u.idusuario, m.titulo;
";

    $stmt = $this->db->query($sql);
    $todosPermisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'permisos' => $todosPermisos];
}


 public function desactivarPermiso($id)
    {
        try {
            $sql = "UPDATE permisos SET estatus = 'Inactivo' WHERE idpermiso = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Puedes loguear el error si deseas
            return false;
        }
    }









}
?>