<?php
namespace App\Models;

use App\Core\Conexion;
use App\Models\MovimientosModel;
use App\Helpers\NotificacionHelper;
use PDO;
use PDOException;
use Exception;

class VentasModel 
{
    // Propiedades privadas
    private $query;
    private $array;
    private $data;
    private $result;
    private $ventaId;
    private $message;
    private $status;
    private $idventa;
    private $idcliente;
    private $fecha_venta;
    private $total_venta;
    private $estatus;
    const SUPER_USUARIO_ROL_ID = 1;

    public function __construct()
    {
        
    }

    // Getters y Setters
    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getArray()
    {
        return $this->array ?? [];
    }

    public function setArray(array $array)
    {
        $this->array = $array;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getVentaId()
    {
        return $this->ventaId;
    }

    public function setVentaId($ventaId)
    {
        $this->ventaId = $ventaId;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

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

   private function esSuperUsuario(int $idusuario){
    $conexion = new Conexion();
    $conexion->connect();
    $dbSeguridad = $conexion->get_conectSeguridad();
    
    try {
        $this->setQuery("SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'ACTIVO'");
        $this->setArray([$idusuario]);
        
        $stmt = $dbSeguridad->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $rolUsuario = intval($usuario['idrol']);
            return $rolUsuario === self::SUPER_USUARIO_ROL_ID;
        }
        return false;
    } catch (Exception $e) {
        error_log("VentasModel::esSuperUsuario - Error: " . $e->getMessage());
        return false;
    } finally {
        $conexion->disconnect();
    }
}

private function esUsuarioActualSuperUsuario(int $idUsuarioSesion){
    return $this->esSuperUsuario($idUsuarioSesion);
}


    private function search(string $query, array $params = [])
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $result = false;

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("VentasModel::search - Error: " . $e->getMessage());
        } finally {
            $conexion->disconnect();
        }

        return $result;
    }

    private function ejecutarBusquedaTodasVentas(int $idUsuarioSesion = 0){
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    
    try {
        $esSuperUsuarioActual = $this->esUsuarioActualSuperUsuario($idUsuarioSesion);
        
        $whereClause = "";
        if (!$esSuperUsuarioActual) {
            $whereClause = " WHERE v.estatus NOT IN ('Inactivo', 'ANULADA')";
        }
        
        $this->setQuery("SELECT
            v.idventa,
            v.nro_venta,
            v.fecha_venta,
            CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente_nombre,
            v.total_general,
            v.estatus,
            v.observaciones,
            v.fecha_creacion,
            v.ultima_modificacion
            FROM venta v
            LEFT JOIN cliente c ON v.idcliente = c.idcliente" . $whereClause . "
            ORDER BY v.fecha_creacion DESC");
        
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute();
        $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        return $this->getResult();
        
    } catch (PDOException $e) {
        error_log("VentasModel::ejecutarBusquedaTodasVentas - Error: " . $e->getMessage());
        return [];
    } finally {
        $conexion->disconnect();
    }
}

    private function ejecutarInsercionVenta(array $data, array $detalles, array $datosClienteNuevo = null)
{
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();

    try {
        $db->beginTransaction();

        $idCliente = $data['idcliente'];

        // Crear cliente nuevo si es necesario
        if (!$idCliente && $datosClienteNuevo) {
            $idCliente = $this->crearClienteNuevo($db, $datosClienteNuevo);
            if (!$idCliente) {
                throw new Exception("No se pudo crear el cliente nuevo");
            }
        }

        // Validar que se tenga un cliente válido
        if (!$idCliente) {
            throw new Exception("Cliente no existe");
        }

        $this->setQuery("SELECT estatus FROM cliente WHERE idcliente = ?");
        $this->setArray([$idCliente]);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente || strtolower($cliente['estatus']) !== 'activo') {
            throw new Exception($cliente ? "Cliente inactivo" : "Cliente no existe");
        }

        $this->setQuery("SELECT COUNT(*) as count FROM monedas WHERE idmoneda = ?");
        $this->setArray([$data['idmoneda_general']]);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Moneda no existe");
        }

        if (floatval($data['monto_descuento_general']) > floatval($data['subtotal_general'])) {
            throw new Exception("Descuento mayor al subtotal");
        }

        // Generar número de venta
        $nro_venta = $this->generarNumeroVenta();
        if (!$nro_venta) {
            throw new Exception('No se pudo generar el número de venta');
        }

        // Insertar venta
        $this->setQuery(
            "INSERT INTO venta 
            (nro_venta, idcliente, fecha_venta, idmoneda, subtotal_general, descuento_porcentaje_general, 
             monto_descuento_general, estatus, total_general, balance, observaciones, tasa, fecha_creacion, ultima_modificacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $this->setArray([
            $nro_venta,
            $idCliente,
            $data['fecha_venta'],
            $data['idmoneda_general'],
            $data['subtotal_general'],
            $data['descuento_porcentaje_general'],
            $data['monto_descuento_general'],
            $data['estatus'],
            $data['total_general'],
            $data['total_general'], // balance inicial igual al total
            $data['observaciones'] ?? '',
            $data['tasa_usada'] ?? 1
        ]);

        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $idventa = $db->lastInsertId();

        if (!$idventa) {
            throw new Exception("No se pudo crear la venta");
        }

        // Insertar detalles
        $this->insertarDetallesVenta($db, $idventa, $detalles, $data['idmoneda_general']);

        // *** REGISTRAR MOVIMIENTOS DE INVENTARIO ***
        $this->registrarMovimientosInventario($db, $idventa, $detalles);

        $db->commit();

        $this->setStatus(true);
        $this->setMessage('Venta registrada exitosamente');
        $this->setVentaId($idventa);

        return [
            'success' => true,
            'message' => $this->getMessage(),
            'idventa' => $this->getVentaId(),
            'idcliente' => $idCliente,
            'nro_venta' => $nro_venta
        ];
    } catch (Exception $e) {
        $db->rollBack();
        error_log("VentasModel::ejecutarInsercionVenta - Error: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error al registrar venta: ' . $e->getMessage()
        ];
    } finally {
        $conexion->disconnect();
    }
}


    private function ejecutarBusquedaVentaPorId(int $idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT v.idventa, v.nro_venta, v.fecha_venta, v.idcliente, v.idmoneda, v.subtotal_general, 
                        v.descuento_porcentaje_general, v.monto_descuento_general, v.estatus, v.total_general, v.balance, v.observaciones,
                        v.tasa as tasa_usada,
                        CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente_nombre,
                        c.nombre as cliente_nombre,
                        c.apellido as cliente_apellido,
                        c.cedula as cliente_cedula,
                        m.codigo_moneda, m.nombre_moneda
                 FROM venta v
                 LEFT JOIN cliente c ON v.idcliente = c.idcliente
                 LEFT JOIN monedas m ON v.idmoneda = m.idmoneda
                 WHERE v.idventa = ?"
            );

            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaVentaPorId - Error: " . $e->getMessage());
            $this->setResult(false);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    private function ejecutarBusquedaDetalleVenta(int $idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT dv.*, p.nombre as nombre_producto, c.nombre as nombre_categoria
                 FROM detalle_venta dv
                 LEFT JOIN producto p ON dv.idproducto = p.idproducto
                 LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                 WHERE dv.idventa = ?
                 ORDER BY dv.iddetalle_venta"
            );

            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaDetalleVenta - Error: " . $e->getMessage());
            $this->setResult([]);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    private function ejecutarEliminacionVenta(int $idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que la venta existe
            $this->setQuery("SELECT COUNT(*) as count FROM venta WHERE idventa = ?");
            $this->setArray([$idventa]);
            $stmtCheck = $db->prepare($this->getQuery());
            $stmtCheck->execute($this->getArray());
            $venta = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($venta['count'] == 0) {
                throw new Exception("La venta especificada no existe.");
            }

            $this->setQuery("UPDATE venta SET estatus = 'Inactivo', ultima_modificacion = NOW() WHERE idventa = ?");
            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;

            if ($resultado) {
                // Registrar movimientos de devolución al eliminar la venta
                error_log("VentasModel::ejecutarEliminacionVenta - Iniciando registro de movimientos de devolución para venta ID: $idventa");
                $this->registrarMovimientosDevolucion($db, $idventa);
                error_log("VentasModel::ejecutarEliminacionVenta - Movimientos de devolución registrados exitosamente para venta ID: $idventa");
            }

            $db->commit();
            error_log("VentasModel::ejecutarEliminacionVenta - Venta ID: $idventa eliminada exitosamente");
        } catch (Exception $e) {
            $db->rollBack();
            error_log("VentasModel::ejecutarEliminacionVenta - Error al eliminar venta ID: $idventa - " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaClientes(string $criterio)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idcliente as id, nombre, apellido, cedula
                 FROM cliente
                 WHERE estatus = 'Activo' 
                 AND (nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ?)
                 ORDER BY nombre, apellido
                 LIMIT 20"
            );

            $parametroBusqueda = '%' . $criterio . '%';
            $this->setArray([$parametroBusqueda, $parametroBusqueda, $parametroBusqueda]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaClientes - Error: " . $e->getMessage());
            $this->setResult([]);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    private function ejecutarBusquedaProductosParaFormulario()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT p.idproducto, 
                        p.nombre as nombre_producto,
                        p.precio as precio_unitario,
                        p.moneda as codigo_moneda_producto,
                        c.nombre as nombre_categoria,
                        m.idmoneda,
                        m.codigo_moneda,
                        m.nombre_moneda,
                        m.valor as tasa_moneda,
                        htbc.tasa_a_bs as tasa_bcv_actual
                 FROM producto p
                 LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                 LEFT JOIN monedas m ON p.moneda = m.codigo_moneda
                 LEFT JOIN (
                     SELECT codigo_moneda, tasa_a_bs,
                            ROW_NUMBER() OVER (PARTITION BY codigo_moneda ORDER BY fecha_publicacion_bcv DESC) as rn
                     FROM historial_tasas_bcv
                 ) htbc ON p.moneda = htbc.codigo_moneda AND htbc.rn = 1
                 WHERE p.estatus = 'ACTIVO' 
                 AND (p.nombre LIKE '%Paca%' OR c.nombre LIKE '%paca%')
                 ORDER BY p.nombre"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaProductosParaFormulario - Error: " . $e->getMessage());
            $this->setResult([]);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    private function ejecutarBusquedaMonedasActivas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idmoneda, codigo_moneda, nombre_moneda, valor
                 FROM monedas 
                 WHERE estatus = 'Activo'
                 ORDER BY codigo_moneda"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaMonedasActivas - Error: " . $e->getMessage());
            $this->setResult([]);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    // Métodos auxiliares privados
    private function crearClienteNuevo($db, array $datosCliente)
    {
        $sqlCliente = "INSERT INTO cliente (nombre, apellido, cedula, telefono_principal, direccion, estatus, fecha_creacion, ultima_modificacion)
                       VALUES (?, ?, ?, ?, ?, 'Activo', NOW(), NOW())";

        $paramsCliente = [
            $datosCliente['nombre'],
            $datosCliente['apellido'] ?? '',
            $datosCliente['cedula'],
            $datosCliente['telefono_principal'],
            $datosCliente['direccion']
        ];

        $stmtCliente = $db->prepare($sqlCliente);
        if ($stmtCliente->execute($paramsCliente)) {
            return $db->lastInsertId();
        }
        return false;
    }

    private function insertarDetallesVenta($db, $idventa, array $detalles, $idmoneda_general)
    {
        $sqlDetalle = "INSERT INTO detalle_venta 
                       (idventa, idproducto, cantidad, precio_unitario_venta, 
                        idmoneda, subtotal_general, peso_vehiculo, peso_bruto, peso_neto)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        foreach ($detalles as $index => $detalle) {
            // Validar datos del detalle
            if (!isset($detalle['idproducto']) || empty($detalle['idproducto'])) {
                error_log("Detalle inválido en índice $index: " . print_r($detalle, true));
                throw new Exception("El producto en el detalle #" . ($index + 1) . " no tiene ID válido");
            }

            $idproducto = intval($detalle['idproducto']);
            if ($idproducto <= 0) {
                error_log("ID producto inválido en índice $index: " . $detalle['idproducto']);
                throw new Exception("El producto en el detalle #" . ($index + 1) . " tiene un ID inválido: " . $detalle['idproducto']);
            }

            // Validar que el producto existe y está activo
            $producto = $this->search("SELECT nombre, estatus FROM producto WHERE idproducto = ?", [$idproducto]);
            if (!$producto) {
                throw new Exception("El producto con ID " . $idproducto . " no existe en el detalle #" . ($index + 1));
            }
            if (strtolower($producto['estatus']) !== 'activo') {
                throw new Exception("El producto '{$producto['nombre']}' no está activo (detalle #" . ($index + 1) . ")");
            }

            // Validar otros campos requeridos
            if (!isset($detalle['cantidad']) || floatval($detalle['cantidad']) <= 0) {
                throw new Exception("La cantidad debe ser mayor a 0 en el detalle #" . ($index + 1));
            }

            if (!isset($detalle['precio_unitario_venta']) || floatval($detalle['precio_unitario_venta']) <= 0) {
                throw new Exception("El precio unitario debe ser válido en el detalle #" . ($index + 1));
            }

            $paramsDetalle = [
                $idventa,
                $idproducto,
                floatval($detalle['cantidad']),
                floatval($detalle['precio_unitario_venta']),
                $detalle['id_moneda_detalle'] ?? $idmoneda_general,
                floatval($detalle['subtotal_general'] ?? 0),
                floatval($detalle['peso_vehiculo'] ?? 0),
                floatval($detalle['peso_bruto'] ?? 0),
                floatval($detalle['peso_neto'] ?? 0)
            ];

            $stmtDetalle = $db->prepare($sqlDetalle);
            if (!$stmtDetalle->execute($paramsDetalle)) {
                throw new Exception("No se pudo insertar el detalle del producto ID: " . $idproducto . " (detalle #" . ($index + 1) . ")");
            }
        }
    }

    private function generarNumeroVenta()
    {
        try {
            $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(nro_venta, 3) AS UNSIGNED)), 0) + 1 as siguiente_numero 
                    FROM venta 
                    WHERE nro_venta LIKE 'VT%'";
            $result = $this->search($sql);
            return 'VT' . str_pad($result['siguiente_numero'], 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("Error al generar número de venta: " . $e->getMessage());
            return false;
        }
    }

    /**
 * Registra movimientos de inventario usando MovimientosModel
 */
private function registrarMovimientosInventario($db, $idventa, array $detalles) {
    try {
        // Instanciar el modelo de movimientos
        $movimientosModel = new MovimientosModel();
        
        // Obtener tipo de movimiento para ventas
        $this->setQuery("SELECT idtipomovimiento FROM tipo_movimiento WHERE nombre = 'Venta' LIMIT 1");
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute();
        $tipoMovimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tipoMovimiento) {
            $this->setQuery("INSERT INTO tipo_movimiento (nombre, descripcion, estatus, fecha_creacion, fecha_modificacion) VALUES ('Venta', 'Salida por venta', 'activo', NOW(), NOW())");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $idtipomovimiento = $db->lastInsertId();
        } else {
            $idtipomovimiento = $tipoMovimiento['idtipomovimiento'];
        }

        // Registrar movimiento por cada producto vendido
        foreach ($detalles as $detalle) {
            $idproducto = intval($detalle['idproducto']);
            $cantidad = floatval($detalle['cantidad']);
            
            // Obtener stock actual y nombre del producto
            $this->setQuery("SELECT COALESCE(existencia, 0) as stock, nombre FROM producto WHERE idproducto = ?");
            $this->setArray([$idproducto]);
            $stmtStock = $db->prepare($this->getQuery());
            $stmtStock->execute($this->getArray());
            $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);
            $stockAnterior = floatval($producto['stock']);
            $stockResultante = $stockAnterior - $cantidad;
            
            // Validar stock suficiente
            if ($stockResultante < 0) {
                $nombreProducto = $producto['nombre'] ?? 'Producto desconocido';
                throw new Exception("Stock insuficiente para el producto: $nombreProducto");
            }
            
            // Preparar datos para MovimientosModel usando sus setters
            $movimientosModel->setIdproducto($idproducto);
            $movimientosModel->setIdtipomovimiento($idtipomovimiento);
            $movimientosModel->setIdventa($idventa);
            $movimientosModel->setIdcompra(null);
            $movimientosModel->setIdproduccion(null);
            $movimientosModel->setCantidadEntrada(0);
            $movimientosModel->setCantidadSalida($cantidad);
            $movimientosModel->setStockAnterior($stockAnterior);
            $movimientosModel->setStockResultante($stockResultante);
            $movimientosModel->setObservaciones('Salida por venta');
            
            // Generar número de movimiento
            $numeroMovimiento = $this->generarNumeroMovimientoVenta($db);
            
            // Insertar movimiento
            $this->setQuery("
                INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, idcompra, idventa, idproduccion,
                cantidad_entrada, cantidad_salida, stock_anterior, stock_resultante, 
                total, observaciones, fecha_creacion, fecha_modificacion, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'activo')
            ");
            
            $this->setArray([
                $numeroMovimiento,
                $movimientosModel->getIdproducto(),
                $movimientosModel->getIdtipomovimiento(),
                $movimientosModel->getIdcompra(),
                $movimientosModel->getIdventa(),
                $movimientosModel->getIdproduccion(),
                $movimientosModel->getCantidadEntrada(),
                $movimientosModel->getCantidadSalida(),
                $movimientosModel->getStockAnterior(),
                $movimientosModel->getStockResultante(),
                $movimientosModel->getStockResultante(),
                $movimientosModel->getObservaciones()
            ]);
            
            $stmtMovimiento = $db->prepare($this->getQuery());
            $stmtMovimiento->execute($this->getArray());
            
            // Actualizar existencia en producto
            $this->setQuery("UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $this->setArray([$movimientosModel->getStockResultante(), $movimientosModel->getIdproducto()]);
            $stmtUpdate = $db->prepare($this->getQuery());
            $stmtUpdate->execute($this->getArray());
            
            // ⚠️ VERIFICAR STOCK MÍNIMO (Notificación informativa)
            $this->verificarStockMinimo($db, $movimientosModel->getIdproducto(), $movimientosModel->getStockResultante());
        }
        
        return true;
    } catch (Exception $e) {
        error_log("VentasModel::registrarMovimientosInventario - Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Elimina movimientos de existencia asociados a una venta y revierte el stock
 */
private function eliminarMovimientosVenta($db, $idventa) {
    try {
        error_log("VentasModel::eliminarMovimientosVenta - Iniciando para venta ID: $idventa");
        
        // Obtener movimientos asociados a la venta
        $this->setQuery("SELECT idmovimiento, idproducto, cantidad_salida FROM movimientos_existencia WHERE idventa = ? AND estatus = 'activo'");
        $this->setArray([$idventa]);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($movimientos)) {
            foreach ($movimientos as $movimiento) {
                // Revertir el stock del producto
                $this->setQuery("UPDATE producto SET existencia = existencia + ?, ultima_modificacion = NOW() WHERE idproducto = ?");
                $this->setArray([$movimiento['cantidad_salida'], $movimiento['idproducto']]);
                $stmtUpdate = $db->prepare($this->getQuery());
                $stmtUpdate->execute($this->getArray());
                
                // Marcar movimiento como inactivo con observación de anulación
                $this->setQuery("UPDATE movimientos_existencia SET estatus = 'inactivo', observaciones = CONCAT(observaciones, ' - Anulado por actualización de venta'), fecha_modificacion = NOW() WHERE idmovimiento = ?");
                $this->setArray([$movimiento['idmovimiento']]);
                $stmtMov = $db->prepare($this->getQuery());
                $stmtMov->execute($this->getArray());
            }
            
            error_log("VentasModel::eliminarMovimientosVenta - Eliminados " . count($movimientos) . " movimientos para venta ID: $idventa");
        } else {
            error_log("VentasModel::eliminarMovimientosVenta - No se encontraron movimientos para venta ID: $idventa");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("VentasModel::eliminarMovimientosVenta - Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Registra movimientos de devolución de inventario cuando se cancela una venta
 */
private function registrarMovimientosDevolucion($db, $idventa) {
    try {
        error_log("VentasModel::registrarMovimientosDevolucion - Iniciando para venta ID: $idventa");
        // Instanciar el modelo de movimientos
        $movimientosModel = new MovimientosModel();
        
        // Obtener tipo de movimiento para devoluciones
        $this->setQuery("SELECT idtipomovimiento FROM tipo_movimiento WHERE nombre = 'Devolución' LIMIT 1");
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute();
        $tipoMovimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tipoMovimiento) {
            error_log("VentasModel::registrarMovimientosDevolucion - Creando tipo de movimiento 'Devolución'");
            $this->setQuery("INSERT INTO tipo_movimiento (nombre, descripcion, estatus, fecha_creacion, fecha_modificacion) VALUES ('Devolución', 'Entrada por cancelación de venta', 'activo', NOW(), NOW())");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $idtipomovimiento = $db->lastInsertId();
        } else {
            $idtipomovimiento = $tipoMovimiento['idtipomovimiento'];
        }
        error_log("VentasModel::registrarMovimientosDevolucion - ID tipo movimiento: $idtipomovimiento");

        // Obtener detalles de la venta
        $this->setQuery("
            SELECT dv.idproducto, dv.cantidad, p.nombre, COALESCE(p.existencia, 0) as stock_actual
            FROM detalle_venta dv
            LEFT JOIN producto p ON dv.idproducto = p.idproducto
            WHERE dv.idventa = ?
        ");
        $this->setArray([$idventa]);
        $stmtDetalles = $db->prepare($this->getQuery());
        $stmtDetalles->execute($this->getArray());
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
        error_log("VentasModel::registrarMovimientosDevolucion - Detalles obtenidos: " . count($detalles));

        if (empty($detalles)) {
            error_log("VentasModel::registrarMovimientosDevolucion - No se encontraron detalles para la venta ID: $idventa");
            return true; // No hay detalles, pero no es error
        }

        // Registrar movimiento por cada producto devuelto
        foreach ($detalles as $detalle) {
            $idproducto = intval($detalle['idproducto']);
            $cantidad = floatval($detalle['cantidad']);
            $stockAnterior = floatval($detalle['stock_actual']);
            $stockResultante = $stockAnterior + $cantidad;
            
            error_log("VentasModel::registrarMovimientosDevolucion - Procesando producto ID: $idproducto, cantidad: $cantidad");
            
            // Preparar datos para MovimientosModel
            $movimientosModel->setIdproducto($idproducto);
            $movimientosModel->setIdtipomovimiento($idtipomovimiento);
            $movimientosModel->setIdventa($idventa);
            $movimientosModel->setIdcompra(null);
            $movimientosModel->setIdproduccion(null);
            $movimientosModel->setCantidadEntrada($cantidad);
            $movimientosModel->setCantidadSalida(0);
            $movimientosModel->setStockAnterior($stockAnterior);
            $movimientosModel->setStockResultante($stockResultante);
            $movimientosModel->setObservaciones('Entrada por cancelación de venta');
            
            // Generar número de movimiento
            $numeroMovimiento = $this->generarNumeroMovimientoDevolucion($db);
            error_log("VentasModel::registrarMovimientosDevolucion - Número movimiento generado: $numeroMovimiento");
            
            // Insertar movimiento
            $this->setQuery("
                INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, idcompra, idventa, idproduccion,
                cantidad_entrada, cantidad_salida, stock_anterior, stock_resultante, 
                total, observaciones, fecha_creacion, fecha_modificacion, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'activo')
            ");
            
            $this->setArray([
                $numeroMovimiento,
                $movimientosModel->getIdproducto(),
                $movimientosModel->getIdtipomovimiento(),
                $movimientosModel->getIdcompra(),
                $movimientosModel->getIdventa(),
                $movimientosModel->getIdproduccion(),
                $movimientosModel->getCantidadEntrada(),
                $movimientosModel->getCantidadSalida(),
                $movimientosModel->getStockAnterior(),
                $movimientosModel->getStockResultante(),
                $movimientosModel->getStockResultante(),
                $movimientosModel->getObservaciones()
            ]);
            
            $stmtMovimiento = $db->prepare($this->getQuery());
            $stmtMovimiento->execute($this->getArray());
            error_log("VentasModel::registrarMovimientosDevolucion - Movimiento insertado para producto ID: $idproducto");
            
            // Actualizar existencia en producto
            $this->setQuery("UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $this->setArray([$movimientosModel->getStockResultante(), $movimientosModel->getIdproducto()]);
            $stmtUpdate = $db->prepare($this->getQuery());
            $stmtUpdate->execute($this->getArray());
            error_log("VentasModel::registrarMovimientosDevolucion - Stock actualizado para producto ID: $idproducto a " . $movimientosModel->getStockResultante());
        }
        
        error_log("VentasModel::registrarMovimientosDevolucion - Completado exitosamente para venta ID: $idventa");
        return true;
    } catch (Exception $e) {
        error_log("VentasModel::registrarMovimientosDevolucion - Error: " . $e->getMessage());
        throw $e;
    }
}

private function generarNumeroMovimientoVenta($db) {
    $prefijo = 'MOV-VENTA-';
    $fecha = date('Ymd');
    
    try {
        $this->setQuery("
            SELECT numero_movimiento 
            FROM movimientos_existencia 
            WHERE numero_movimiento LIKE ? 
            ORDER BY idmovimiento DESC LIMIT 1
        ");
        $this->setArray([$prefijo . $fecha . '-%']);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultimo) {
            $partes = explode('-', $ultimo['numero_movimiento']);
            $consecutivo = intval(end($partes)) + 1;
        } else {
            $consecutivo = 1;
        }
        
        return $prefijo . $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefijo . $fecha . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

private function generarNumeroMovimientoDevolucion($db) {
    $prefijo = 'MOV-DEVOLUCION-';
    $fecha = date('Ymd');
    
    try {
        $this->setQuery("
            SELECT numero_movimiento 
            FROM movimientos_existencia 
            WHERE numero_movimiento LIKE ? 
            ORDER BY idmovimiento DESC LIMIT 1
        ");
        $this->setArray([$prefijo . $fecha . '-%']);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultimo) {
            $partes = explode('-', $ultimo['numero_movimiento']);
            $consecutivo = intval(end($partes)) + 1;
        } else {
            $consecutivo = 1;
        }
        
        return $prefijo . $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefijo . $fecha . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}





    public function getVentasDatatable(int $idUsuarioSesion = 0){
        return $this->ejecutarBusquedaTodasVentas($idUsuarioSesion);
    }


    public function insertVenta(array $data, array $detalles, array $datosClienteNuevo = null)
    {
        $this->setData($data);
        return $this->ejecutarInsercionVenta($this->getData(), $detalles, $datosClienteNuevo);
    }

    public function updateVenta(int $idventa, array $data)
    {
        $this->setVentaId($idventa);
        $this->setData($data);
        return $this->ejecutarActualizacionVenta($this->getVentaId(), $this->getData());
    }

    private function ejecutarActualizacionVenta(int $idventa, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            error_log("VentasModel::updateVenta - Iniciando actualización de venta ID: $idventa");
            error_log("VentasModel::updateVenta - Datos recibidos: " . print_r($data, true));
            
            $db->beginTransaction();

            // Verificar que la venta existe
            $ventaExistente = $this->search("SELECT * FROM venta WHERE idventa = ?", [$idventa]);
            if (!$ventaExistente) {
                throw new Exception("La venta especificada no existe");
            }

            // Si hay datos de cliente nuevo, crear el cliente
            $idCliente = $data['idcliente'] ?? $ventaExistente['idcliente'];
            if (!$idCliente && isset($data['datosClienteNuevo'])) {
                $idCliente = $this->crearClienteNuevo($db, $data['datosClienteNuevo']);
                if (!$idCliente) {
                    throw new Exception("No se pudo crear el cliente nuevo");
                }
            }

            // Validar que el cliente existe
            if ($idCliente) {
                $clienteExiste = $this->search("SELECT COUNT(*) as count FROM cliente WHERE idcliente = ?", [$idCliente]);
                if ($clienteExiste['count'] == 0) {
                    throw new Exception("El cliente especificado no existe");
                }
            }

            // Actualizar la venta
            $this->setQuery(
                "UPDATE venta SET 
                    idcliente = ?, 
                    fecha_venta = ?, 
                    idmoneda = ?, 
                    subtotal_general = ?, 
                    descuento_porcentaje_general = ?, 
                    monto_descuento_general = ?, 
                    estatus = ?, 
                    total_general = ?, 
                    balance = ?, 
                    observaciones = ?, 
                    tasa = ?, 
                    ultima_modificacion = NOW()
                WHERE idventa = ?"
            );

            $this->setArray([
                $idCliente ?: $ventaExistente['idcliente'],
                $data['fecha_venta'] ?? $ventaExistente['fecha_venta'],
                $data['idmoneda_general'] ?? $ventaExistente['idmoneda'],
                $data['subtotal_general'] ?? $ventaExistente['subtotal_general'],
                $data['descuento_porcentaje_general'] ?? $ventaExistente['descuento_porcentaje_general'],
                $data['monto_descuento_general'] ?? $ventaExistente['monto_descuento_general'],
                $data['estatus'] ?? $ventaExistente['estatus'],
                $data['total_general'] ?? $ventaExistente['total_general'],
                $data['total_general'] ?? $ventaExistente['total_general'], // balance igual al total
                $data['observaciones'] ?? $ventaExistente['observaciones'],
                $data['tasa_usada'] ?? $ventaExistente['tasa'],
                $idventa
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // Si hay detalles nuevos, actualizar los detalles
            if (isset($data['detalles']) && is_array($data['detalles'])) {
                error_log("VentasModel::updateVenta - Procesando detalles para venta ID: $idventa");
                error_log("VentasModel::updateVenta - Número de detalles recibidos: " . count($data['detalles']));
                error_log("VentasModel::updateVenta - Detalles recibidos: " . print_r($data['detalles'], true));

                // Eliminar detalles existentes
                $sqlEliminarDetalles = "DELETE FROM detalle_venta WHERE idventa = ?";
                $stmtEliminar = $db->prepare($sqlEliminarDetalles);
                $stmtEliminar->execute([$idventa]);

                // Eliminar movimientos de existencia asociados a la venta
                $this->eliminarMovimientosVenta($db, $idventa);

                // Insertar nuevos detalles solo si hay detalles válidos
                if (!empty($data['detalles'])) {
                    // Validar que los detalles tienen estructura correcta
                    foreach ($data['detalles'] as $index => $detalle) {
                        if (!is_array($detalle)) {
                            throw new Exception("El detalle #" . ($index + 1) . " no tiene formato válido");
                        }
                    }
                    
                    $this->insertarDetallesVenta($db, $idventa, $data['detalles'], $data['idmoneda_general'] ?? $ventaExistente['idmoneda']);
                    
                    // Registrar nuevos movimientos de inventario
                    $this->registrarMovimientosInventario($db, $idventa, $data['detalles']);
                }
            } else {
                error_log("VentasModel::updateVenta - No se recibieron detalles para actualizar (mantener existentes)");
            }

            $db->commit();

            $this->setStatus(true);
            $this->setMessage('Venta actualizada exitosamente');

            return [
                'success' => true,
                'message' => $this->getMessage(),
                'idventa' => $idventa
            ];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("VentasModel::ejecutarActualizacionVenta - Error: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al actualizar venta: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }


    public function obtenerVentaPorId(int $idventa)
    {
        $this->setVentaId($idventa);
        return $this->ejecutarBusquedaVentaPorId($this->getVentaId());
    }

    public function obtenerDetalleVenta(int $idventa)
    {
        $this->setVentaId($idventa);
        return $this->ejecutarBusquedaDetalleVenta($this->getVentaId());
    }

    /**
     * Obtiene el detalle completo de una venta con información de productos
     */
    public function obtenerDetalleVentaCompleto($idventa)
    {
        return $this->ejecutarObtenerDetalleVentaCompleto($idventa);
    }

    private function ejecutarObtenerDetalleVentaCompleto($idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Consulta simplificada sin el campo codigo que no existe
            $this->setQuery(
                "SELECT 
                dv.iddetalle_venta,
                dv.idventa,
                dv.idproducto,
                dv.cantidad,
                dv.precio_unitario_venta,
                dv.subtotal_general,
                p.nombre as nombre_producto,
                c.nombre as nombre_categoria
             FROM detalle_venta dv
             LEFT JOIN producto p ON dv.idproducto = p.idproducto
             LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
             WHERE dv.idventa = ?
             ORDER BY dv.iddetalle_venta"
            );

            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::obtenerDetalleVentaCompleto - Error: " . $e->getMessage());
            $this->setResult([]);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    public function eliminarVenta(int $idventa)
    {
        $this->setVentaId($idventa);
        $resultado = $this->ejecutarEliminacionVenta($this->getVentaId());

        if ($resultado) {
            return ['success' => true, 'message' => 'Venta desactivada exitosamente'];
        } else {
            return ['success' => false, 'message' => 'No se pudo desactivar la venta'];
        }
    }

    public function buscarClientes(string $criterio)
    {
        return $this->ejecutarBusquedaClientes($criterio);
    }

    public function getListaProductosParaFormulario()
    {
        return $this->ejecutarBusquedaProductosParaFormulario();
    }

    public function getMonedasActivas()
    {
        return $this->ejecutarBusquedaMonedasActivas();
    }

    public function obtenerProductos()
    {
        return $this->ejecutarBusquedaProductosParaFormulario();
    }

    public function verificarEsSuperUsuario(int $idusuario){
    return $this->esSuperUsuario($idusuario);
}

    public function validarDatosCliente($datos)
    {
        $errores = [];

        if (empty($datos['nombre']) || strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 50) {
            $errores[] = "El nombre debe tener entre 2 y 50 caracteres";
        }

        if (empty($datos['cedula'])) {
            $errores[] = "La cédula es obligatoria";
        } elseif (!preg_match('/^\d{7,8}$/', $datos['cedula'])) {
            $errores[] = "La cédula debe tener entre 7 y 8 dígitos";
        }

        if (empty($datos['telefono_principal'])) {
            $errores[] = "El teléfono es obligatorio";
        } elseif (!preg_match('/^\d{11}$/', $datos['telefono_principal'])) {
            $errores[] = "El teléfono debe tener 11 dígitos";
        }

        if (empty($datos['direccion'])) {
            $errores[] = "La dirección es obligatoria";
        } elseif (strlen($datos['direccion']) < 5 || strlen($datos['direccion']) > 200) {
            $errores[] = "La dirección debe tener entre 5 y 200 caracteres";
        }

        return $errores;
    }

    public function getTasaPorCodigoYFecha($codigo, $fecha)
    {
        return $this->ejecutarGetTasaPorCodigoYFecha($codigo, $fecha);
    }

    private function ejecutarGetTasaPorCodigoYFecha($codigo, $fecha)
    {
        try {
            $sql = "SELECT tasa_a_bs FROM historial_tasas_bcv WHERE codigo_moneda = ? AND DATE(fecha_publicacion_bcv) <= DATE(?) ORDER BY fecha_publicacion_bcv DESC LIMIT 1";
            $result = $this->search($sql, [$codigo, $fecha]);
            return $result['tasa_a_bs'] ?? 1;
        } catch (Exception $e) {
            error_log("Error al obtener tasa: " . $e->getMessage());
            return 1;
        }
    }

    public function obtenerEstadoVenta($idventa)
    {
        return $this->ejecutarObtenerEstadoVenta($idventa);
    }

    private function ejecutarObtenerEstadoVenta($idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT estatus FROM venta WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['estatus'] : null;
        } catch (PDOException $e) {
            error_log("VentasModel::obtenerEstadoVenta - Error: " . $e->getMessage());
            return null;
        } finally {
            $conexion->disconnect();
        }
    }

    public function cambiarEstadoVenta(int $idventa, string $nuevoEstado)
    {
        return $this->ejecutarCambioEstadoVenta($idventa, $nuevoEstado);
    }

    private function ejecutarCambioEstadoVenta(int $idventa, string $nuevoEstado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $estadosValidos = ['BORRADOR', 'POR_PAGAR', 'PAGADA', 'ANULADA'];

            if (!in_array($nuevoEstado, $estadosValidos)) {
                return [
                    'status' => false,
                    'message' => 'Estado no válido.'
                ];
            }

            $this->setQuery("SELECT estatus FROM venta WHERE idventa = ?");
            $stmtGet = $db->prepare($this->getQuery());
            $stmtGet->execute([$idventa]);
            $venta = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return [
                    'status' => false,
                    'message' => 'Venta no encontrada.'
                ];
            }

            $estadoActual = $venta['estatus'];

            if (!$this->validarTransicionEstadoVenta($estadoActual, $nuevoEstado)) {
                return [
                    'status' => false,
                    'message' => 'Transición de estado no válida.'
                ];
            }

            // Validación especial para marcar como PAGADA
            if ($nuevoEstado === 'PAGADA') {
                $validacionPago = $this->validarPagosCompletosVenta($db, $idventa);
                if (!$validacionPago['valido']) {
                    return [
                        'status' => false,
                        'message' => $validacionPago['mensaje']
                    ];
                }
            }

            $this->setQuery("UPDATE venta SET estatus = ?, ultima_modificacion = NOW() WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$nuevoEstado, $idventa]);

            return [
                'status' => true,
                'message' => 'Estado de venta actualizado exitosamente.'
            ];
        } catch (PDOException $e) {
            error_log("VentasModel::ejecutarCambioEstadoVenta - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al cambiar estado: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function validarTransicionEstadoVenta($estadoActual, $nuevoEstado): bool
    {
        $transicionesValidas = [
            'BORRADOR' => ['POR_PAGAR'],
            'POR_PAGAR' => ['PAGADA', 'BORRADOR'],
            'PAGADA' => [], // Una vez pagada, no se puede cambiar
            'ANULADA' => [] // Una vez anulada, no se puede cambiar
        ];

        return isset($transicionesValidas[$estadoActual]) &&
            in_array($nuevoEstado, $transicionesValidas[$estadoActual]);
    }

    /**
     * Obtiene la tasa de cambio actual de una moneda específica
     */
    public function obtenerTasaActualMoneda($codigoMoneda)
    {
        return $this->ejecutarObtenerTasaActualMoneda($codigoMoneda);
    }

    public function getVentaDetalle($idventa)
    {
        return $this->ejecutarGetVentaDetalle($idventa);
    }

    private function ejecutarGetVentaDetalle($idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener información de la venta
            $this->setQuery(
                "SELECT v.idventa, v.nro_venta, v.fecha_venta, v.total_general, v.estatus,
                        CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente_nombre,
                        m.codigo_moneda, m.nombre_moneda
                 FROM venta v
                 LEFT JOIN cliente c ON v.idcliente = c.idcliente
                 LEFT JOIN monedas m ON v.idmoneda = m.idmoneda
                 WHERE v.idventa = ?"
            );

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return ['status' => false, 'message' => 'Venta no encontrada'];
            }

            // Obtener detalles de la venta
            $detalles = $this->obtenerDetalleVentaCompleto($idventa);
            
            // Debug: Verificar qué devuelve obtenerDetalleVentaCompleto
            error_log("DEBUG obtenerDetalleVentaCompleto - ID: $idventa");
            error_log("DEBUG obtenerDetalleVentaCompleto - Resultado: " . print_r($detalles, true));
            error_log("DEBUG obtenerDetalleVentaCompleto - Count: " . count($detalles));

            return [
                'status' => true,
                'venta' => $venta,
                'detalles' => $detalles
            ];

        } catch (Exception $e) {
            error_log("VentasModel::ejecutarGetVentaDetalle - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener detalle de venta'];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarObtenerTasaActualMoneda($codigoMoneda)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT htbc.tasa_a_bs, htbc.fecha_publicacion_bcv, m.nombre_moneda
                 FROM historial_tasas_bcv htbc
                 LEFT JOIN monedas m ON htbc.codigo_moneda = m.codigo_moneda
                 WHERE htbc.codigo_moneda = ?
                 ORDER BY htbc.fecha_publicacion_bcv DESC
                 LIMIT 1"
            );

            $this->setArray([$codigoMoneda]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarObtenerTasaActualMoneda - Error: " . $e->getMessage());
            $this->setResult(false);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }

    /**
     * Valida que una venta tenga pagos conciliados que cubran el total
     */
    private function validarPagosCompletosVenta($db, $idventa)
    {
        try {
            // Obtener información de la venta
            $this->setQuery("SELECT total_general FROM venta WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return [
                    'valido' => false,
                    'mensaje' => 'Venta no encontrada.'
                ];
            }

            $totalVenta = $venta['total_general'];

            // Obtener el total de pagos conciliados
            $this->setQuery("SELECT COALESCE(SUM(monto), 0) as total_pagado FROM pagos WHERE idventa = ? AND estatus = 'conciliado'");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $resultadoPagos = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPagado = $resultadoPagos['total_pagado'] ?? 0;

            // Verificar si el total pagado cubre el total de la venta
            $diferencia = abs($totalVenta - $totalPagado);
            
            if ($diferencia > 0.01) // Tolerancia para problemas de redondeo
            {
                return [
                    'valido' => false,
                    'mensaje' => "No se puede marcar como pagada. Total venta: $totalVenta, Total pagado (conciliado): $totalPagado. Faltan: " . ($totalVenta - $totalPagado)
                ];
            }

            return [
                'valido' => true,
                'mensaje' => 'Pagos completos validados.'
            ];

        } catch (Exception $e) {
            error_log("VentasModel::validarPagosCompletosVenta - Error: " . $e->getMessage());
            return [
                'valido' => false,
                'mensaje' => 'Error al validar pagos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si el producto llegó a stock mínimo (Notificación informativa)
     */
    private function verificarStockMinimo($db, $idproducto, $existenciaActual) {
        try {
            // Obtener datos del producto
            $this->setQuery("SELECT idproducto, nombre, existencia, stock_minimo FROM producto WHERE idproducto = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idproducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto || $producto['stock_minimo'] <= 0) {
                return; // No tiene stock_minimo configurado
            }
            
            // Verificar si llegó o está por debajo del stock mínimo
            if ($existenciaActual <= $producto['stock_minimo']) {
                error_log("⚠️ Stock mínimo alcanzado: {$producto['nombre']} ({$existenciaActual} <= {$producto['stock_minimo']})");
                
                // Enviar notificación (BD + WebSocket)
                $helper = new NotificacionHelper();
                $helper->enviarNotificacionStockMinimo($producto);
            }
            
        } catch (\Exception $e) {
            error_log("❌ Error al verificar stock mínimo: " . $e->getMessage());
        }
    }
}
