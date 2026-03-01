<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;

class PagosModel
{
    private $objModelPagosModel;
    private $query;
    private $array;
    private $data;
    private $result;
    private $pagoId;
    private $message;
    private $status;

    public function __construct()
    {
    }

    private function getInstanciaModel()
    {
        if ($this->objModelPagosModel == null) {
            $this->objModelPagosModel = new PagosModel();
        }
        return $this->objModelPagosModel;
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

    public function getPagoId()
    {
        return $this->pagoId;
    }

    public function setPagoId(?int $pagoId)
    {
        $this->pagoId = $pagoId;
    }

    public function getMessage()
    {
        return $this->message ?? '';
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status ?? false;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

    // Método privado para verificar si una persona existe
    private function ejecutarVerificacionPersona(int $idpersona)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM personas WHERE idpersona = ?");
            $this->setArray([$idpersona]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;

        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar persona existente: " . $e->getMessage());
            $exists = false;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    // Función privada para insertar pago
    private function ejecutarInsercionPago(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar si idpersona es válido antes de insertar
            if ($data['idpersona'] !== null) {
                if (!$this->ejecutarVerificacionPersona($data['idpersona'])) {
                    error_log("Persona con ID {$data['idpersona']} no existe");
                    $data['idpersona'] = null;
                }
            }

            $this->setQuery(
                "INSERT INTO pagos (
                    idpersona, idtipo_pago, idventa, idcompra, idsueldotemp, 
                    monto, referencia, fecha_pago, observaciones, estatus, 
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())"
            );

            $this->setArray([
                $data['idpersona'],
                $data['idtipo_pago'],
                $data['idventa'],
                $data['idcompra'],
                $data['idsueldotemp'],
                $data['monto'],
                $data['referencia'],
                $data['fecha_pago'],
                $data['observaciones']
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setPagoId($db->lastInsertId());

            if ($this->getPagoId()) {
                // Si el pago está asociado a una venta, verificar si debe marcarse como pagada
                if (!empty($data['idventa'])) {
                    $this->verificarEstadoVentaDespuesPago($db, $data['idventa']);
                }

                $this->setStatus(true);
                $this->setMessage('Pago registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de pago tras registro.');
            }

            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'data' => ['idpago' => $this->getPagoId()]
            ];

        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al insertar pago: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar pago: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function verificarEstadoVentaDespuesPago($db, $idventa)
    {
        try {
            // Solo actualizar el balance, NO marcar como pagada automáticamente
            // La venta solo se marca como pagada cuando se concilian los pagos

            // Calcular el total de pagos registrados (activos + conciliados)
            $this->setQuery("
                SELECT COALESCE(SUM(monto), 0) as total_pagado
                FROM pagos 
                WHERE idventa = ? AND estatus IN ('activo', 'conciliado')
            ");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $resultadoPagos = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPagado = $resultadoPagos['total_pagado'] ?? 0;

            // Obtener el total de la venta
            $this->setQuery("SELECT total_general FROM venta WHERE idventa = ?");
            $stmtVenta = $db->prepare($this->getQuery());
            $stmtVenta->execute([$idventa]);
            $ventaInfo = $stmtVenta->fetch(PDO::FETCH_ASSOC);

            if ($ventaInfo) {
                $totalVenta = $ventaInfo['total_general'];
                $nuevoBalance = $totalVenta - $totalPagado;

                // Asegurar que el balance no sea negativo
                if ($nuevoBalance < 0) {
                    $nuevoBalance = 0;
                }

                // SOLO actualizar el balance, no cambiar el estado
                $this->setQuery("UPDATE venta SET balance = ? WHERE idventa = ?");
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute([$nuevoBalance, $idventa]);

                error_log("PagosModel::verificarEstadoVentaDespuesPago -> Balance actualizado para venta ID: " . $idventa . " - Nuevo balance: $nuevoBalance (Total pagado: $totalPagado)");
            }

        } catch (Exception $e) {
            error_log("Error al verificar estado de venta después del pago: " . $e->getMessage());
        }
    }

    // Función privada para actualizar pago
    private function ejecutarActualizacionPago(int $idpago, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar si idpersona es válido antes de actualizar
            if ($data['idpersona'] !== null) {
                if (!$this->ejecutarVerificacionPersona($data['idpersona'])) {
                    error_log("Persona con ID {$data['idpersona']} no existe");
                    $data['idpersona'] = null;
                }
            }

            $this->setQuery(
                "UPDATE pagos SET 
                    idpersona = ?, 
                    idtipo_pago = ?, 
                    idventa = ?, 
                    idcompra = ?, 
                    idsueldotemp = ?, 
                    monto = ?, 
                    referencia = ?, 
                    fecha_pago = ?, 
                    observaciones = ?
                WHERE idpago = ? AND estatus = 'activo'"
            );

            $this->setArray([
                $data['idpersona'],
                $data['idtipo_pago'],
                $data['idventa'],
                $data['idcompra'],
                $data['idsueldotemp'],
                $data['monto'],
                $data['referencia'],
                $data['fecha_pago'],
                $data['observaciones'],
                $idpago
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();

            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Pago actualizado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar el pago o no se realizaron cambios.');
            }

            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];

        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al actualizar pago: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar pago: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para buscar pago por ID
    private function ejecutarBusquedaPagoPorId(int $idpago)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idpago,
                    p.idpersona,
                    p.idtipo_pago,
                    p.idventa,
                    p.idcompra,
                    p.idsueldotemp,
                    CAST(p.monto AS DECIMAL(15,4)) as monto,
                    p.referencia,
                    p.fecha_pago,
                    DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_formato,
                    p.observaciones,
                    p.estatus,
                    p.fecha_creacion,
                    tp.nombre as metodo_pago,
                    per.nombre as persona_nombre,
                    per.apellido as persona_apellido,
                    per.identificacion as persona_identificacion,
                    -- Información de compra
                    c.nro_compra,
                    prov.nombre as proveedor_nombre,
                    prov.apellido as proveedor_apellido,
                    prov.identificacion as proveedor_identificacion,
                    -- Información de venta
                    v.nro_venta,
                    cli.nombre as cliente_nombre,
                    cli.apellido as cliente_apellido,
                    cli.cedula as cliente_cedula,
                    -- Información de sueldo/empleado (maneja personas y empleados)
                    CASE 
                        WHEN s.idpersona IS NOT NULL THEN CONCAT(COALESCE(emp.nombre, ''), ' ', COALESCE(emp.apellido, ''))
                        WHEN s.idempleado IS NOT NULL THEN CONCAT(COALESCE(empl.nombre, ''), ' ', COALESCE(empl.apellido, ''))
                        ELSE NULL
                    END as empleado_nombre,
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN 'Compra'
                        WHEN p.idventa IS NOT NULL THEN 'Venta'
                        WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                        ELSE 'Otro'
                    END as tipo_pago_texto,
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN CONCAT(COALESCE(prov.nombre, ''), ' ', COALESCE(prov.apellido, ''))
                        WHEN p.idventa IS NOT NULL THEN CONCAT(COALESCE(cli.nombre, ''), ' ', COALESCE(cli.apellido, ''))
                        WHEN p.idsueldotemp IS NOT NULL THEN 
                            CASE 
                                WHEN s.idpersona IS NOT NULL THEN CONCAT(COALESCE(emp.nombre, ''), ' ', COALESCE(emp.apellido, ''))
                                WHEN s.idempleado IS NOT NULL THEN CONCAT(COALESCE(empl.nombre, ''), ' ', COALESCE(empl.apellido, ''))
                                ELSE 'Empleado no encontrado'
                            END
                        ELSE 'Otro pago'
                    END as destinatario
                FROM pagos p
                LEFT JOIN personas per ON p.idpersona = per.idpersona
                LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                LEFT JOIN compra c ON p.idcompra = c.idcompra
                LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
                LEFT JOIN venta v ON p.idventa = v.idventa  
                LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
                LEFT JOIN sueldos s ON p.idsueldotemp = s.idsueldo
                LEFT JOIN personas emp ON s.idpersona = emp.idpersona
                LEFT JOIN empleado empl ON s.idempleado = empl.idempleado
                WHERE p.idpago = ?"
            );

            $this->setArray([$idpago]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $resultado = $this->getResult();

        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("PagosModel::ejecutarBusquedaPagoPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para eliminar pago (soft delete)
    private function ejecutarEliminacionPago(int $idpago)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE pagos SET estatus = 'inactivo' WHERE idpago = ? AND estatus = 'activo'");
            $this->setArray([$idpago]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarEliminacionPago -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener todos los pagos
    private function ejecutarBusquedaTodosPagos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idpago,
                    p.idsueldotemp,
                    CAST(p.monto AS DECIMAL(15,4)) as monto,
                    p.referencia,
                    p.fecha_pago,
                    DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_formato,
                    p.observaciones,
                    p.estatus,
                    p.fecha_creacion,
                    tp.nombre as metodo_pago,
                    per.nombre as persona_nombre,
                    per.apellido as persona_apellido,
                    per.identificacion as persona_identificacion,
                    -- Información de compra
                    c.nro_compra,
                    prov.nombre as proveedor_nombre,
                    prov.apellido as proveedor_apellido,
                    prov.identificacion as proveedor_identificacion,
                    -- Información de venta
                    v.nro_venta,
                    cli.nombre as cliente_nombre,
                    cli.apellido as cliente_apellido,
                    cli.cedula as cliente_cedula,
                    -- Información de sueldo/empleado (maneja personas y empleados)
                    CASE 
                        WHEN s.idpersona IS NOT NULL THEN CONCAT(COALESCE(emp.nombre, ''), ' ', COALESCE(emp.apellido, ''))
                        WHEN s.idempleado IS NOT NULL THEN CONCAT(COALESCE(empl.nombre, ''), ' ', COALESCE(empl.apellido, ''))
                        ELSE NULL
                    END as empleado_nombre,
                    -- Determinar tipo y destinatario
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN 'Compra'
                        WHEN p.idventa IS NOT NULL THEN 'Venta'
                        WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                        ELSE 'Otro'
                    END as tipo_pago_texto,
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN CONCAT(COALESCE(prov.nombre, ''), ' ', COALESCE(prov.apellido, ''))
                        WHEN p.idventa IS NOT NULL THEN CONCAT(COALESCE(cli.nombre, ''), ' ', COALESCE(cli.apellido, ''))
                        WHEN p.idsueldotemp IS NOT NULL THEN 
                            CASE 
                                WHEN s.idpersona IS NOT NULL THEN CONCAT(COALESCE(emp.nombre, ''), ' ', COALESCE(emp.apellido, ''))
                                WHEN s.idempleado IS NOT NULL THEN CONCAT(COALESCE(empl.nombre, ''), ' ', COALESCE(empl.apellido, ''))
                                ELSE 'Empleado no encontrado'
                            END
                        ELSE 'Otro pago'
                    END as destinatario
                FROM pagos p
                LEFT JOIN personas per ON p.idpersona = per.idpersona
                LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                LEFT JOIN compra c ON p.idcompra = c.idcompra
                LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
                LEFT JOIN venta v ON p.idventa = v.idventa  
                LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
                LEFT JOIN sueldos s ON p.idsueldotemp = s.idsueldo
                LEFT JOIN personas emp ON s.idpersona = emp.idpersona
                LEFT JOIN empleado empl ON s.idempleado = empl.idempleado
                ORDER BY p.idpago DESC, p.fecha_creacion DESC"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Pagos obtenidos.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaTodosPagos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener pagos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener tipos de pago
    private function ejecutarBusquedaTiposPago()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idtipo_pago, nombre
                 FROM tipos_pagos 
                 WHERE estatus = 'activo' 
                 ORDER BY nombre"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Tipos de pago obtenidos.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaTiposPago - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener tipos de pago: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener compras pendientes
    private function ejecutarBusquedaComprasPendientes()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT
                    c.idcompra,
                    c.nro_compra,
                    CAST(c.total_general AS DECIMAL(15,4)) as total,
                    CAST(c.balance AS DECIMAL(15,4)) as balance, 
                    p.nombre AS proveedor,
                    p.identificacion AS proveedor_identificacion
                FROM
                    compra c
                INNER JOIN
                    proveedor p ON c.idproveedor = p.idproveedor
                WHERE
                    c.estatus_compra IN ('AUTORIZADA', 'POR_PAGAR', 'PAGO_FRACCIONADO')
                    AND c.balance > 0.01
                ORDER BY
                    c.fecha DESC"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Compras pendientes obtenidas.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaComprasPendientes - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener compras pendientes: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener ventas pendientes
    private function ejecutarBusquedaVentasPendientes()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    v.idventa,
                    v.nro_venta,
                    CAST(v.total_general AS DECIMAL(15,4)) as total,
                    CAST(v.balance AS DECIMAL(15,4)) as balance,
                    CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente,
                    c.cedula as cliente_identificacion
                FROM venta v
                INNER JOIN cliente c ON v.idcliente = c.idcliente
                WHERE v.estatus IN ('POR_PAGAR', 'PAGO_FRACCIONADO')
                AND v.balance > 0.01
                ORDER BY v.fecha_venta DESC"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Ventas pendientes obtenidas.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaVentasPendientes - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener ventas pendientes: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener sueldos pendientes
    private function ejecutarBusquedaSueldosPendientes()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    s.idsueldo,
                    s.idsueldo as idsueldotemp, -- Mantener compatibilidad
                    CASE 
                        WHEN s.idpersona IS NOT NULL THEN CONCAT(p.nombre, ' ', COALESCE(p.apellido, ''))
                        WHEN s.idempleado IS NOT NULL THEN CONCAT(e.nombre, ' ', COALESCE(e.apellido, ''))
                        ELSE 'Destinatario no encontrado'
                    END as empleado,
                    CASE 
                        WHEN s.idpersona IS NOT NULL THEN CONCAT(p.nombre, ' ', COALESCE(p.apellido, ''))
                        WHEN s.idempleado IS NOT NULL THEN CONCAT(e.nombre, ' ', COALESCE(e.apellido, ''))
                        ELSE 'Destinatario no encontrado'
                    END as nombre_completo,
                    CAST(s.monto AS DECIMAL(15,4)) as monto,
                    CAST(s.balance AS DECIMAL(15,4)) as balance,
                    s.idmoneda,
                    COALESCE(m.codigo_moneda, 'VES') as simbolo_moneda,
                    COALESCE(m.nombre_moneda, 'Bolívares') as nombre_moneda,
                    CAST(COALESCE(
                        (SELECT ht.tasa_a_bs 
                         FROM historial_tasas_bcv ht 
                         INNER JOIN monedas m2 ON ht.codigo_moneda = m2.codigo_moneda
                         WHERE m2.idmoneda = s.idmoneda 
                           AND ht.fecha_publicacion_bcv = CURDATE() 
                         ORDER BY ht.fecha_publicacion_bcv DESC 
                         LIMIT 1), 
                        COALESCE(m.valor, 1)
                    ) AS DECIMAL(15,4)) AS tasa_actual,
                    CAST((s.monto * COALESCE(
                        (SELECT ht.tasa_a_bs 
                         FROM historial_tasas_bcv ht 
                         INNER JOIN monedas m2 ON ht.codigo_moneda = m2.codigo_moneda
                         WHERE m2.idmoneda = s.idmoneda 
                           AND ht.fecha_publicacion_bcv = CURDATE() 
                         ORDER BY ht.fecha_publicacion_bcv DESC 
                         LIMIT 1), 
                        COALESCE(m.valor, 1)
                    )) AS DECIMAL(15,4)) as monto_bolivares,
                    COALESCE(s.observacion, 'Sin descripción') as periodo,
                    s.observacion,
                    s.fecha_creacion
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                LEFT JOIN monedas m ON s.idmoneda = m.idmoneda
                WHERE s.estatus IN ('POR_PAGAR', 'PAGO_FRACCIONADO')
                AND s.monto > 0
                AND (s.idpersona IS NOT NULL OR s.idempleado IS NOT NULL)
                ORDER BY s.fecha_creacion DESC"
            );

            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Sueldos pendientes obtenidos.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaSueldosPendientes - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener sueldos pendientes: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener información de compra
    private function ejecutarBusquedaInfoCompra(int $idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT p.idproveedor, per.idpersona
                 FROM compra c 
                 INNER JOIN proveedor p ON c.idproveedor = p.idproveedor
                 LEFT JOIN personas per ON p.identificacion = per.identificacion
                 WHERE c.idcompra = ?"
            );

            $this->setArray([$idcompra]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();

            if (!empty($result)) {
                if ($result['idpersona']) {
                    $resultado = ['idpersona' => $result['idpersona']];
                } else {
                    // Crear persona para el proveedor si no existe
                    $resultado = $this->ejecutarCreacionPersonaParaProveedor($result['idproveedor']);
                }
            } else {
                $resultado = ['idpersona' => null];
            }

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaInfoCompra - Error: " . $e->getMessage());
            $resultado = ['idpersona' => null];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener información de venta
    private function ejecutarBusquedaInfoVenta(int $idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT c.idcliente, per.idpersona
                 FROM venta v 
                 INNER JOIN cliente c ON v.idcliente = c.idcliente
                 LEFT JOIN personas per ON c.cedula = per.identificacion
                 WHERE v.idventa = ?"
            );

            $this->setArray([$idventa]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();

            if (!empty($result)) {
                if ($result['idpersona']) {
                    $resultado = ['idpersona' => $result['idpersona']];
                } else {
                    // Crear persona para el cliente si no existe
                    $resultado = $this->ejecutarCreacionPersonaParaCliente($result['idcliente']);
                }
            } else {
                $resultado = ['idpersona' => null];
            }

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarBusquedaInfoVenta - Error: " . $e->getMessage());
            $resultado = ['idpersona' => null];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para crear persona para proveedor
    private function ejecutarCreacionPersonaParaProveedor(int $idproveedor)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT nombre, apellido, identificacion FROM proveedor WHERE idproveedor = ?");
            $this->setArray([$idproveedor]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($proveedor)) {
                $this->setQuery(
                    "INSERT INTO personas (nombre, apellido, identificacion, telefono_principal, estatus, fecha_creacion) 
                     VALUES (?, ?, ?, '', 'activo', NOW())"
                );
                $this->setArray([
                    $proveedor['nombre'],
                    $proveedor['apellido'] ?? '',
                    $proveedor['identificacion']
                ]);

                $stmt = $db->prepare($this->getQuery());
                $stmt->execute($this->getArray());
                $idpersona = $db->lastInsertId();

                if ($idpersona > 0) {
                    $resultado = ['idpersona' => $idpersona];
                } else {
                    $resultado = ['idpersona' => null];
                }
            } else {
                $resultado = ['idpersona' => null];
            }

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarCreacionPersonaParaProveedor - Error: " . $e->getMessage());
            $resultado = ['idpersona' => null];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para crear persona para cliente
    private function ejecutarCreacionPersonaParaCliente(int $idcliente)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT nombre, apellido, cedula FROM cliente WHERE idcliente = ?");
            $this->setArray([$idcliente]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($cliente)) {
                $this->setQuery(
                    "INSERT INTO personas (nombre, apellido, identificacion, telefono_principal, estatus, fecha_creacion) 
                     VALUES (?, ?, ?, '', 'activo', NOW())"
                );
                $this->setArray([
                    $cliente['nombre'],
                    $cliente['apellido'] ?? '',
                    $cliente['cedula']
                ]);

                $stmt = $db->prepare($this->getQuery());
                $stmt->execute($this->getArray());
                $idpersona = $db->lastInsertId();

                if ($idpersona > 0) {
                    $resultado = ['idpersona' => $idpersona];
                } else {
                    $resultado = ['idpersona' => null];
                }
            } else {
                $resultado = ['idpersona' => null];
            }

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarCreacionPersonaParaCliente - Error: " . $e->getMessage());
            $resultado = ['idpersona' => null];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para conciliar pago
    private function ejecutarConciliacionPago(int $idpago)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Obtener información del pago a conciliar
            $this->setQuery("SELECT * FROM pagos WHERE idpago = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idpago]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }

            // Validar que el pago no esté ya conciliado
            if ($pago['estatus'] === 'conciliado') {
                throw new Exception("El pago ya está conciliado");
            }

            // Actualizar el estado del pago a conciliado
            $this->setQuery("UPDATE pagos SET estatus = 'conciliado' WHERE idpago = ?");
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute([$idpago]);

            if (!$resultado || $stmt->rowCount() == 0) {
                throw new Exception("No se pudo actualizar el estado del pago");
            }

            // Procesar según el tipo (compra, venta o sueldo)
            if (!empty($pago['idcompra'])) {
                // Lógica para compras
                $this->procesarConciliacionCompra($db, $pago['idcompra']);
            } elseif (!empty($pago['idventa'])) {
                // Lógica para ventas
                $this->procesarConciliacionVenta($db, $pago['idventa']);
            } elseif (!empty($pago['idsueldotemp'])) {
                // Los pagos de sueldos se procesan automáticamente mediante el trigger
                // trg_pago_sueldo_conciliado que:
                // - Resta el monto del balance del sueldo
                // - Marca el sueldo como PAGADO si el balance llega a 0
                // - Registra los eventos en la bitácora
                error_log("PagosModel::ejecutarConciliacionPago -> Pago de sueldo ID: {$pago['idsueldotemp']} procesado por trigger automático");
            }

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("PagosModel::ejecutarConciliacionPago - Error: " . $e->getMessage());
            throw $e;
        } finally {
            $conexion->disconnect();
        }
    }

    // Métodos públicos que usan las funciones privadas
    public function insertPago(array $data)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        $objModelPagosModel->setData($data);
        return $objModelPagosModel->ejecutarInsercionPago($objModelPagosModel->getData());
    }

    public function updatePago(int $idpago, array $data)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        $objModelPagosModel->setData($data);
        $objModelPagosModel->setPagoId($idpago);
        return $objModelPagosModel->ejecutarActualizacionPago($objModelPagosModel->getPagoId(), $objModelPagosModel->getData());
    }

    public function selectPagoById(int $idpago)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        $objModelPagosModel->setPagoId($idpago);
        $result = $objModelPagosModel->ejecutarBusquedaPagoPorId($objModelPagosModel->getPagoId());

        if (!empty($result)) {
            return [
                'status' => true,
                'data' => $result
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Pago no encontrado'
            ];
        }
    }

    public function deletePagoById(int $idpago)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        $objModelPagosModel->setPagoId($idpago);
        $result = $objModelPagosModel->ejecutarEliminacionPago($objModelPagosModel->getPagoId());

        if ($result) {
            return [
                'status' => true,
                'message' => 'Pago eliminado exitosamente'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'No se pudo eliminar el pago'
            ];
        }
    }

    public function selectAllPagos()
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaTodosPagos();
    }

    public function selectTiposPago()
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaTiposPago();
    }

    private function ejecutarVerificacionTipoPagoExiste(int $idtipo_pago)
    {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectGeneral();

            $this->setQuery("SELECT COUNT(*) as total FROM tipos_pagos WHERE idtipo_pago = ? AND estatus = 'activo'");
            $this->setArray([$idtipo_pago]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $conexion->disconnect();

            return $result && $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar tipo de pago: " . $e->getMessage());
            return false;
        }
    }

    public function verificarTipoPagoExiste(int $idtipo_pago)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarVerificacionTipoPagoExiste($idtipo_pago);
    }

    public function selectComprasPendientes()
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaComprasPendientes();
    }

    public function selectVentasPendientes()
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaVentasPendientes();
    }

    public function selectSueldosPendientes()
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaSueldosPendientes();
    }

    public function getInfoCompra(int $idcompra)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaInfoCompra($idcompra);
    }

    public function getInfoVenta(int $idventa)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaInfoVenta($idventa);
    }

    private function ejecutarBusquedaInfoSueldo(int $idsueldotemp)
    {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectGeneral();

            // Buscar en la tabla sueldos usando el ID proporcionado
            $this->setQuery(
                "SELECT 
                    s.idpersona,
                    s.idempleado,
                    CASE 
                        WHEN s.idpersona IS NOT NULL THEN s.idpersona
                        WHEN s.idempleado IS NOT NULL THEN (
                            SELECT p.idpersona 
                            FROM personas p 
                            INNER JOIN empleado e ON p.identificacion = e.identificacion 
                            WHERE e.idempleado = s.idempleado 
                            LIMIT 1
                        )
                        ELSE NULL
                    END as idpersona_final
                FROM sueldos s 
                WHERE s.idsueldo = ?"
            );

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idsueldotemp]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $conexion->disconnect();

            if ($result && $result['idpersona_final']) {
                return ['idpersona' => $result['idpersona_final']];
            } else {
                return ['idpersona' => null];
            }

        } catch (Exception $e) {
            error_log("Error en getInfoSueldo: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    public function getInfoSueldo(int $idsueldotemp)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarBusquedaInfoSueldo($idsueldotemp);
    }

    public function conciliarPago(int $idpago)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        $objModelPagosModel->setPagoId($idpago);

        try {
            $result = $objModelPagosModel->ejecutarConciliacionPago($objModelPagosModel->getPagoId());

            return [
                'status' => true,
                'message' => 'Pago conciliado exitosamente',
                'idpago' => $objModelPagosModel->getPagoId()
            ];

        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'idpago' => $objModelPagosModel->getPagoId()
            ];
        }
    }

    /**
     * Obtiene un resumen del estado de pagos de una venta
     */
    public function obtenerResumenPagosVenta($idventa)
    {
        $objModelPagosModel = $this->getInstanciaModel();
        return $objModelPagosModel->ejecutarObtenerResumenPagosVenta($idventa);
    }

    private function ejecutarObtenerResumenPagosVenta($idventa)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener información de la venta
            $this->setQuery("SELECT total_general, balance, estatus FROM venta WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return ['status' => false, 'message' => 'Venta no encontrada'];
            }

            // Obtener resumen de pagos
            $this->setQuery("
                SELECT 
                    COUNT(*) as total_pagos,
                    COALESCE(SUM(CASE WHEN estatus = 'conciliado' THEN monto ELSE 0 END), 0) as total_conciliado,
                    COALESCE(SUM(CASE WHEN estatus = 'activo' THEN monto ELSE 0 END), 0) as total_pendiente,
                    COALESCE(SUM(monto), 0) as total_pagos_registrados
                FROM pagos 
                WHERE idventa = ?
            ");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $resumenPagos = $stmt->fetch(PDO::FETCH_ASSOC);

            $diferenciaPendiente = $venta['total_general'] - $resumenPagos['total_pagos_registrados'];

            return [
                'status' => true,
                'venta' => [
                    'total_venta' => $venta['total_general'],
                    'balance_actual' => $venta['balance'],
                    'estatus_venta' => $venta['estatus']
                ],
                'pagos' => [
                    'total_pagos' => $resumenPagos['total_pagos'],
                    'total_conciliado' => $resumenPagos['total_conciliado'],
                    'total_pendiente' => $resumenPagos['total_pendiente'],
                    'total_registrado' => $resumenPagos['total_pagos_registrados'],
                    'diferencia_pendiente' => $diferenciaPendiente
                ],
                'puede_completarse' => $diferenciaPendiente <= 0.01,
                'falta_por_registrar' => max(0, $diferenciaPendiente)
            ];

        } catch (Exception $e) {
            error_log("PagosModel::ejecutarObtenerResumenPagosVenta - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener resumen'];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Procesa la conciliación de un pago de compra
     * Nota: El trigger trg_pago_update_compra_conciliado maneja automáticamente
     * la actualización del balance y estado de la compra
     */
    private function procesarConciliacionCompra($db, $idcompra)
    {
        try {
            // El trigger de la base de datos se encarga de:
            // 1. Calcular el total pagado
            // 2. Actualizar el balance de la compra  
            // 3. Cambiar el estado de la compra según corresponda
            // Por lo tanto, solo registramos el evento

            error_log("PagosModel::procesarConciliacionCompra -> Procesando compra ID: {$idcompra}");

            // Opcional: Verificar el resultado después del trigger
            $this->setQuery("SELECT balance, estatus_compra FROM compra WHERE idcompra = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($compra) {
                error_log("PagosModel::procesarConciliacionCompra -> Compra ID: {$idcompra}, Balance: {$compra['balance']}, Estado: {$compra['estatus_compra']}");

                // Si la compra está pagada, procesar notificaciones
                if ($compra['estatus_compra'] === 'PAGADA') {
                    try {
                        if (file_exists("app/models/notificacionesModel.php")) {
                            require_once "app/models/notificacionesModel.php";
                            $notificacionesModel = new NotificacionesModel();
                            error_log("PagosModel: Compra marcada como pagada ID: {$idcompra}");
                        }
                    } catch (Exception $e) {
                        error_log("PagosModel: Error al procesar notificaciones de compra pagada ID {$idcompra}: " . $e->getMessage());
                    }
                }
            }

        } catch (Exception $e) {
            error_log("PagosModel::procesarConciliacionCompra - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesa la conciliación de un pago de venta
     */
    private function procesarConciliacionVenta($db, $idventa)
    {
        try {
            // Obtener información de la venta
            $this->setQuery("SELECT total_general, balance, estatus FROM venta WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                throw new Exception("Venta no encontrada");
            }

            // Calcular total pagado después de la conciliación
            $this->setQuery("SELECT COALESCE(SUM(monto), 0) as total_pagado FROM pagos WHERE idventa = ? AND estatus = 'conciliado'");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idventa]);
            $totalPagado = $stmt->fetch(PDO::FETCH_ASSOC)['total_pagado'];

            // Calcular nuevo balance
            $nuevoBalance = $venta['total_general'] - $totalPagado;
            if ($nuevoBalance < 0) {
                $nuevoBalance = 0;
            }

            // Actualizar balance de la venta
            $this->setQuery("UPDATE venta SET balance = ?, ultima_modificacion = NOW() WHERE idventa = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$nuevoBalance, $idventa]);

            // Si el balance llega a 0, marcar como pagada
            if ($nuevoBalance <= 0.01) {
                $this->setQuery("UPDATE venta SET estatus = 'PAGADA', balance = 0, ultima_modificacion = NOW() WHERE idventa = ?");
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute([$idventa]);

                // Limpiar notificaciones si existe el modelo
                try {
                    if (file_exists("app/models/notificacionesModel.php")) {
                        require_once "app/models/notificacionesModel.php";
                        $notificacionesModel = new NotificacionesModel();
                        // TODO: Implementar método limpiarNotificacionesVentaPagada si es necesario
                        // if (method_exists($notificacionesModel, 'limpiarNotificacionesVentaPagada')) {
                        //     $notificacionesModel->limpiarNotificacionesVentaPagada($idventa);
                        //     error_log("PagosModel: Notificaciones limpiadas para venta pagada ID: {$idventa}");
                        // }
                        error_log("PagosModel: Venta marcada como pagada ID: {$idventa}");
                    }
                } catch (Exception $e) {
                    error_log("PagosModel: Error al procesar notificaciones de venta pagada ID {$idventa}: " . $e->getMessage());
                }
            }

            error_log("PagosModel::procesarConciliacionVenta -> Venta ID: {$idventa}, Balance actualizado: {$nuevoBalance}");

        } catch (Exception $e) {
            error_log("PagosModel::procesarConciliacionVenta - Error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>