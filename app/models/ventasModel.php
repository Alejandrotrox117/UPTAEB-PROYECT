<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class VentasModel extends Mysql
{
    private $db;
    private $conexion;
    private $idventa;
    private $idcliente;
    private $idproducto;
    private $fecha;
    private $cantidad;
    private $estatus;
    private $descuento;
    private $total;

    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
       

    }

    // Métodos Getters y Setters
    public function getIdcliente()
    {
        return $this->idcliente;
    }

    public function setIdcliente($idcliente)
    {
        $this->idcliente = $idcliente;
    }

    public function getIdVenta()
    {
        return $this->idventa;
    }

    public function setIdVenta($idventa)
    {
        $this->idventa = $idventa;
    }
    public function getIdProducto()
    {
        return $this->idproducto;
    }
    public function setIdProducto($idproducto)
    {
        $this->idproducto = $idproducto;
    }


    public function getFecha()
    {
        return $this->fecha;
    }
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    public function getCantidad()
    {
        return $this->cantidad;
    }
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }
    public function getEstatus()
    {
        return $this->estatus;
    }
    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }


    public function getDescuento()
    {
        return $this->descuento;
    }
    public function setDescuento($descuento)
    {
        $this->descuento = $descuento;
    }
    public function getTotal()
    {
        return $this->total;
    }
    public function setTotal($total)
    {
        $this->total = $total;
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

    // Método para seleccionar todos los clientes activos
    public function selectAllVentas()
    {
        $sql = "SELECT 
                    v.idventa,
                    p.nombre AS nombre_producto,
                    v.fecha,
                    v.cantidad,
                    v.estatus,
                    v.descuento,
                    v.total,
                    v.fecha_creacion
                FROM 
                    venta v
                INNER JOIN 
                    producto p
                ON 
                    v.idproducto = p.idproducto";

        $result = $this->searchAll($sql);

        // Mapear los resultados a las propiedades de la clase
        $ventas = [];
        foreach ($result as $row) {
            $venta = new self();
            $venta->setIdVenta($row['idventa']);
            $venta->setIdProducto($row['nombre_producto']); // Aquí puedes manejar el nombre del producto
            $venta->setFecha($row['fecha']);
            $venta->setCantidad($row['cantidad']);
            $venta->setEstatus($row['estatus']);
            $venta->setDescuento($row['descuento']);
            $venta->setTotal($row['total']);
            $venta->setFechaCreacion($row['fecha_creacion']);
            $ventas[] = $venta; // Agregar el objeto correctamente al array
        }

        return $ventas;
    }

    // // Método para insertar un nuevo cliente
    // public function insertCliente()
    // {
    //     $sql = "INSERT INTO cliente (
    //                 cedula,
    //                 nombre, 
    //                 apellido, 
    //                 direccion, 
    //                 telefono_principal, 
    //                 estatus, 
    //                 observaciones
    //             ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    //     $stmt = $this->db->prepare($sql);
    //     $arrValues = [
    //         $this->getCedula(),
    //         $this->getNombre(),
    //         $this->getApellido(),
    //         $this->getDireccion(),
    //         $this->getTelefonoPrincipal(),
    //         $this->getEstatus(),
    //         $this->getObservaciones()
    //     ];

    //     return $stmt->execute($arrValues);
    // }

    // // Método para eliminar lógicamente un cliente
    // public function deleteCliente($clienteId)
    // {
    //     $sql = "UPDATE cliente SET estatus = 'INACTIVO' WHERE idcliente = ?";
    //     $stmt = $this->db->prepare($sql);
    //     return $stmt->execute([$clienteId]);
    // }

    // // Método para actualizar un cliente
    // public function updateCliente()
    // {
    //     $sql = "UPDATE cliente SET 
    //                 cedula = ?, 
    //                 nombre = ?, 
    //                 apellido = ?, 
    //                 direccion = ?, 
    //                 telefono_principal = ?, 
    //                 estatus = ?, 
    //                 observaciones = ? 
    //             WHERE idcliente = ?";

    //     $stmt = $this->db->prepare($sql);
    //     $arrValues = [
    //         $this->getCedula(),
    //         $this->getNombre(),
    //         $this->getApellido(),
    //         $this->getDireccion(),
    //         $this->getTelefonoPrincipal(),
    //         $this->getEstatus(),
    //         $this->getObservaciones(),
    //         $this->getIdcliente()
    //     ];

    //     return $stmt->execute($arrValues);
    // }

    // // Método para obtener un cliente por su ID
    // public function getClienteById($idcliente)
    // {
    //     $sql = "SELECT 
    //                 idcliente, 
    //                 cedula, 
    //                 nombre, 
    //                 apellido, 
    //                 direccion, 
    //                 telefono_principal, 
    //                 estatus, 
    //                 observaciones 
    //             FROM cliente 
    //             WHERE idcliente = ?";

    //     $stmt = $this->db->prepare($sql);
    //     $stmt->execute([$idcliente]);

    //     // Obtener el resultado
    //     $result = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if ($result) {
    //         // Mapear los datos a las propiedades de la clase
    //         $this->setIdcliente($result['idcliente']);
    //         $this->setCedula($result['cedula']);
    //         $this->setNombre($result['nombre']);
    //         $this->setApellido($result['apellido']);
    //         $this->setDireccion($result['direccion']);
    //         $this->setTelefonoPrincipal($result['telefono_principal']);
    //         $this->setEstatus($result['estatus']);
    //         $this->setObservaciones($result['observaciones']);

    //         return $result; // Retornar los datos como un array asociativo
    //     }

    //     return null; // Retornar null si no se encuentra el cliente
    // }
}
