<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class MovimientosModel extends Mysql
{
    //  PROPIEDADES PRIVADAS ENCAPSULADAS (mantener igual)
    private $query;
    private $array;
    private $data;
    private $result;
    private $movimientoId;
    private $message;
    private $status;

    //  PROPIEDADES ESPECÍFICAS DE MOVIMIENTO (mantener igual)
    private $idmovimiento;
    private $numero_movimiento;
    private $idproducto;
    private $idtipomovimiento;
    private $idcompra;
    private $idventa;
    private $idproduccion;
    private $cantidad_entrada;
    private $cantidad_salida;
    private $stock_anterior;
    private $stock_resultante;
    private $total;
    private $entrada;
    private $salida;
    private $observaciones;
    private $estatus;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        // Constructor vacío
    }

    //  MANTENER TODOS LOS GETTERS Y SETTERS IGUAL...
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

    public function getMovimientoId(){
        return $this->movimientoId;
    }

    public function setMovimientoId(?int $movimientoId){
        $this->movimientoId = $movimientoId;
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

    //  GETTERS Y SETTERS ESPECÍFICOS (mantener igual)
    public function getIdmovimiento() { return $this->idmovimiento; }
    public function setIdmovimiento($idmovimiento) { 
        $this->idmovimiento = filter_var($idmovimiento, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getNumeroMovimiento() { return $this->numero_movimiento; }
    public function setNumeroMovimiento($numero_movimiento) { 
        $this->numero_movimiento = trim($numero_movimiento);
        return $this;
    }

    public function getIdproducto() { return $this->idproducto; }
    public function setIdproducto($idproducto) { 
        $this->idproducto = filter_var($idproducto, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getIdtipomovimiento() { return $this->idtipomovimiento; }
    public function setIdtipomovimiento($idtipomovimiento) { 
        $this->idtipomovimiento = filter_var($idtipomovimiento, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getIdcompra() { return $this->idcompra; }
    public function setIdcompra($idcompra) { 
        $this->idcompra = $idcompra ? filter_var($idcompra, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getIdventa() { return $this->idventa; }
    public function setIdventa($idventa) { 
        $this->idventa = $idventa ? filter_var($idventa, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getIdproduccion() { return $this->idproduccion; }
    public function setIdproduccion($idproduccion) { 
        $this->idproduccion = $idproduccion ? filter_var($idproduccion, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getCantidadEntrada() { return $this->cantidad_entrada; }
    public function setCantidadEntrada($cantidad_entrada) { 
        $this->cantidad_entrada = $cantidad_entrada ? filter_var($cantidad_entrada, FILTER_VALIDATE_FLOAT) : null;
        return $this;
    }

    public function getCantidadSalida() { return $this->cantidad_salida; }
    public function setCantidadSalida($cantidad_salida) { 
        $this->cantidad_salida = $cantidad_salida ? filter_var($cantidad_salida, FILTER_VALIDATE_FLOAT) : null;
        return $this;
    }

    public function getStockAnterior() { return $this->stock_anterior; }
    public function setStockAnterior($stock_anterior) { 
        $this->stock_anterior = filter_var($stock_anterior, FILTER_VALIDATE_FLOAT);
        return $this;
    }

    public function getStockResultante() { return $this->stock_resultante; }
    public function setStockResultante($stock_resultante) { 
        $this->stock_resultante = filter_var($stock_resultante, FILTER_VALIDATE_FLOAT);
        return $this;
    }

    public function getObservaciones() { return $this->observaciones; }
    public function setObservaciones($observaciones) { 
        $this->observaciones = trim($observaciones);
        return $this;
    }

    public function getEstatusMovimiento() { return $this->estatus; }
    public function setEstatusMovimiento($estatus) { 
        $estatusValidos = ['activo', 'inactivo', 'eliminado'];
        $this->estatus = in_array($estatus, $estatusValidos) ? $estatus : 'activo';
        return $this;
    }

    //  FUNCIONES PRIVADAS CORREGIDAS

    /**
     * Función privada para obtener todos los movimientos
     */
    private function ejecutarBusquedaTodosMovimientos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, m.entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, m.salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    COALESCE(m.fecha_creacion, NOW()) as fecha_creacion,
                    COALESCE(m.fecha_modificacion, NOW()) as fecha_modificacion,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    COALESCE(tm.descripcion, '') AS tipo_descripcion,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato,
                    DATE_FORMAT(COALESCE(m.fecha_modificacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_modificacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.estatus != 'eliminado'
                ORDER BY COALESCE(m.fecha_creacion, m.idmovimiento) DESC"
            );
            
            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($movimientos) {
                $resultado = [
                    'status' => true,
                    'message' => 'Movimientos obtenidos correctamente.',
                    'data' => $movimientos
                ];
            } else {
                $resultado = [
                    'status' => false,
                    'message' => 'No hay movimientos disponibles.',
                    'data' => []
                ];
            }
            
        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaTodosMovimientos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar movimiento por ID
     */
    private function ejecutarBusquedaMovimientoPorId(int $idmovimiento){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, m.entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, m.salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    COALESCE(m.fecha_creacion, NOW()) as fecha_creacion,
                    COALESCE(m.fecha_modificacion, NOW()) as fecha_modificacion,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    COALESCE(tm.descripcion, '') AS tipo_descripcion,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato,
                    DATE_FORMAT(COALESCE(m.fecha_modificacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_modificacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.idmovimiento = ? AND m.estatus != 'eliminado'"
            );
            
            $this->setArray([$idmovimiento]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $resultado = $movimiento ? [
                'status' => true,
                'message' => 'Movimiento obtenido correctamente.',
                'data' => $movimiento
            ] : [
                'status' => false,
                'message' => 'Movimiento no encontrado.',
                'data' => null
            ];
            
        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaMovimientoPorId - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para insertar movimiento
     */
    private function ejecutarInsercionMovimiento(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            //  GENERAR NÚMERO DE MOVIMIENTO
            $numeroMovimiento = $this->generarNumeroMovimiento($db);

            //  INSERCIÓN CON ESTRUCTURA OPTIMIZADA
            $this->setQuery(
                "INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, idcompra, idventa, idproduccion,
                 cantidad_entrada, cantidad_salida, stock_anterior, stock_resultante, observaciones, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')"
            );
            
            //  DATOS OPTIMIZADOS
            $cantidadEntrada = floatval($data['cantidad_entrada'] ?? 0);
            $cantidadSalida = floatval($data['cantidad_salida'] ?? 0);
            $stockAnterior = floatval($data['stock_anterior'] ?? 0);
            $stockResultante = floatval($data['stock_resultante'] ?? 0);
            
            $this->setArray([
                $numeroMovimiento,
                $data['idproducto'],
                $data['idtipomovimiento'],
                $data['idcompra'] ?? null,
                $data['idventa'] ?? null,
                $data['idproduccion'] ?? null,
                $cantidadEntrada,
                $cantidadSalida,
                $stockAnterior,
                $stockResultante,
                $data['observaciones'] ?? ''
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setMovimientoId($db->lastInsertId());
            
            if ($this->getMovimientoId()) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Movimiento registrado correctamente.');
                
                $resultado = [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage(),
                    'data' => [
                        'idmovimiento' => $this->getMovimientoId(),
                        'numero_movimiento' => $numeroMovimiento
                    ]
                ];
            } else {
                $db->rollBack();
                $resultado = [
                    'status' => false,
                    'message' => 'Error al obtener ID de movimiento tras registro.',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarInsercionMovimiento - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al registrar movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para actualizar movimiento
     */
    private function ejecutarActualizacionMovimiento(int $idmovimiento, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            //  ACTUALIZACIÓN CON ESTRUCTURA OPTIMIZADA
            $this->setQuery(
                "UPDATE movimientos_existencia 
                SET idproducto = ?, idtipomovimiento = ?, idcompra = ?, idventa = ?, idproduccion = ?,
                    cantidad_entrada = ?, cantidad_salida = ?, stock_anterior = ?, stock_resultante = ?, 
                    observaciones = ?, estatus = ?, fecha_modificacion = NOW()
                WHERE idmovimiento = ?"
            );
            
            $cantidadEntrada = floatval($data['cantidad_entrada'] ?? 0);
            $cantidadSalida = floatval($data['cantidad_salida'] ?? 0);
            $stockAnterior = floatval($data['stock_anterior'] ?? 0);
            $stockResultante = floatval($data['stock_resultante'] ?? 0);
            
            $this->setArray([
                $data['idproducto'],
                $data['idtipomovimiento'],
                $data['idcompra'] ?? null,
                $data['idventa'] ?? null,
                $data['idproduccion'] ?? null,
                $cantidadEntrada,
                $cantidadSalida,
                $stockAnterior,
                $stockResultante,
                $data['observaciones'] ?? '',
                $data['estatus'] ?? 'activo',
                $idmovimiento
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $db->commit();
                $resultado = [
                    'status' => true,
                    'message' => 'Movimiento actualizado correctamente.',
                    'data' => ['idmovimiento' => $idmovimiento]
                ];
            } else {
                $db->commit();
                $resultado = [
                    'status' => true,
                    'message' => 'No se realizaron cambios en el movimiento (datos idénticos).',
                    'data' => ['idmovimiento' => $idmovimiento]
                ];
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarActualizacionMovimiento - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al actualizar movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para eliminar (desactivar) movimiento
     */
    private function ejecutarEliminacionMovimiento(int $idmovimiento){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();
            
            $this->setQuery("UPDATE movimientos_existencia SET estatus = 'eliminado' WHERE idmovimiento = ?");
            $this->setArray([$idmovimiento]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $db->commit();
                $resultado = true;
            } else {
                $db->rollBack();
                $resultado = false;
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarEliminacionMovimiento - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener productos activos
     */
    private function ejecutarBusquedaProductosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            //  CONSULTA SIMPLIFICADA SIN COLUMNAS QUE NO EXISTEN
            $this->setQuery("SELECT idproducto, nombre, COALESCE(stock_actual, 0) as stock_actual FROM producto WHERE estatus = 'activo' ORDER BY nombre ASC");
            $this->setArray([]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resultado = [
                'status' => true,
                'message' => 'Productos activos obtenidos.',
                'data' => $productos
            ];
            
        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaProductosActivos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener tipos de movimiento activos
     */
    private function ejecutarBusquedaTiposMovimientoActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT idtipomovimiento, nombre, COALESCE(descripcion, '') as descripcion FROM tipo_movimiento WHERE estatus = 'activo' ORDER BY nombre ASC");
            $this->setArray([]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resultado = [
                'status' => true,
                'message' => 'Tipos de movimiento activos obtenidos.',
                'data' => $tipos
            ];
            
        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaTiposMovimientoActivos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener tipos de movimiento: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar movimientos por criterio
     */
    private function ejecutarBusquedaMovimientosPorCriterio(string $criterio){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, m.entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, m.salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.estatus != 'eliminado' 
                AND (COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) LIKE ? 
                     OR p.nombre LIKE ? 
                     OR tm.nombre LIKE ? 
                     OR COALESCE(m.observaciones, '') LIKE ?)
                ORDER BY COALESCE(m.fecha_creacion, m.idmovimiento) DESC"
            );

            $criterioLike = '%' . $criterio . '%';
            $this->setArray([$criterioLike, $criterioLike, $criterioLike, $criterioLike]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($movimientos) {
                $resultado = [
                    'status' => true,
                    'message' => 'Búsqueda completada.',
                    'data' => $movimientos
                ];
            } else {
                $resultado = [
                    'status' => false,
                    'message' => 'No se encontraron movimientos con el criterio especificado.',
                    'data' => []
                ];
            }
            
        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaMovimientosPorCriterio - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para validar datos de movimiento
     */
    private function validarDatosMovimiento(array $data){
        //  CAMPOS OBLIGATORIOS
        if (empty($data['idproducto'])) {
            return ['valido' => false, 'mensaje' => 'El producto es obligatorio.'];
        }

        if (empty($data['idtipomovimiento'])) {
            return ['valido' => false, 'mensaje' => 'El tipo de movimiento es obligatorio.'];
        }

        //  VALIDAR QUE TENGA AL MENOS UNA CANTIDAD
        $cantidadEntrada = floatval($data['cantidad_entrada'] ?? 0);
        $cantidadSalida = floatval($data['cantidad_salida'] ?? 0);
        
        if ($cantidadEntrada <= 0 && $cantidadSalida <= 0) {
            return ['valido' => false, 'mensaje' => 'Debe especificar al menos una cantidad (entrada o salida).'];
        }

        //  VALIDAR QUE NO TENGA AMBAS CANTIDADES
        if ($cantidadEntrada > 0 && $cantidadSalida > 0) {
            return ['valido' => false, 'mensaje' => 'No puede tener cantidad de entrada y salida al mismo tiempo.'];
        }

        return ['valido' => true, 'mensaje' => 'Datos válidos.'];
    }

    /**
     * Función privada para generar número único de movimiento
     */
    private function generarNumeroMovimiento($db){
        $prefijo = 'MOV-';
        $fecha = date('Ymd');
        
        try {
            $query = "SELECT COALESCE(numero_movimiento, CONCAT('MOV-', idmovimiento)) as numero_movimiento 
                      FROM movimientos_existencia 
                      WHERE COALESCE(numero_movimiento, CONCAT('MOV-', idmovimiento)) LIKE ? 
                      ORDER BY idmovimiento DESC LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$prefijo . $fecha . '-%']);
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ultimo) {
                $partes = explode('-', $ultimo['numero_movimiento']);
                $consecutivo = intval(end($partes)) + 1;
            } else {
                $consecutivo = 1;
            }
            
            return $prefijo . $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            // Si hay error, generar número simple
            return $prefijo . $fecha . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    //  MÉTODOS PÚBLICOS (mantener igual)

    /**
     * Insertar nuevo movimiento
     */
    public function insertMovimiento(array $data){
        $this->setData($data);
        
        //  VALIDAR DATOS USANDO FUNCIÓN PRIVADA
        $validacion = $this->validarDatosMovimiento($this->getData());
        if (!$validacion['valido']) {
            return [
                'status' => false,
                'message' => $validacion['mensaje'],
                'data' => null
            ];
        }

        return $this->ejecutarInsercionMovimiento($this->getData());
    }

    /**
     * Actualizar movimiento existente
     */
    public function updateMovimiento(int $idmovimiento, array $data){
        $this->setData($data);
        $this->setMovimientoId($idmovimiento);
        
        //  VALIDAR DATOS USANDO FUNCIÓN PRIVADA
        $validacion = $this->validarDatosMovimiento($this->getData());
        if (!$validacion['valido']) {
            return [
                'status' => false,
                'message' => $validacion['mensaje'],
                'data' => null
            ];
        }

        return $this->ejecutarActualizacionMovimiento($this->getMovimientoId(), $this->getData());
    }

    /**
     * Obtener movimiento por ID
     */
    public function selectMovimientoById(int $idmovimiento){
        $this->setMovimientoId($idmovimiento);
        
        if (!$this->getMovimientoId()) {
            return [
                'status' => false,
                'message' => 'ID de movimiento inválido.',
                'data' => null
            ];
        }

        return $this->ejecutarBusquedaMovimientoPorId($this->getMovimientoId());
    }

    /**
     * Eliminar movimiento por ID
     */
    public function deleteMovimientoById(int $idmovimiento){
        $this->setMovimientoId($idmovimiento);
        
        if (!$this->getMovimientoId()) {
            return [
                'status' => false,
                'message' => 'ID de movimiento inválido.',
                'data' => null
            ];
        }

        $resultado = $this->ejecutarEliminacionMovimiento($this->getMovimientoId());
        
        return [
            'status' => $resultado,
            'message' => $resultado ? 'Movimiento eliminado correctamente.' : 'No se pudo eliminar el movimiento.',
            'data' => $resultado ? ['idmovimiento' => $this->getMovimientoId()] : null
        ];
    }

    /**
     * Obtener todos los movimientos
     */
    public function selectAllMovimientos(){
        return $this->ejecutarBusquedaTodosMovimientos();
    }

    /**
     * Buscar movimientos por criterio
     */
    public function buscarMovimientos(string $criterio){
        if (empty(trim($criterio))) {
            return $this->selectAllMovimientos();
        }

        return $this->ejecutarBusquedaMovimientosPorCriterio($criterio);
    }

    /**
     * Obtener productos activos
     */
    public function getProductosActivos(){
        return $this->ejecutarBusquedaProductosActivos();
    }

    /**
     * Obtener tipos de movimiento activos
     */
    public function getTiposMovimientoActivos(){
        return $this->ejecutarBusquedaTiposMovimientoActivos();
    }

    /**
     * Obtener tipos de movimiento para filtros (incluyendo estadísticas)
     */
    public function getTiposMovimientoConEstadisticas(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            //  OBTENER TIPOS CON CONTEO DE MOVIMIENTOS
            $this->setQuery(
                "SELECT 
                    tm.idtipomovimiento,
                    tm.nombre,
                    COALESCE(tm.descripcion, '') as descripcion,
                    tm.estatus,
                    COUNT(m.idmovimiento) as total_movimientos,
                    SUM(CASE WHEN m.entrada > 0 OR m.cantidad_entrada > 0 THEN 1 ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN m.salida > 0 OR m.cantidad_salida > 0 THEN 1 ELSE 0 END) as total_salidas
                FROM tipo_movimiento tm
                LEFT JOIN movimientos_existencia m ON tm.idtipomovimiento = m.idtipomovimiento 
                    AND m.estatus != 'eliminado'
                WHERE tm.estatus = 'activo'
                GROUP BY tm.idtipomovimiento, tm.nombre, tm.descripcion, tm.estatus
                ORDER BY tm.nombre ASC"
            );
            
            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resultado = [
                'status' => true,
                'message' => 'Tipos de movimiento con estadísticas obtenidos.',
                'data' => $tipos
            ];
            
        } catch (Exception $e) {
            error_log("MovimientosModel::getTiposMovimientoConEstadisticas - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener tipos de movimiento: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }
}
?>