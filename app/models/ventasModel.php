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
                    FROM cliente
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
  private function generarNumeroVenta()
    {
        // Busca el último idventa registrado
        $sql = "SELECT MAX(idventa) as ultimo_id FROM venta";
        $result = $this->search($sql);
        $ultimoId = isset($result['ultimo_id']) ? intval($result['ultimo_id']) : 0;
        $nuevoId = $ultimoId + 1;
        // Formato: V-000001
        return 'V-' . str_pad($nuevoId, 6, '0', STR_PAD_LEFT);
    }

    // Método para crear una nueva venta
    public function crearVenta($data, $detalles)
    {
        return $this->executeTransaction(function($mysql) use ($data, $detalles) {
            // Validar datos obligatorios
            $camposObligatorios = [
                'idcliente', 'fecha_venta', 'idmoneda_general', 'subtotal_general',
                'descuento_porcentaje_general', 'monto_descuento_general', 'total_general', 'estatus'
            ];
            foreach ($camposObligatorios as $campo) {
                if (!isset($data[$campo])) {
                    throw new Exception("Falta el campo obligatorio: $campo");
                }
            }

            // Generar número de venta
            $nro_venta = $this->generarNumeroVenta();

            // Insertar venta
            $sqlVenta = "INSERT INTO venta 
                (nro_venta, idcliente, fecha_venta, idmoneda_general, subtotal_general, descuento_porcentaje_general, monto_descuento_general, estatus, total_general, observaciones, fecha_creacion, ultima_modificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $paramsVenta = [
                $nro_venta,
                $data['idcliente'],
                $data['fecha_venta'],
                $data['idmoneda_general'],
                $data['subtotal_general'],
                $data['descuento_porcentaje_general'],
                $data['monto_descuento_general'],
                $data['estatus'],
                $data['total_general'],
                $data['observaciones'] ?? ''
            ];
            $idventa = $this->insert($sqlVenta, $paramsVenta);
            if (!$idventa) throw new Exception("No se pudo crear la venta.");

            // Insertar detalles (ajusta los campos según tu tabla)
            $sqlDetalle = "INSERT INTO detalle_venta 
                (id_venta, idproducto, descripcion_temporal_producto, cantidad, precio_unitario_venta, id_moneda_detalle, subtotal_general, peso_vehiculo, peso_bruto, peso_neto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            foreach ($detalles as $detalle) {
                $paramsDetalle = [
                    $idventa,
                    $detalle['idproducto'],
                    $detalle['descripcion_temporal_producto'] ?? '',
                    $detalle['cantidad'],
                    $detalle['precio_unitario_venta'],
                    $detalle['id_moneda_detalle'],
                    $detalle['subtotal_general'] ?? 0,
                    $detalle['peso_vehiculo'] ?? 0,
                    $detalle['peso_bruto'] ?? 0,
                    $detalle['peso_neto'] ?? 0
                ];
                $this->insert($sqlDetalle, $paramsDetalle);
            }

            return ['success' => true, 'message' => 'Venta creada exitosamente', 'idventa' => $idventa];
        });
    }
   public function obtenerTodasLasVentasConCliente()
    {
        try {
            $sql = "SELECT v.idventa,
                           CONCAT('V-', LPAD(v.idventa, 6, '0')) as nro_venta, -- o v.nro_venta si ya está formateado en la tabla
                           CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                           DATE_FORMAT(v.fecha_creacion, '%d/%m/%Y') as fecha_venta,
                           FORMAT(v.total_general, 2) as total_formateado, -- total_general es el nombre de la columna
                           v.estatus
                    FROM venta v
                    INNER JOIN cliente c ON v.idcliente = c.idcliente
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
                    FROM venta v
                    INNER JOIN cliente c ON v.idcliente = c.idcliente
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
                    FROM detalle_venta dv
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
            $venta = $this->search("SELECT COUNT(*) as count FROM venta WHERE idventa = ?", [$idventa]);
            if ($venta['count'] == 0) {
                throw new Exception("La venta especificada no existe.");
            }
            
            // En lugar de eliminar físicamente, cambiar el estatus
            $sqlUpdate = "UPDATE venta SET estatus = 'Inactivo', fecha_modificacion = NOW() WHERE idventa = ?";
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
                    FROM cliente
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
