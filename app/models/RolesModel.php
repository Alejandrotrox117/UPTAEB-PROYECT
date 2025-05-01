<?php
require_once("app/core/conexion.php");

class RolesModel
{
    private $conn;
    private $idrol;
    private $nombre;
    private $estatus;
    private $descripcion;
    private $fecha_creacion;
    private $ultima_modificacion;

    public function __construct()
    {
        $this->conn = (new Conexion())->connect();
    }

    // Métodos GET
    public function getIdrol() { return $this->idrol; }
    public function getNombre() { return $this->nombre; }
    public function getEstatus() { return $this->estatus; }
    public function getDescripcion() { return $this->descripcion; }
    public function getFechaCreacion() { return $this->fecha_creacion; }
    public function getUltimaModificacion() { return $this->ultima_modificacion; }

    // Métodos SET
    public function setIdrol($idrol) { $this->idrol = $idrol; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }
    public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }
    public function setFechaCreacion($fecha) { $this->fecha_creacion = $fecha; }
    public function setUltimaModificacion($fecha) { $this->ultima_modificacion = $fecha; }

    // Guardar un nuevo rol
    public function guardarRol()
    {
        $sql = "INSERT INTO roles (nombre, estatus, descripcion, fecha_creacion)
                VALUES (:nombre, :estatus, :descripcion, :fecha_creacion)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombre' => $this->nombre,
            ':estatus' => $this->estatus,
            ':descripcion' => $this->descripcion,
            ':fecha_creacion' => date('Y-m-d H:i:s')
        ]);
    }

    // Obtener todos los roles según el ID del rol del usuario
    public function getRoles($userRole)
    {
        if ($userRole == 3) {
            $sql = "SELECT idrol, nombre, estatus, descripcion FROM roles";
        } elseif ($userRole == 1) {
            $sql = "SELECT idrol, nombre, estatus, descripcion FROM roles WHERE estatus = 'Activo' AND idrol != 3";
        } else {
            return null;
        }

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un rol por ID
    public function getRolById($id)
    {
        $sql = "SELECT * FROM roles WHERE idrol = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Eliminar (inhabilitar) un rol
    public function eliminarRol($id)
    {
        $sql = "UPDATE roles SET estatus = 'Inactivo', ultima_modificacion = :fecha WHERE idrol = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':fecha' => date('Y-m-d H:i:s')
        ]);
    }
}
?>
