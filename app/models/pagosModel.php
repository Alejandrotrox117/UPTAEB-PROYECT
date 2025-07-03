<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";

class PagosModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $pagoId;
    private $message;
    private $status;

    public function __construct()
    {
        parent::__construct();
    }

    // Getters y Setters
    public function getQuery(){
        return $this->query;
    }

    public function setQuery(string $query){
        $this->query = $query;
    }

    public function getArray(){
        return $this->array ?? [];
    }

    public function setArray(array $array){
        $this->array = $array;
    }

    public function getData(){
        return $this->data ?? [];
    }

    public function setData(array $data){
        $this->data = $data;
    }

    public function getResult(){
        return $this->result;
    }

    public function setResult($result){
        $this->result = $result;
    }

    public function getPagoId(){
        return $this->pagoId;
    }

    public function setPagoId(?int $pagoId){
        $this->pagoId = $pagoId;
    }

    public function getMessage(){
        return $this->message ?? '';
    }

    public function setMessage(string $message){
        $this->message = $message;
    }

    public function getStatus(){
        return $this->status ?? false;
    }

    public function setStatus(bool $status){
        $this->status = $status;
    }

    // Método privado para verificar si una persona existe
    private function ejecutarVerificacionPersona(int $idpersona){
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
    private function ejecutarInsercionPago(array $data){
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

    // Función privada para actualizar pago
    private function ejecutarActualizacionPago(int $idpago, array $data){
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
    private function ejecutarBusquedaPagoPorId(int $idpago){
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
                    p.monto,
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
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN 'Compra'
                        WHEN p.idventa IS NOT NULL THEN 'Venta'
                        WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                        ELSE 'Otro'
                    END as tipo_pago_texto,
                    CASE 
                        WHEN p.idcompra IS NOT NULL THEN CONCAT(COALESCE(prov.nombre, ''), ' ', COALESCE(prov.apellido, ''))
                        WHEN p.idventa IS NOT NULL THEN CONCAT(COALESCE(cli.nombre, ''), ' ', COALESCE(cli.apellido, ''))
                        ELSE 'Otro pago'
                    END as destinatario
                FROM pagos p
                LEFT JOIN personas per ON p.idpersona = per.idpersona
                LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                LEFT JOIN compra c ON p.idcompra = c.idcompra
                LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
                LEFT JOIN venta v ON p.idventa = v.idventa  
                LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
                LEFT JOIN sueldos_temporales st ON p.idsueldotemp = st.idsueldotemp
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
    private function ejecutarEliminacionPago(int $idpago){
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
    private function ejecutarBusquedaTodosPagos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idpago,
                    p.monto,
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
                        ELSE 'Otro pago'
                    END as destinatario
                FROM pagos p
                LEFT JOIN personas per ON p.idpersona = per.idpersona
                LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                LEFT JOIN compra c ON p.idcompra = c.idcompra
                LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
                LEFT JOIN venta v ON p.idventa = v.idventa  
                LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
                LEFT JOIN sueldos_temporales st ON p.idsueldotemp = st.idsueldotemp
                ORDER BY p.fecha_pago DESC, p.idpago DESC"
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
    private function ejecutarBusquedaTiposPago(){
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
    private function ejecutarBusquedaComprasPendientes(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT
                    c.idcompra,
                    c.nro_compra,
                    c.balance, 
                    p.nombre AS proveedor,
                    p.identificacion AS proveedor_identificacion
                FROM
                    compra c
                INNER JOIN
                    proveedor p ON c.idproveedor = p.idproveedor
                WHERE
                    (c.estatus_compra = 'POR_PAGAR' OR c.estatus_compra = 'PAGO_FRACCIONADO')
                    AND c.balance > 0
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
    private function ejecutarBusquedaVentasPendientes(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    v.idventa,
                    v.nro_venta,
                    v.total,
                    c.nombre as cliente,
                    c.cedula as cliente_identificacion
                FROM venta v
                INNER JOIN cliente c ON v.idcliente = c.idcliente
                WHERE v.estatus = 'activo'
                AND v.idventa NOT IN (
                    SELECT pg.idventa 
                    FROM pagos pg 
                    WHERE pg.idventa IS NOT NULL 
                    AND pg.estatus IN ('activo', 'conciliado')
                )
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
    private function ejecutarBusquedaSueldosPendientes(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    st.idsueldotemp,
                    st.descripcion as empleado,
                    st.monto as total,
                    st.periodo,
                    '' as empleado_identificacion
                FROM sueldos_temporales st
                WHERE st.estatus = 'activo'
                AND st.idsueldotemp NOT IN (
                    SELECT pg.idsueldotemp 
                    FROM pagos pg 
                    WHERE pg.idsueldotemp IS NOT NULL 
                    AND pg.estatus IN ('activo', 'conciliado')
                )
                ORDER BY st.fecha_creacion DESC"
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
    private function ejecutarBusquedaInfoCompra(int $idcompra){
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
    private function ejecutarBusquedaInfoVenta(int $idventa){
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
    private function ejecutarCreacionPersonaParaProveedor(int $idproveedor){
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
                    "INSERT INTO personas (nombre, apellido, identificacion, tipo, estatus, fecha_creacion) 
                     VALUES (?, ?, ?, 'proveedor', 'activo', NOW())"
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
    private function ejecutarCreacionPersonaParaCliente(int $idcliente){
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
                    "INSERT INTO personas (nombre, apellido, identificacion, tipo, estatus, fecha_creacion) 
                     VALUES (?, ?, ?, 'cliente', 'activo', NOW())"
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
    private function ejecutarConciliacionPago(int $idpago){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Primero obtener información del pago
            $this->setQuery("SELECT idcompra FROM pagos WHERE idpago = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idpago]);
            $pagoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pagoInfo) {
                error_log("PagosModel::ejecutarConciliacionPago -> Pago no encontrado: " . $idpago);
                return false;
            }
            
            // Actualizar el estado del pago a conciliado
            $this->setQuery("UPDATE pagos SET estatus = 'conciliado' WHERE idpago = ? AND estatus = 'activo'");
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute([$idpago]);
            
            if (!$resultado || $stmt->rowCount() == 0) {
                error_log("PagosModel::ejecutarConciliacionPago -> No se pudo actualizar el pago: " . $idpago);
                return false;
            }
            
            // Si el pago está asociado a una compra, verificar si todos los pagos están conciliados
            if ($pagoInfo['idcompra']) {
                $idcompra = $pagoInfo['idcompra'];
                
                // Verificar si todos los pagos de esta compra están conciliados
                $this->setQuery("
                    SELECT COUNT(*) as total_pagos, 
                           SUM(CASE WHEN estatus = 'conciliado' THEN 1 ELSE 0 END) as pagos_conciliados
                    FROM pagos 
                    WHERE idcompra = ? AND estatus IN ('activo', 'conciliado')
                ");
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute([$idcompra]);
                $estatusPagos = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si todos los pagos están conciliados, actualizar el estado de la compra a PAGADA
                if ($estatusPagos['total_pagos'] > 0 && 
                    $estatusPagos['total_pagos'] == $estatusPagos['pagos_conciliados']) {
                    
                    $this->setQuery("UPDATE compra SET estatus_compra = 'PAGADA' WHERE idcompra = ? AND estatus_compra != 'PAGADA'");
                    $stmt = $db->prepare($this->getQuery());
                    $resultadoCompra = $stmt->execute([$idcompra]);
                    
                    if ($resultadoCompra && $stmt->rowCount() > 0) {
                        error_log("PagosModel::ejecutarConciliacionPago -> Compra marcada como PAGADA: " . $idcompra);
                        
                        // Limpiar notificaciones de compra cuando se marca como pagada
                        try {
                            require_once "app/models/notificacionesModel.php";
                            $notificacionesModel = new NotificacionesModel();
                            $notificacionesModel->limpiarNotificacionesCompraPagada($idcompra);
                            error_log("PagosModel: Notificaciones limpiadas para compra pagada ID: {$idcompra}");
                        } catch (Exception $e) {
                            error_log("PagosModel: Error al limpiar notificaciones de compra pagada ID {$idcompra}: " . $e->getMessage());
                        }
                    }
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("PagosModel::ejecutarConciliacionPago -> " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    // Métodos públicos que usan las funciones privadas
    public function insertPago(array $data){
        $this->setData($data);
        return $this->ejecutarInsercionPago($this->getData());
    }

    public function updatePago(int $idpago, array $data){
        $this->setData($data);
        $this->setPagoId($idpago);
        return $this->ejecutarActualizacionPago($this->getPagoId(), $this->getData());
    }

    public function selectPagoById(int $idpago){
        $this->setPagoId($idpago);
        $result = $this->ejecutarBusquedaPagoPorId($this->getPagoId());
        
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

    public function deletePagoById(int $idpago){
        $this->setPagoId($idpago);
        $result = $this->ejecutarEliminacionPago($this->getPagoId());
        
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

    public function selectAllPagos(){
        return $this->ejecutarBusquedaTodosPagos();
    }

    public function selectTiposPago(){
        return $this->ejecutarBusquedaTiposPago();
    }

    public function selectComprasPendientes(){
        return $this->ejecutarBusquedaComprasPendientes();
    }

    public function selectVentasPendientes(){
        return $this->ejecutarBusquedaVentasPendientes();
    }

    public function selectSueldosPendientes(){
        return $this->ejecutarBusquedaSueldosPendientes();
    }

    public function getInfoCompra(int $idcompra){
        return $this->ejecutarBusquedaInfoCompra($idcompra);
    }

    public function getInfoVenta(int $idventa){
        return $this->ejecutarBusquedaInfoVenta($idventa);
    }

    public function getInfoSueldo(int $idsueldotemp){
        try {
            // Por ahora retorna null, se puede implementar después si es necesario
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en getInfoSueldo: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    public function conciliarPago(int $idpago){
        $this->setPagoId($idpago);
        $result = $this->ejecutarConciliacionPago($this->getPagoId());
        
        if ($result) {
            return [
                'status' => true,
                'message' => 'Pago conciliado exitosamente',
                'idpago' => $this->getPagoId()
            ];
        } else {
            return [
                'status' => false,
                'message' => 'No se pudo conciliar el pago',
                'idpago' => $this->getPagoId()
            ];
        }
    }
}
?>