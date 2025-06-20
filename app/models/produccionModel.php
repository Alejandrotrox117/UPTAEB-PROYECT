<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";

class ProduccionModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $produccionId;
    private $message;
    private $status;

    // Propiedades específicas del modelo
    private $idproduccion;
    private $idempleado;
    private $idproducto;
    private $cantidad_a_realizar;
    private $fecha_inicio;
    private $fecha_fin;
    private $estado;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        
    }

    // === GETTERS Y SETTERS GENERALES ===
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

    public function getProduccionId(){
        return $this->produccionId;
    }

    public function setProduccionId(?int $produccionId){
        $this->produccionId = $produccionId;
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

    // === GETTERS Y SETTERS ESPECÍFICOS ===
    public function getIdProduccion(){
        return $this->idproduccion;
    }

    public function setIdProduccion($id){
        $this->idproduccion = $id;
    }

    public function getIdEmpleado(){
        return $this->idempleado;
    }

    public function setIdEmpleado($id){
        $this->idempleado = $id;
    }

    public function getIdProducto(){
        return $this->idproducto;
    }

    public function setIdProducto($id){
        $this->idproducto = $id;
    }

    public function getCantidadARealizar(){
        return $this->cantidad_a_realizar;
    }

    public function setCantidadARealizar($cant){
        $this->cantidad_a_realizar = $cant;
    }

    public function getFechaInicio(){
        return $this->fecha_inicio;
    }

    public function setFechaInicio($fecha){
        $this->fecha_inicio = $fecha;
    }

    public function getFechaFin(){
        return $this->fecha_fin;
    }

    public function setFechaFin($fecha){
        $this->fecha_fin = $fecha;
    }

    public function getEstado(){
        return $this->estado;
    }

    public function setEstado($estado){
        $this->estado = $estado;
    }

    // === MÉTODOS PRIVADOS PARA OPERACIONES DE BASE DE DATOS ===

    private function ejecutarInsercionProduccion(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Insertar producción principal
            $this->setQuery(
                "INSERT INTO produccion (
                    idempleado, idproducto, cantidad_a_realizar, fecha_inicio, 
                    fecha_fin, estado, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );

            $this->setArray([
                $data['idempleado'],
                $data['idproducto'],
                $data['cantidad_a_realizar'],
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                $data['estado']
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setProduccionId($db->lastInsertId());

            // Insertar detalles de insumos si existen
            if (!empty($data['insumos']) && is_array($data['insumos'])) {
                foreach ($data['insumos'] as $insumo) {
                    $resultadoDetalle = $this->ejecutarInsercionDetalleProduccion($this->getProduccionId(), $insumo, $db);
                    if (!$resultadoDetalle) {
                        throw new Exception("Error al insertar detalle de producción");
                    }
                }
            }

            $db->commit();

            if ($this->getProduccionId()) {
                $this->setStatus(true);
                $this->setMessage('Producción registrada exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de producción tras registro.');
            }

            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'produccion_id' => $this->getProduccionId()
            ];

        } catch (Exception $e) {
            $db->rollBack();
            $conexion->disconnect();
            error_log("Error al insertar producción: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar producción: ' . $e->getMessage(),
                'produccion_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarInsercionDetalleProduccion(int $idproduccion, array $insumo, $db): bool
    {
        try {
            $this->setQuery(
                "INSERT INTO detalle_produccion (
                    idproduccion, idproducto, cantidad, cantidad_consumida, 
                    observaciones, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, NOW())"
            );

            $this->setArray([
                $idproduccion,
                $insumo['idproducto'],
                $insumo['cantidad'],
                $insumo['cantidad_utilizada'] ?? 0,
                $insumo['observaciones'] ?? ''
            ]);

            $stmt = $db->prepare($this->getQuery());
            return $stmt->execute($this->getArray());

        } catch (Exception $e) {
            error_log("Error al insertar detalle producción: " . $e->getMessage());
            return false;
        }
    }

    private function ejecutarActualizacionProduccion(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $this->setQuery(
                "UPDATE produccion SET 
                    idempleado = ?, idproducto = ?, cantidad_a_realizar = ?, 
                    fecha_inicio = ?, fecha_fin = ?, estado = ?, 
                    fecha_modificacion = NOW() 
                WHERE idproduccion = ?"
            );

            $this->setArray([
                $data['idempleado'],
                $data['idproducto'],
                $data['cantidad_a_realizar'],
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                $data['estado'],
                $data['idproduccion']
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();

            // Actualizar detalles si se proporcionan
            if (!empty($data['insumos']) && is_array($data['insumos'])) {
                $this->ejecutarEliminacionDetalleProduccion($data['idproduccion'], $db);
                foreach ($data['insumos'] as $insumo) {
                    $this->ejecutarInsercionDetalleProduccion($data['idproduccion'], $insumo, $db);
                }
            }

            $db->commit();

            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Producción actualizada exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar la producción o no se realizaron cambios.');
            }

            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];

        } catch (Exception $e) {
            $db->rollBack();
            $conexion->disconnect();
            error_log("Error al actualizar producción: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar producción: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaProduccionPorId(int $idproduccion){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.*, 
                    prod.nombre AS nombre_producto,
                    e.nombre AS nombre_empleado,
                    DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y') as fecha_inicio_formato,
                    DATE_FORMAT(p.fecha_fin, '%d/%m/%Y') as fecha_fin_formato,
                    DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(p.fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                FROM produccion p
                JOIN producto prod ON p.idproducto = prod.idproducto
                JOIN empleado e ON p.idempleado = e.idempleado
                WHERE p.idproduccion = ?"
            );

            $this->setArray([$idproduccion]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $resultado = $this->getResult();

        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("ProduccionModel::ejecutarBusquedaProduccionPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTodasProducciones(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idproduccion, 
                    pr.nombre AS nombre_producto, 
                    e.nombre AS nombre_empleado, 
                    p.cantidad_a_realizar, 
                    DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y') as fecha_inicio,
                    DATE_FORMAT(p.fecha_fin, '%d/%m/%Y') as fecha_fin,
                    p.estado 
                FROM produccion p
                INNER JOIN producto pr ON p.idproducto = pr.idproducto
                INNER JOIN empleado e ON p.idempleado = e.idempleado
                WHERE p.estado != 'inactivo'
                ORDER BY p.fecha_creacion DESC"
            );

            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Producciones obtenidas.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaTodasProducciones - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener producciones: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaDetalleProduccion(int $idproduccion){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    dp.iddetalle_produccion,
                    dp.idproducto,
                    p.nombre AS nombre_producto,
                    p.unidad_medida,
                    dp.cantidad,
                    dp.cantidad_consumida,
                    dp.observaciones
                FROM detalle_produccion dp
                INNER JOIN producto p ON dp.idproducto = p.idproducto
                WHERE dp.idproduccion = ?"
            );

            $this->setArray([$idproduccion]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Detalle de producción obtenido.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaDetalleProduccion - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener detalle: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminacionProduccion(int $idproduccion){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE produccion SET estado = 'inactivo', fecha_modificacion = NOW() WHERE idproduccion = ?");
            $this->setArray([$idproduccion]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarEliminacionProduccion -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminacionDetalleProduccion(int $idproduccion, $db): bool
    {
        try {
            $this->setQuery("DELETE FROM detalle_produccion WHERE idproduccion = ?");
            $this->setArray([$idproduccion]);
            $stmt = $db->prepare($this->getQuery());
            return $stmt->execute($this->getArray());
        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarEliminacionDetalleProduccion -> " . $e->getMessage());
            return false;
        }
    }

    private function ejecutarBusquedaEmpleadosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idempleado, nombre, apellido, identificacion,
                    CONCAT(nombre, ' ', apellido) as nombre_completo
                FROM empleado 
                WHERE estatus = 'activo'
                ORDER BY nombre ASC, apellido ASC"
            );

            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Empleados activos obtenidos.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaEmpleadosActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener empleados: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaProductosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idproducto, nombre, unidad_medida, estatus
                FROM producto 
                WHERE estatus = 'activo'
                ORDER BY nombre ASC"
            );

            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $resultado = [
                "status" => true,
                "message" => "Productos activos obtenidos.",
                "data" => $this->getResult()
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaProductosActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener productos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // === MÉTODOS PÚBLICOS (INTERFAZ) ===

    public function insertProduccion(array $data){
        $this->setData($data);
        return $this->ejecutarInsercionProduccion($this->getData());
    }

    public function updateProduccion(int $idproduccion, array $data){
        $this->setData($data);
        $this->setProduccionId($idproduccion);
        $data['idproduccion'] = $idproduccion;
        return $this->ejecutarActualizacionProduccion($data);
    }

    public function selectProduccionById(int $idproduccion){
        $this->setProduccionId($idproduccion);
        return $this->ejecutarBusquedaProduccionPorId($this->getProduccionId());
    }

    public function selectAllProducciones(){
        return $this->ejecutarBusquedaTodasProducciones();
    }

    public function selectDetalleProduccion(int $idproduccion){
        return $this->ejecutarBusquedaDetalleProduccion($idproduccion);
    }

    public function deleteProduccionById(int $idproduccion){
        $this->setProduccionId($idproduccion);
        return $this->ejecutarEliminacionProduccion($this->getProduccionId());
    }

    public function selectEmpleadosActivos(){
        return $this->ejecutarBusquedaEmpleadosActivos();
    }

    public function selectProductosActivos(){
        return $this->ejecutarBusquedaProductosActivos();
    }

    // Métodos de estadísticas
    public function getTotalProducciones(): int
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) AS total FROM produccion WHERE estado != 'inactivo'");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total producciones: " . $e->getMessage());
            return 0;
        } finally {
            $conexion->disconnect();
        }
    }

    public function getProduccionesEnClasificacion(): int
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) AS total FROM produccion WHERE estado = 'en_clasificacion'");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log("Error al obtener producciones en clasificación: " . $e->getMessage());
            return 0;
        } finally {
            $conexion->disconnect();
        }
    }

    public function getProduccionesFinalizadas(): int
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) AS total FROM produccion WHERE estado = 'realizado'");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log("Error al obtener producciones finalizadas: " . $e->getMessage());
            return 0;
        } finally {
            $conexion->disconnect();
        }
    }
}
?>