<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class DetalleProduccionModel extends Mysql
{
    private $db;
    private $conexion;

    // Atributos de la tabla `detalle_produccion`
    private $iddetalle_produccion;
    private $idproduccion;
    private $idmaterial;
    private $cantidad;
    private $unidad_medida;
    private $cantidad_consumida;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdDetalleProduccion()
    {
        return $this->iddetalle_produccion;
    }

    public function setIdDetalleProduccion($iddetalle_produccion)
    {
        $this->iddetalle_produccion = $iddetalle_produccion;
    }

    public function getIdProduccion()
    {
        return $this->idproduccion;
    }

    public function setIdProduccion($idproduccion)
    {
        $this->idproduccion = $idproduccion;
    }

    public function getIdMaterial()
    {
        return $this->idmaterial;
    }

    public function setIdMaterial($idmaterial)
    {
        $this->idmaterial = $idmaterial;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }

    public function getUnidadMedida()
    {
        return $this->unidad_medida;
    }

    public function setUnidadMedida($unidad_medida)
    {
        $this->unidad_medida = $unidad_medida;
    }

    public function getCantidadConsumida()
    {
        return $this->cantidad_consumida;
    }

    public function setCantidadConsumida($cantidad_consumida)
    {
        $this->cantidad_consumida = $cantidad_consumida;
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

    // Método para seleccionar todos los detalles de una producción
    public function SelectDetallesByProduccion($idproduccion)
    {
        $sql = "SELECT 
                    dp.iddetalle_produccion, 
                    i.nombre AS nombre_material, 
                    dp.cantidad, 
                    dp.unidad_medida, 
                    dp.cantidad_consumida 
                FROM detalle_produccion dp
                INNER JOIN inventario i ON dp.idmaterial = i.idmaterial
                WHERE dp.idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para insertar un nuevo detalle de producción
    public function insertDetalleProduccion($data)
    {
        $sql = "INSERT INTO detalle_produccion (
                    idproduccion, 
                    idmaterial, 
                    cantidad, 
                    unidad_medida
                ) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['idproduccion'],
            $data['idmaterial'],
            $data['cantidad'],
            $data['unidad_medida']
        ];

        return $stmt->execute($arrValues);
    }

    // Método para actualizar la cantidad consumida en un detalle
    public function updateCantidadConsumida($iddetalle_produccion, $cantidad_consumida)
    {
        $sql = "UPDATE detalle_produccion 
                SET cantidad_consumida = ?, fecha_modificacion = NOW() 
                WHERE iddetalle_produccion = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cantidad_consumida, $iddetalle_produccion]);
    }

    // Método para eliminar los detalles de una producción
    public function deleteDetallesByProduccion($idproduccion)
    {
        $sql = "DELETE FROM detalle_produccion WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idproduccion]);
    }
}