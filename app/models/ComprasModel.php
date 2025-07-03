<?php
require_once "app/core/Conexion.php";
require_once "app/core/Mysql.php";
require_once "app/models/notificacionesModel.php";

class ComprasModel extends Mysql
{
    const SUPER_USUARIO_ROL_ID = 1; // ID del rol de super usuario
    
    private $query;
    private $array;
    private $data;
    private $result;
    private $message;
    private $status;

    private $idcompra;
    private $nro_compra;
    private $fecha;
    private $idproveedor;
    private $idmoneda_general;
    private $subtotal_general;
    private $descuento_porcentaje_general;
    private $monto_descuento_general;
    private $total_general;
    private $estatus_compra;
    private $observaciones_compra;
    private $termino;
    private $identificacion;

    private $iddetalle_compra;
    private $idproducto;
    private $descripcion_temporal_producto;
    private $cantidad;
    private $precio_unitario_compra;
    private $idmoneda_detalle;
    private $subtotal_linea;
    private $peso_vehiculo;
    private $peso_bruto;
    private $peso_neto;

    public function __construct()
    {
        parent::__construct();
    }

    // GETTERS Y SETTERS DE CONTROL
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

    // GETTERS Y SETTERS DE COMPRA
    public function setIdCompra($idcompra){
        $this->idcompra = $idcompra;
    }
    public function getIdCompra(){
        return $this->idcompra;
    }
    public function setNroCompra($nro_compra){
        $this->nro_compra = $nro_compra;
    }
    public function getNroCompra(){
        return $this->nro_compra;
    }
    public function setFecha($fecha){
        $this->fecha = $fecha;
    }
    public function getFecha(){
        return $this->fecha;
    }
    public function setIdProveedor($idproveedor){
        $this->idproveedor = $idproveedor; 
    }
    public function getIdProveedor(){
        return $this->idproveedor;
    }
    public function setIdMonedaGeneral($idmoneda_general){
        $this->idmoneda_general = $idmoneda_general;
    }
    public function getIdMonedaGeneral(){
        return $this->idmoneda_general; 
    }
    public function setSubtotalGeneral($subtotal_general){ 
        $this->subtotal_general = $subtotal_general;
    }
    public function getSubtotalGeneral(){
        return $this->subtotal_general;
    }
    public function setDescuentoPorcentajeGeneral($descuento_porcentaje_general){
        $this->descuento_porcentaje_general = $descuento_porcentaje_general;
    }
    public function getDescuentoPorcentajeGeneral(){
        return $this->descuento_porcentaje_general;
    }
    public function setMontoDescuentoGeneral($monto_descuento_general){
        $this->monto_descuento_general = $monto_descuento_general;
    }
    public function getMontoDescuentoGeneral(){
        return $this->monto_descuento_general;
    }
    public function setTotalGeneral($total_general){
        $this->total_general = $total_general;
    }
    public function getTotalGeneral(){
        return $this->total_general;
    }
    public function setEstatusCompra($estatus_compra){
        $this->estatus_compra = $estatus_compra;
    }
    public function getEstatusCompra(){
        return $this->estatus_compra;
    }
    public function setObservacionesCompra($observaciones_compra){
        $this->observaciones_compra = $observaciones_compra; 
    }
    public function getObservacionesCompra(){
        return $this->observaciones_compra; 
    }
    public function setTermino($termino){
        $this->termino = $termino; 
    }
    public function getTermino(){
        return $this->termino;
    }
    public function setIdentificacion($identificacion){ 
        $this->identificacion = $identificacion; 
    }
    public function getIdentificacion(){
        return $this->identificacion; 
    }
    public function setIdProducto($idproducto){
        $this->idproducto = $idproducto;
    }
    public function getIdProducto(){
        return $this->idproducto;
    }

    // MÉTODOS PRIVADOS 
    private function ejecutarConsultaTodasCompras(int $idUsuarioSesion = 0)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT 
                        c.idcompra, 
                        c.nro_compra, 
                        c.fecha, 
                        CONCAT(p.nombre, ' ', COALESCE(p.apellido, '')) as proveedor,
                        c.total_general, 
                        c.estatus_compra,
                        c.observaciones_compra,
                        c.fecha_creacion, 
                        c.fecha_modificacion
                    FROM compra c
                    LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor
                    ORDER BY c.fecha_creacion DESC");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarConsultaTodasCompras - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarGeneracionNumeroCompra()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $year = date("Y");
            $this->setQuery("SELECT MAX(CAST(SUBSTRING_INDEX(nro_compra, '-', -1) AS UNSIGNED)) as max_num
                        FROM compra WHERE nro_compra LIKE ?");
            $this->setArray(["C-" . $year . "-%"]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $result = $this->getResult();
            $next_num = ($result && isset($result['max_num'])) ? intval($result['max_num']) + 1 : 1;
            return "C-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarGeneracionNumeroCompra - Error: " . $e->getMessage());
            return "C-" . date("Y") . "-ERROR";
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaProveedor()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT idproveedor, nombre, apellido, identificacion
                        FROM proveedor
                        WHERE (nombre LIKE ? OR apellido LIKE ? OR identificacion LIKE ?)
                        AND estatus = 'activo'
                        LIMIT 10");
            
            $param = "%{$this->getTermino()}%";
            $this->setArray([$param, $param, $param]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaProveedor - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaProductosConCategoria()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT
                        p.idproducto,
                        p.nombre AS nombre_producto,
                        p.idcategoria,
                        cp.nombre AS nombre_categoria,
                        p.precio AS precio_referencia_compra,
                        m.idmoneda AS idmoneda_producto,
                        p.moneda AS codigo_moneda
                    FROM
                        producto p
                    JOIN
                        categoria cp ON p.idcategoria = cp.idcategoria
                    LEFT JOIN
                        monedas m ON p.moneda = m.codigo_moneda 
                    WHERE
                        p.estatus = 'activo'");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarConsultaProductosConCategoria - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaProductoPorId()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT p.idproducto, p.nombre, p.idcategoria,
                           p.precio, p.moneda,
                           m.codigo_moneda as codigo_moneda,
                           m.idmoneda as idmoneda_producto,
                           cp.nombre as nombre_categoria
                    FROM producto p
                    JOIN categoria cp ON p.idcategoria = cp.idcategoria
                    LEFT JOIN monedas m ON p.moneda = m.codigo_moneda
                    WHERE p.idproducto = ? AND p.estatus = 'activo'");
            
            $this->setArray([$this->getIdProducto()]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaProductoPorId - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaMonedasActivas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT idmoneda, codigo_moneda, valor FROM monedas WHERE estatus = 'activo'");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarConsultaMonedasActivas - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaTasasPorFecha()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT codigo_moneda, tasa_a_bs 
                    FROM historial_tasas_bcv 
                    WHERE fecha_publicacion_bcv = ?");
            
            $this->setArray([$this->getFecha()]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
            $tasas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tasas[$row['codigo_moneda']] = floatval($row['tasa_a_bs']);
            }
            
            return $tasas;
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarConsultaTasasPorFecha - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaUltimoPesoRomana()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT peso FROM historial_romana ORDER BY idromana DESC LIMIT 1");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $result = $this->getResult();
            if ($result && isset($result['peso'])) {
                return floatval($result['peso']);
            } else {
                return null;
            }
            
        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarConsultaUltimoPesoRomana - Error: " . $e->getMessage());
            return null;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionCompra(array $datosCompra, array $detallesCompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $this->setQuery("INSERT INTO compra (nro_compra, fecha, idproveedor, idmoneda_general, subtotal_general, descuento_porcentaje_general, monto_descuento_general, total_general, balance, observaciones_compra, estatus_compra)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'BORRADOR')");
            
            $this->setArray([
                $datosCompra['nro_compra'],
                $datosCompra['fecha_compra'],
                $datosCompra['idproveedor'],
                $datosCompra['idmoneda_general'],
                $datosCompra['subtotal_general_compra'] ?? 0,
                $datosCompra['descuento_porcentaje_compra'] ?? 0,
                $datosCompra['monto_descuento_compra'] ?? 0,
                $datosCompra['total_general_compra'],
                $datosCompra['total_general_compra'],
                $datosCompra['observaciones_compra']
            ]);

            $stmtCompra = $db->prepare($this->getQuery());
            if (!$stmtCompra->execute($this->getArray())) {
                $db->rollBack();
                throw new Exception("Error al insertar cabecera de compra");
            }
            
            $idCompra = $db->lastInsertId();

            $this->setQuery("INSERT INTO detalle_compra (idcompra, idproducto, descripcion_temporal_producto, cantidad, descuento, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmtDetalle = $db->prepare($this->getQuery());

            foreach ($detallesCompra as $detalle) {
                $arrDataDetalle = [
                    $idCompra,
                    $detalle['idproducto'],
                    $detalle['descripcion_temporal_producto'],
                    $detalle['cantidad'],
                    $detalle['descuento'] ?? 0,
                    $detalle['precio_unitario_compra'],
                    $detalle['idmoneda_detalle'],
                    $detalle['subtotal_linea'],
                    $detalle['peso_vehiculo'],
                    $detalle['peso_bruto'],
                    $detalle['peso_neto']
                ];

                if (!$stmtDetalle->execute($arrDataDetalle)) {
                    $db->rollBack();
                    throw new Exception("Error al insertar detalle de compra");
                }
            }

            $db->commit();
            return $idCompra;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("ComprasModel::ejecutarInsercionCompra - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarActualizacionCompra(int $idcompra, array $datosCompra, array $detallesCompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();
        
            $this->setQuery("UPDATE compra SET 
                        fecha = ?, 
                        idproveedor = ?, 
                        idmoneda_general = ?, 
                        subtotal_general = ?, 
                        descuento_porcentaje_general = ?, 
                        monto_descuento_general = ?, 
                        total_general = ?, 
                        observaciones_compra = ?,
                        fecha_modificacion = NOW()
                    WHERE idcompra = ?");
            
            $this->setArray([
                $datosCompra['fecha_compra'],
                $datosCompra['idproveedor'],
                $datosCompra['idmoneda_general'],
                $datosCompra['subtotal_general_compra'] ?? 0,
                $datosCompra['descuento_porcentaje_compra'] ?? 0,
                $datosCompra['monto_descuento_compra'] ?? 0,
                $datosCompra['total_general_compra'],
                $datosCompra['observaciones_compra'],
                $idcompra
            ]);

            $stmtCompra = $db->prepare($this->getQuery());
            if (!$stmtCompra->execute($this->getArray())) {
                $db->rollBack();
                throw new Exception("Error al actualizar cabecera de compra");
            }

            $this->setQuery("DELETE FROM detalle_compra WHERE idcompra = ?");
            $stmtDelete = $db->prepare($this->getQuery());
            if (!$stmtDelete->execute([$idcompra])) {
                $db->rollBack();
                throw new Exception("Error al eliminar detalles existentes");
            }

            $this->setQuery("INSERT INTO detalle_compra (idcompra, idproducto, descripcion_temporal_producto, cantidad, descuento, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmtDetalle = $db->prepare($this->getQuery());

            foreach ($detallesCompra as $detalle) {
                $arrDataDetalle = [
                    $idcompra,
                    $detalle['idproducto'],
                    $detalle['descripcion_temporal_producto'],
                    $detalle['cantidad'],
                    $detalle['descuento'] ?? 0,
                    $detalle['precio_unitario_compra'],
                    $detalle['idmoneda_detalle'],
                    $detalle['subtotal_linea'],
                    $detalle['peso_vehiculo'],
                    $detalle['peso_bruto'],
                    $detalle['peso_neto']
                ];

                if (!$stmtDetalle->execute($arrDataDetalle)) {
                    $db->rollBack();
                    throw new Exception("Error al insertar detalle actualizado de compra");
                }
            }

            $db->commit();
            return true;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("ComprasModel::ejecutarActualizacionCompra - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaCompraCompletaParaEditar($idcompra)
    {
        try {
            $compra = $this->ejecutarBusquedaCompraPorId($idcompra);
            if (!$compra) {
                return false;
            }

            $detalles = $this->ejecutarBusquedaDetalleCompraPorIdParaEditar($idcompra);

            return [
                'compra' => $compra,
                'detalles' => $detalles
            ];

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaCompraCompletaParaEditar - Error: " . $e->getMessage());
            return false;
        }
    }

    private function ejecutarBusquedaDetalleCompraPorIdParaEditar($idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT 
                            dc.*,
                            p.nombre as producto_nombre,
                            p.idcategoria,
                            m.codigo_moneda,
                            cat.nombre as categoria_nombre
                        FROM detalle_compra dc
                        LEFT JOIN producto p ON dc.idproducto = p.idproducto
                        LEFT JOIN monedas m ON dc.idmoneda_detalle = m.idmoneda
                        LEFT JOIN categoria cat ON p.idcategoria = cat.idcategoria
                        WHERE dc.idcompra = ?");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaDetalleCompraPorIdParaEditar - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaCompraPorId($idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $codigoMonedaEuro = 'EUR';
            $codigoMonedaDolar = 'USD';

            $this->setQuery("SELECT 
                        c.*,
                        CONCAT(p.nombre, ' ', COALESCE(p.apellido, '')) as proveedor_nombre,
                        m.codigo_moneda AS moneda_general_compra_codigo,
                        (SELECT ht_eur.tasa_a_bs
                        FROM historial_tasas_bcv ht_eur
                        WHERE ht_eur.codigo_moneda = ?
                        AND DATE(ht_eur.fecha_publicacion_bcv) <= DATE(c.fecha)
                        ORDER BY ht_eur.fecha_publicacion_bcv DESC
                        LIMIT 1) AS tasa_eur_ves,
                        (SELECT ht_usd.tasa_a_bs
                        FROM historial_tasas_bcv ht_usd
                        WHERE ht_usd.codigo_moneda = ?
                        AND DATE(ht_usd.fecha_publicacion_bcv) <= DATE(c.fecha)
                        ORDER BY ht_usd.fecha_publicacion_bcv DESC
                        LIMIT 1) AS tasa_usd_ves
                    FROM compra c 
                    LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor
                    LEFT JOIN monedas m ON c.idmoneda_general = m.idmoneda
                    WHERE c.idcompra = ?");
            
            $this->setArray([$codigoMonedaEuro, $codigoMonedaDolar, $idcompra]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaCompraPorId - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaDetalleCompraPorId($idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT 
                        dc.*,
                        p.nombre as producto_nombre,
                        m.codigo_moneda
                    FROM detalle_compra dc
                    LEFT JOIN producto p ON dc.idproducto = p.idproducto
                    LEFT JOIN monedas m ON dc.idmoneda_detalle = m.idmoneda
                    WHERE dc.idcompra = ?");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaDetalleCompraPorId - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarEliminacionLogicaCompra(int $idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();
            
            $this->setQuery("UPDATE compra SET estatus_compra = 'inactivo' WHERE idcompra = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            
            $db->commit();
            
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("ComprasModel::ejecutarEliminacionLogicaCompra - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarCambioEstadoCompra(int $idcompra, string $nuevoEstado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $estadosValidos = ['BORRADOR', 'POR_AUTORIZAR', 'AUTORIZADA', 'POR_PAGAR', 'PAGADA'];
            
            if (!in_array($nuevoEstado, $estadosValidos)) {
                return [
                    'status' => false,
                    'message' => 'Estado no válido.'
                ];
            }

            $this->setQuery("SELECT estatus_compra FROM compra WHERE idcompra = ?");
            $stmtGet = $db->prepare($this->getQuery());
            $stmtGet->execute([$idcompra]);
            $compra = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                return [
                    'status' => false,
                    'message' => 'Compra no encontrada.'
                ];
            }

            $estadoActual = $compra['estatus_compra'];

            if (!$this->validarTransicionEstado($estadoActual, $nuevoEstado)) {
                return [
                    'status' => false,
                    'message' => 'Transición de estado no válida.'
                ];
            }

            $this->setQuery("UPDATE compra SET estatus_compra = ?, fecha_modificacion = NOW() WHERE idcompra = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$nuevoEstado, $idcompra]);

            if ($nuevoEstado === 'PAGADA') {
                $this->ejecutarGeneracionNotaEntrega($idcompra, $db);
                
                // Limpiar notificaciones de compras cuando se marca como pagada
                try {
                    $notificacionesModel = new NotificacionesModel();
                    $notificacionesModel->limpiarNotificacionesCompraPagada($idcompra);
                    error_log("ComprasModel: Notificaciones de compra limpiadas para compra ID: {$idcompra}");
                } catch (Exception $e) {
                    error_log("ComprasModel: Error al limpiar notificaciones de compra ID {$idcompra}: " . $e->getMessage());
                }
            }

            return [
                'status' => true,
                'message' => 'Estado de compra actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarCambioEstadoCompra - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al cambiar estado: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionProveedor(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("INSERT INTO proveedor (nombre, apellido, identificacion, telefono_principal, correo_electronico, direccion, fecha_nacimiento, genero, observaciones, estatus, fecha_cracion, fecha_modificacion) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW(), NOW())");
            
            $this->setArray([
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['telefono_principal'],
                $data['correo_electronico'],
                $data['direccion'],
                $data['fecha_nacimiento'],
                $data['genero'],
                $data['observaciones']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $insertExitoso = $stmt->execute($this->getArray());
            
            if ($insertExitoso) {
                $idProveedor = $db->lastInsertId();
                return [
                    'status' => true,
                    'message' => 'Proveedor registrado exitosamente.',
                    'proveedor_id' => $idProveedor
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Error al registrar proveedor.'
                ];
            }

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarInsercionProveedor - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al registrar proveedor: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaProveedorPorId($idproveedor)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT * FROM proveedor WHERE idproveedor = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idproveedor]);
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarBusquedaProveedorPorId - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function validarTransicionEstado($estadoActual, $nuevoEstado): bool
    {
        $transicionesValidas = [
            'BORRADOR' => ['POR_AUTORIZAR'],
            'POR_AUTORIZAR' => ['AUTORIZADA', 'BORRADOR'],
            'AUTORIZADA' => ['POR_PAGAR'],
            'POR_PAGAR' => ['PAGADA', 'AUTORIZADA'],
            'PAGADA' => [] 
        ];

        return isset($transicionesValidas[$estadoActual]) && 
               in_array($nuevoEstado, $transicionesValidas[$estadoActual]);
    }

    private function ejecutarGeneracionNotaEntrega($idcompra, $db)
    {
        try {
            $year = date("Y");
            $this->setQuery("SELECT MAX(CAST(SUBSTRING_INDEX(numero_nota, '-', -1) AS UNSIGNED)) as max_num
                           FROM notas_entrega WHERE numero_nota LIKE ?");
            $stmtNum = $db->prepare($this->getQuery());
            $stmtNum->execute(["NE-" . $year . "-%"]);
            $result = $stmtNum->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result && isset($result['max_num'])) ? intval($result['max_num']) + 1 : 1;
            $numeroNota = "NE-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);

            $this->setQuery("INSERT INTO notas_entrega (numero_nota, idcompra, fecha_creacion, estado) 
                        VALUES (?, ?, NOW(), 'PENDIENTE')");
            $stmtNota = $db->prepare($this->getQuery());
            $stmtNota->execute([$numeroNota, $idcompra]);

            return $numeroNota;
        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarGeneracionNotaEntrega - Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function ejecutarGuardarPesoRomana($peso, $fecha = null, $estatus = 'activo')
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("INSERT INTO historial_romana 
            (peso, fecha, estatus, fecha_creacion) 
            VALUES (?, ?, ?, NOW())");

            $this->setArray([
                $peso,
                $fecha ?? date('Y-m-d H:i:s'),
                $estatus
            ]);

            $stmt = $db->prepare($this->getQuery());
            $insertExitoso = $stmt->execute($this->getArray());

            if ($insertExitoso) {
                $idRomana = $db->lastInsertId();
                return [
                    'status' => true,
                    'message' => 'Peso registrado exitosamente.',
                    'idromana' => $idRomana
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Error al registrar el peso.'
                ];
            }

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarGuardarPesoRomana - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al registrar peso: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }
    private function ejecutarBusquedaPermisosUsuarioModulo($idusuario, $modulo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("
                SELECT 
                    u.idusuario,
                    u.usuario,
                    u.idrol,
                    r.nombre as rol_nombre,
                    m.titulo as modulo_nombre,
                    p.idpermiso,
                    p.nombre_permiso,
                    rmp.activo,
                    m.estatus as modulo_estatus
                FROM usuario u
                INNER JOIN roles r ON u.idrol = r.idrol
                INNER JOIN rol_modulo_permisos rmp ON r.idrol = rmp.idrol
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE u.idusuario = ? 
                AND LOWER(m.titulo) = LOWER(?)
                AND rmp.activo = 1
            ");
            
            $this->setArray([$idusuario, $modulo]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarBusquedaPermisosUsuarioModulo - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaTodosPermisosRol($idrol)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("
                SELECT 
                    m.titulo as modulo,
                    p.nombre_permiso as permiso,
                    rmp.activo
                FROM rol_modulo_permisos rmp
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE rmp.idrol = ?
                AND rmp.activo = 1
                ORDER BY m.titulo, p.idpermiso
            ");
            
            $this->setArray([$idrol]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarBusquedaTodosPermisosRol - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaEstadoCompra($idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT estatus_compra FROM compra WHERE idcompra = ?");
            $this->setArray([$idcompra]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['estatus_compra'] : null;
            
        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarBusquedaEstadoCompra - Error: " . $e->getMessage());
            return null;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaCompraCompleta($idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT 
                        c.idcompra,
                        c.nro_compra,
                        c.fecha,
                        c.total_general,
                        c.observaciones_compra,
                        c.estatus_compra,
                        p.nombre as nombrePersona,
                        p.apellido as apellidoPersona,
                        p.identificacion as personaId,
                        p.direccion,
                        p.telefono_principal as telefono,
                        p.correo_electronico as email
                    FROM compra c
                    LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor  
                    WHERE c.idcompra = ?");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                return [];
            }

            $this->setQuery("SELECT 
                        dc.cantidad,
                        dc.precio_unitario_compra as precio,
                        dc.subtotal_linea,
                        dc.idproducto as productoId,
                        COALESCE(p.nombre, dc.descripcion_temporal_producto) as nombreProducto,
                        p.descripcion as modelo,
                        '' as color,
                        '' as capacidad
                    FROM detalle_compra dc
                    LEFT JOIN producto p ON dc.idproducto = p.idproducto
                    WHERE dc.idcompra = ?");
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idcompra]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'solicitud' => $compra,
                'detalles' => $detalles,
                'pago' => [] 
            ];

        } catch (PDOException $e) {
            error_log("ComprasModel::ejecutarBusquedaCompraCompleta - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarReactivacionCompra(int $idcompra)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar que la compra existe
            $this->setQuery("SELECT idcompra, estatus_compra FROM compra WHERE idcompra = ?");
            $this->setArray([$idcompra]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$compra) {
                return [
                    'status' => false,
                    'message' => 'Compra no encontrada'
                ];
            }
            
            // Verificar si la compra está inactiva (el estado debe ser exactamente "inactivo")
            if ($compra['estatus_compra'] !== 'inactivo') {
                return [
                    'status' => false,
                    'message' => 'La compra no está inactiva'
                ];
            }
            
            // Reactivar compra (cambiar a BORRADOR)
            $this->setQuery("UPDATE compra SET estatus_compra = 'BORRADOR', fecha_modificacion = NOW() WHERE idcompra = ?");
            $this->setArray([$idcompra]);
            
            $stmt = $db->prepare($this->getQuery());
            $resultado = $stmt->execute($this->getArray());
            
            if ($resultado && $stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'message' => 'Compra reactivada exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo reactivar la compra'
                ];
            }
            
        } catch (Exception $e) {
            error_log("ComprasModel::ejecutarReactivacionCompra - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al reactivar compra: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }


    // MÉTODOS PÚBLICOS QUE SE LLAMAN EN EL CONTROLADOR
    public function selectAllCompras(int $idUsuarioSesion = 0)
    {
        return $this->ejecutarConsultaTodasCompras($idUsuarioSesion);
    }

    public function generarNumeroCompra()
    {
        return $this->ejecutarGeneracionNumeroCompra();
    }

    public function buscarProveedor($termino)
    {
        $this->setTermino($termino);
        return $this->ejecutarBusquedaProveedor();
    }

    public function getProductosConCategoria()
    {
        return $this->ejecutarConsultaProductosConCategoria();
    }

    public function getProductoById(int $idproducto)
    {
        $this->setIdProducto($idproducto);
        return $this->ejecutarBusquedaProductoPorId();
    }

    public function getMonedasActivas()
    {
        return $this->ejecutarConsultaMonedasActivas();
    }

    public function getTasasPorFecha($fecha)
    {
        $this->setFecha($fecha);
        return $this->ejecutarConsultaTasasPorFecha();
    }

    public function getUltimoPesoRomana()
    {
        return $this->ejecutarConsultaUltimoPesoRomana();
    }

    public function insertarCompra(array $datosCompra, array $detallesCompra)
    {
        return $this->ejecutarInsercionCompra($datosCompra, $detallesCompra);
    }

    public function actualizarCompra(int $idcompra, array $datosCompra, array $detallesCompra)
    {
        return $this->ejecutarActualizacionCompra($idcompra, $datosCompra, $detallesCompra);
    }

    public function getCompraCompletaParaEditar($idcompra)
    {
        return $this->ejecutarBusquedaCompraCompletaParaEditar($idcompra);
    }

    public function getCompraById($idcompra)
    {
        return $this->ejecutarBusquedaCompraPorId($idcompra);
    }

    public function getDetalleCompraById($idcompra)
    {
        return $this->ejecutarBusquedaDetalleCompraPorId($idcompra);
    }

    public function deleteCompraById(int $idcompra)
    {
        return $this->ejecutarEliminacionLogicaCompra($idcompra);
    }

    public function cambiarEstadoCompra(int $idcompra, string $nuevoEstado)
    {
        return $this->ejecutarCambioEstadoCompra($idcompra, $nuevoEstado);
    }

    public function insertProveedor(array $data): array
    {
        return $this->ejecutarInsercionProveedor($data);
    }

    public function getProveedorById($idproveedor)
    {
        return $this->ejecutarBusquedaProveedorPorId($idproveedor);
    }

    public function guardarPesoRomana($peso, $fecha = null, $estatus = 'activo')
    {
        return $this->ejecutarGuardarPesoRomana($peso, $fecha, $estatus);
    }

    public function obtenerPermisosUsuarioModulo($idusuario, $modulo)
    {
        return $this->ejecutarBusquedaPermisosUsuarioModulo($idusuario, $modulo);
    }

    public function obtenerTodosPermisosRol($idrol)
    {
        return $this->ejecutarBusquedaTodosPermisosRol($idrol);
    }

    public function selectCompra($idcompra)
    {
        return $this->ejecutarBusquedaCompraCompleta($idcompra);
    }

    public function obtenerEstadoCompra($idcompra)
    {
        return $this->ejecutarBusquedaEstadoCompra($idcompra);
    }

    public function reactivarCompra(int $idcompra)
    {
        return $this->ejecutarReactivacionCompra($idcompra);
    }
}
?>