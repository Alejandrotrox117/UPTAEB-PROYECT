<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class ProduccionModel extends Mysql
{
    private $db;
    private $conexion;

    // Atributos de la tabla `produccion`
    private $idproduccion;
    private $idempleado;
    private $idproducto;
    private $cantidad_a_realizar;
    private $fecha_inicio;
    private $fecha_fin;
    private $estado;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdProduccion()
    {
        return $this->idproduccion;
    }

    public function setIdProduccion($idproduccion)
    {
        $this->idproduccion = $idproduccion;
    }

    public function getIdEmpleado()
    {
        return $this->idempleado;
    }

    public function setIdEmpleado($idempleado)
    {
        $this->idempleado = $idempleado;
    }

    public function getIdProducto()
    {
        return $this->idproducto;
    }

    public function setIdProducto($idproducto)
    {
        $this->idproducto = $idproducto;
    }

    public function getCantidadARealizar()
    {
        return $this->cantidad_a_realizar;
    }

    public function setCantidadARealizar($cantidad_a_realizar)
    {
        $this->cantidad_a_realizar = $cantidad_a_realizar;
    }

    public function getFechaInicio()
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio($fecha_inicio)
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getFechaFin()
    {
        return $this->fecha_fin;
    }

    public function setFechaFin($fecha_fin)
    {
        $this->fecha_fin = $fecha_fin;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getFechaModificacion()
    {
        return $this->fecha_modificacion;
    }

    public function setFechaModificacion($fecha_modificacion)
    {
        $this->fecha_modificacion = $fecha_modificacion;
    }

    // Método para seleccionar todas las producciones activas
    public function SelectAllProducciones()
    {
        $sql = "SELECT 
                    p.idproduccion, 
                    pr.nombre AS nombre_producto, 
                    e.nombre AS nombre_empleado, 
                    p.cantidad_a_realizar, 
                    p.fecha_inicio, 
                    p.fecha_fin, 
                    p.estado 
                FROM produccion p
                INNER JOIN producto pr ON p.idproducto = pr.idproducto
                INNER JOIN empleado e ON p.idempleado = e.idempleado
                WHERE p.estado = 'borrador'";
        return $this->searchAll($sql);
    }

    // Método para insertar una nueva producción
    public function insertProduccion($data)
    {
        $sql = "INSERT INTO produccion (
                    idempleado, 
                    idproducto, 
                    cantidad_a_realizar, 
                    fecha_inicio, 
                    fecha_fin, 
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['idempleado'],
            $data['idproducto'],
            $data['cantidad_a_realizar'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado']
        ];

        return $stmt->execute($arrValues);
    }

    // Método para eliminar lógicamente una producción
    public function deleteProduccion($idproduccion)
    {
        $sql = "UPDATE produccion SET estado = 'inactivo' WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idproduccion]);
    }

    // Método para actualizar una producción
    public function updateProduccion($data)
    {
        $sql = "UPDATE produccion SET 
                    idempleado = ?, 
                    idproducto = ?, 
                    cantidad_a_realizar = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?, 
                    estado = ?, 
                    fecha_modificacion = ?
                WHERE idproduccion = ?";

        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['idempleado'],
            $data['idproducto'],
            $data['cantidad_a_realizar'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado'],
            $data['fecha_modificacion'],
            $data['idproduccion']
        ];

        return $stmt->execute($arrValues);
    }

    // Método para obtener una producción por ID
    public function getProduccionById($idproduccion)
    {
        $sql = "SELECT 
                    idproduccion, 
                    idempleado, 
                    idproducto, 
                    cantidad_a_realizar, 
                    fecha_inicio, 
                    fecha_fin, 
                    estado, 
                    fecha_creacion, 
                    fecha_modificacion 
                FROM produccion 
                WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}