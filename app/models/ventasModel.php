<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class VentasModel extends Mysql
{
    private $idventa;
    private $idcliente;
    private $fecha_venta;
    private $total_venta;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
    }

    // Getters y Setters
    public function getIdVenta()
    {
        return $this->idventa;
    }

    public function setIdVenta($idventa)
    {
        $this->idventa = $idventa;
    }

    public function getIdcliente()
    {
        return $this->idcliente;
    }

    public function setIdcliente($idcliente)
    {
        $this->idcliente = $idcliente;
    }

    public function getFechaVenta()
    {
        return $this->fecha_venta;
    }

    public function setFechaVenta($fecha_venta)
    {
        $this->fecha_venta = $fecha_venta;
    }

    public function getTotalVenta()
    {
        return $this->total_venta;
    }

    public function setTotalVenta($total_venta)
    {
        $this->total_venta = $total_venta;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    // Método para obtener clientes activos
    public function obtenerClientes()
    {
        try {
            $sql = "SELECT idcliente, nombre, apellido, cedula 
                    FROM clientes 
                    WHERE estatus = 'Activo' 
                    ORDER BY nombre, apellido";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener clientes: " . $e->getMessage());
            throw new Exception("Error al obtener clientes: " . $e->getMessage());
        }
    }

    // Método para obtener productos activos
    public function obtenerProductos()
    {
        try {
            $sql = "SELECT idproducto, nombre, precio 
                    FROM productos 
                    WHERE activo = 1 
                    ORDER BY nombre";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            throw new Exception("Error al obtener productos: " . $e->getMessage());
        }
    }

    // Método para crear una nueva venta
    public function crearVenta($idcliente, $fecha_venta, $total_venta, $detalles)
    {
        return $this->executeTransaction(function($mysql) use ($idcliente, $fecha_venta, $total_venta, $detalles) {
            
            // Validar datos de entrada
            if (empty($idcliente) || !is_numeric($idcliente)) {
                throw new Exception("El ID del cliente es requerido y debe ser numérico.");
            }
            
            if (empty($fecha_venta)) {
                throw new Exception("La fecha de venta es requerida.");
            }
            
            if (empty($total_venta) || !is_numeric($total_venta)) {
                throw new Exception("El total de la venta es requerido y debe ser numérico.");
            }
            
            if (empty($detalles) || !is_array($detalles)) {
                throw new Exception("Los detalles de la venta son requeridos.");
            }
            
            // Validar cada detalle
            foreach ($detalles as $detalle_item) {
                if (empty($detalle_item['detalle_idproducto']) || !is_numeric($detalle_item['detalle_idproducto'])) {
                    throw new Exception("Faltan datos de detalle de la venta ('detalle_idproducto') o no tienen el formato correcto.");
                }
                
                if (empty($detalle_item['detalle_cantidad']) || !is_numeric($detalle_item['detalle_cantidad'])) {
                    throw new Exception("Faltan datos de detalle de la venta ('detalle_cantidad') o no tienen el formato correcto.");
                }
                
                if (!isset($detalle_item['detalle_precio']) || !is_numeric($detalle_item['detalle_precio'])) {
                    throw new Exception("Faltan datos de detalle de la venta ('detalle_precio') o no tienen el formato correcto.");
                }
                
                if (!isset($detalle_item['detalle_total']) || !is_numeric($detalle_item['detalle_total'])) {
                    throw new Exception("Faltan datos de detalle de la venta ('detalle_total') o no tienen el formato correcto.");
                }
            }
            
            // Verificar que el cliente existe
            $cliente = $this->search("SELECT COUNT(*) as count FROM clientes WHERE idcliente = ?", [$idcliente]);
            if ($cliente['count'] == 0) {
                throw new Exception("El cliente especificado no existe.");
            }
            
            // Insertar la venta
            $sqlVenta = "INSERT INTO ventas (idcliente, fecha_venta, total_venta, estatus, fecha_creacion) 
                         VALUES (?, ?, ?, 'Activo', NOW())";
            
            $idventa = $this->insert($sqlVenta, [$idcliente, $fecha_venta, $total_venta]);
            
            if (!$idventa) {
                throw new Exception("No se pudo crear la venta.");
            }
            
            // Insertar los detalles
            $sqlDetalle = "INSERT INTO detalle_ventas (idventa, idproducto, cantidad, precio, total) 
                           VALUES (?, ?, ?, ?, ?)";
            
            foreach ($detalles as $detalle_item) {
                // Verificar que el producto existe
                $producto = $this->search("SELECT COUNT(*) as count FROM productos WHERE idproducto = ?", [$detalle_item['detalle_idproducto']]);
                if ($producto['count'] == 0) {
                    throw new Exception("El producto con ID {$detalle_item['detalle_idproducto']} no existe.");
                }
                
                $resultDetalle = $this->insert($sqlDetalle, [
                    $idventa,
                    $detalle_item['detalle_idproducto'],
                    $detalle_item['detalle_cantidad'],
                    $detalle_item['detalle_precio'],
                    $detalle_item['detalle_total']
                ]);
                
                if (!$resultDetalle) {
                    throw new Exception("Error al insertar detalle de venta para producto ID {$detalle_item['detalle_idproducto']}.");
                }
            }
            
            return ['success' => true, 'message' => 'Venta creada exitosamente', 'idventa' => $idventa];
        });
    }

    // Método para obtener todas las ventas
    public function obtenerVentas()
    {
        try {
            $sql = "SELECT v.idventa, v.fecha_venta, v.total_venta, v.estatus,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM ventas v
                    INNER JOIN clientes c ON v.idcliente = c.idcliente
                    ORDER BY v.idventa DESC";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener ventas: " . $e->getMessage());
            throw new Exception("Error al obtener ventas: " . $e->getMessage());
        }
    }

    // Método para obtener todas las ventas con información del cliente (para DataTables)
    public function obtenerTodasLasVentasConCliente()
    {
        try {
            $sql = "SELECT v.idventa,
                           CONCAT('V-', LPAD(v.idventa, 6, '0')) as nro_venta,
                           CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                           DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_venta,
                           FORMAT(v.total_venta, 2) as total_general,
                           v.estatus
                    FROM ventas v
                    INNER JOIN clientes c ON v.idcliente = c.idcliente
                    ORDER BY v.idventa DESC";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener ventas con cliente: " . $e->getMessage());
            throw new Exception("Error al obtener ventas con cliente: " . $e->getMessage());
        }
    }

    // Método para obtener una venta por ID
    public function obtenerVentaPorId($idventa)
    {
        try {
            $sql = "SELECT v.idventa, v.fecha_venta, v.total_venta, v.estatus,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM ventas v
                    INNER JOIN clientes c ON v.idcliente = c.idcliente
                    WHERE v.idventa = ?";
            return $this->search($sql, [$idventa]);
        } catch (Exception $e) {
            error_log("Error al obtener la venta: " . $e->getMessage());
            throw new Exception("Error al obtener la venta: " . $e->getMessage());
        }
    }

    // Método para obtener el detalle de una venta
    public function obtenerDetalleVenta($idventa)
    {
        try {
            $sql = "SELECT dv.cantidad as detalle_cantidad, 
                           dv.precio as detalle_precio, 
                           dv.total as detalle_total, 
                           p.nombre as producto_nombre
                    FROM detalle_ventas dv
                    INNER JOIN productos p ON dv.idproducto = p.idproducto
                    WHERE dv.idventa = ?
                    ORDER BY p.nombre";
            return $this->searchAll($sql, [$idventa]);
        } catch (Exception $e) {
            error_log("Error al obtener el detalle de la venta: " . $e->getMessage());
            throw new Exception("Error al obtener el detalle de la venta: " . $e->getMessage());
        }
    }

    // Método para eliminar/desactivar venta
    public function eliminarVenta($idventa)
    {
        return $this->executeTransaction(function($mysql) use ($idventa) {
            
            // Verificar que la venta existe
            $venta = $this->search("SELECT COUNT(*) as count FROM ventas WHERE idventa = ?", [$idventa]);
            if ($venta['count'] == 0) {
                throw new Exception("La venta especificada no existe.");
            }
            
            // En lugar de eliminar físicamente, cambiar el estatus
            $sqlUpdate = "UPDATE ventas SET estatus = 'Inactivo', fecha_modificacion = NOW() WHERE idventa = ?";
            $result = $this->update($sqlUpdate, [$idventa]);
            
            if ($result > 0) {
                return ['success' => true, 'message' => 'Venta desactivada exitosamente'];
            } else {
                throw new Exception("No se pudo desactivar la venta.");
            }
        });
    }

    // Método para buscar clientes por criterio
    public function buscarClientes($criterio)
    {
        try {
            $sql = "SELECT idcliente as id, nombre, apellido, cedula
                    FROM clientes 
                    WHERE estatus = 'Activo' 
                    AND (nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ?)
                    ORDER BY nombre, apellido
                    LIMIT 20";
            
            $parametroBusqueda = '%' . $criterio . '%';
            return $this->searchAll($sql, [$parametroBusqueda, $parametroBusqueda, $parametroBusqueda]);
        } catch (Exception $e) {
            error_log("Error al buscar clientes: " . $e->getMessage());
            throw new Exception("Error al buscar clientes: " . $e->getMessage());
        }
    }

    // Método para obtener productos para formulario
    public function getListaProductosParaFormulario()
    {
        try {
            $sql = "SELECT p.idproducto, 
                           p.nombre as nombre_producto,
                           p.precio as precio_unitario,
                           c.nombre as nombre_categoria
                    FROM productos p
                    LEFT JOIN categorias c ON p.idcategoria = c.idcategoria
                    WHERE p.activo = 1
                    ORDER BY p.nombre";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener productos para formulario: " . $e->getMessage());
            throw new Exception("Error al obtener productos para formulario: " . $e->getMessage());
        }
    }

    // Método para obtener monedas activas
    public function getMonedasActivas()
    {
        try {
            $sql = "SELECT idmoneda, codigo_moneda, nombre_moneda, valor
                    FROM monedas 
                    WHERE estatus = 'Activo'
                    ORDER BY codigo_moneda";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener monedas: " . $e->getMessage());
            throw new Exception("Error al obtener monedas: " . $e->getMessage());
        }
    }
}
?>
