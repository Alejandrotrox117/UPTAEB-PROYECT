<?php
require_once("app/core/conexion.php");

class VentasModel // Eliminamos "extends Mysql"
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

    public function __construct()
    {
        // parent::__construct(); // Ya no es necesario
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

    // Método search implementado localmente para eliminar la dependencia de Mysql
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

    // Métodos privados encapsulados
    private function ejecutarBusquedaTodasVentas(array $params)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        // Parámetros de DataTables
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $searchValue = $params['search']['value'] ?? '';
        $orderColumnIndex = $params['order'][0]['column'] ?? 0;
        $orderColumnName = $params['columns'][$orderColumnIndex]['data'] ?? 'v.fecha_venta';
        $orderDir = $params['order'][0]['dir'] ?? 'desc';

      
        $columnasPermitidas = ['nro_venta', 'fecha_venta', 'cliente_nombre', 'total_general', 'estatus_formato'];
        if (!in_array($orderColumnName, $columnasPermitidas)) {
            $orderColumnName = 'v.fecha_venta'; // Columna segura por defecto
        }

        try {
            $bindings = [];
            $whereClause = " WHERE v.estatus NOT IN ('inactivo', 'eliminado')";

            // Lógica de búsqueda
            if (!empty($searchValue)) {
                $whereClause .= " AND (v.nro_venta LIKE ? OR CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) LIKE ? OR v.estatus LIKE ?)";
                $searchTerm = "%{$searchValue}%";
                array_push($bindings, $searchTerm, $searchTerm, $searchTerm);
            }

            // Contar total de registros (sin filtrar)
            $totalRecords = $db->query("SELECT COUNT(v.idventa) FROM venta v WHERE v.estatus NOT IN ('inactivo', 'eliminado')")->fetchColumn();

            // Contar total de registros (con filtro de búsqueda)
            $queryFiltered = "SELECT COUNT(v.idventa) FROM venta v LEFT JOIN cliente c ON v.idcliente = c.idcliente" . $whereClause;
            $stmtFiltered = $db->prepare($queryFiltered);
            $stmtFiltered->execute($bindings);
            $totalFiltered = $stmtFiltered->fetchColumn();

            // Consulta principal con paginación y orden
            $query = "SELECT 
                        v.idventa, v.nro_venta, v.fecha_venta,
                        CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente_nombre,
                        v.total_general, v.estatus,
                        DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_formato,
                        CASE 
                            WHEN v.estatus = 'BORRADOR' THEN 'Borrador'
                            WHEN v.estatus = 'POR_PAGAR' THEN 'Por Pagar'
                            WHEN v.estatus = 'PAGADA' THEN 'Pagada'
                            WHEN v.estatus = 'ANULADA' THEN 'Anulada'
                            ELSE v.estatus
                        END as estatus_formato
                      FROM venta v
                      LEFT JOIN cliente c ON v.idcliente = c.idcliente
                      $whereClause
                      ORDER BY $orderColumnName $orderDir
                      LIMIT " . intval($start) . ", " . intval($length);
            
            $stmt = $db->prepare($query);
            $stmt->execute($bindings);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultado = [
                "draw" => intval($params['draw'] ?? 0),
                "recordsTotal" => intval($totalRecords),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            ];

        } catch (Exception $e) {
            error_log("VentasModel::ejecutarBusquedaTodasVentas - Error: " . $e->getMessage());
            $resultado = [
                "draw" => intval($params['draw'] ?? 0),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
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

            // Validar que el cliente existe
            $clienteExiste = $this->search("SELECT COUNT(*) as count FROM cliente WHERE idcliente = ?", [$idCliente]);
            if ($clienteExiste['count'] == 0) {
                throw new Exception("El cliente especificado no existe");
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
                 monto_descuento_general, estatus, total_general, observaciones, tasa, fecha_creacion, ultima_modificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
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
                "SELECT v.idventa, v.nro_venta, v.fecha_venta, v.idmoneda, v.subtotal_general, 
                        v.descuento_porcentaje_general, v.monto_descuento_general, v.estatus, v.total_general, v.observaciones,
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
            // Verificar que la venta existe
            $venta = $this->search("SELECT COUNT(*) as count FROM venta WHERE idventa = ?", [$idventa]);
            if ($venta['count'] == 0) {
                throw new Exception("La venta especificada no existe.");
            }

            $this->setQuery("UPDATE venta SET estatus = 'Inactivo', ultima_modificacion = NOW() WHERE idventa = ?");
            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("VentasModel::ejecutarEliminacionVenta - Error: " . $e->getMessage());
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
                 AND c.nombre = 'Pacas'
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

        foreach ($detalles as $detalle) {
            // Validar que el producto existe
            $productoExiste = $this->search("SELECT COUNT(*) as count FROM producto WHERE idproducto = ?", [$detalle['idproducto']]);
            if ($productoExiste['count'] == 0) {
                throw new Exception("El producto con ID " . $detalle['idproducto'] . " no existe");
            }

            $paramsDetalle = [
                $idventa,
                $detalle['idproducto'],
                $detalle['cantidad'],
                $detalle['precio_unitario_venta'],
                $detalle['id_moneda_detalle'] ?? $idmoneda_general,
                $detalle['subtotal_general'] ?? 0,
                $detalle['peso_vehiculo'] ?? 0,
                $detalle['peso_bruto'] ?? 0,
                $detalle['peso_neto'] ?? 0
            ];

            $stmtDetalle = $db->prepare($sqlDetalle);
            if (!$stmtDetalle->execute($paramsDetalle)) {
                throw new Exception("No se pudo insertar el detalle del producto ID: " . $detalle['idproducto']);
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

    // Métodos públicos que usan las funciones privadas
    public function getVentasDatatable(array $params)
    {
        return $this->ejecutarBusquedaTodasVentas($params);
    }

    public function insertVenta(array $data, array $detalles, array $datosClienteNuevo = null)
    {
        $this->setData($data);
        return $this->ejecutarInsercionVenta($this->getData(), $detalles, $datosClienteNuevo);
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
            $this->setQuery(
                "SELECT 
                dv.iddetalle_venta,
                dv.idventa,
                dv.idproducto,
                dv.cantidad,
                dv.precio_unitario_venta,
                dv.subtotal_general,
                0 as descuento_porcentaje_general,
                dv.tasa_usada,
                dv.peso_vehiculo,
                dv.peso_bruto,
                dv.peso_neto,
                dv.idmoneda as id_moneda_detalle,
                p.nombre as nombre_producto,
                p.codigo as producto_codigo,
                c.nombre as nombre_categoria,
                m.codigo_moneda,
                m.nombre_moneda
             FROM detalle_venta dv
             LEFT JOIN producto p ON dv.idproducto = p.idproducto
             LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
             LEFT JOIN monedas m ON dv.idmoneda = m.idmoneda
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

            $this->setQuery("UPDATE venta SET estatus = ?, fecha_modificacion = NOW() WHERE idventa = ?");
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
            error_log("VentasModel::obtenerTasaActualMoneda - Error: " . $e->getMessage());
            $this->setResult(false);
        } finally {
            $conexion->disconnect();
        }

        return $this->getResult();
    }
}
