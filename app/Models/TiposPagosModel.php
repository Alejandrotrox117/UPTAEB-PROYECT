<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;

class TiposPagosModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $tipoPagoId;
    private $message;
    private $status;

    public function __construct()
    {
        
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

    public function getTipoPagoId(){
        return $this->tipoPagoId;
    }

    public function setTipoPagoId(?int $tipoPagoId){
        $this->tipoPagoId = $tipoPagoId;
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

    private function ejecutarVerificacionTipoPago(string $nombre, int $idTipoPagoExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM tipos_pagos WHERE nombre = ?");
            $this->setArray([$nombre]);
            if ($idTipoPagoExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idtipo_pago != ?");
                $array = $this->getArray();
                $array[] = $idTipoPagoExcluir;
                $this->setArray($array);
            }
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar tipo de pago existente: " . $e->getMessage());
            $exists = true;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    private function ejecutarInsercionTipoPago(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "INSERT INTO tipos_pagos (nombre, estatus, fecha_creacion, fecha_modificacion) 
                VALUES (?, ?, NOW(), NOW())"
            );
            
            $this->setArray([
                $data['nombre'],
                'activo'
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setTipoPagoId($db->lastInsertId());
            
            if ($this->getTipoPagoId()) {
                $this->setStatus(true);
                $this->setMessage('Tipo de pago registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de tipo de pago tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'tipo_pago_id' => $this->getTipoPagoId()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al insertar tipo de pago: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar tipo de pago: ' . $e->getMessage(),
                'tipo_pago_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarActualizacionTipoPago(int $idtipo_pago, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "UPDATE tipos_pagos SET 
                    nombre = ?, fecha_modificacion = NOW() 
                WHERE idtipo_pago = ?"
            );
            
            $this->setArray([
                $data['nombre'],
                $idtipo_pago
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Tipo de pago actualizado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar el tipo de pago o no se realizaron cambios.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al actualizar tipo de pago: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar tipo de pago: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTipoPagoPorId(int $idtipo_pago){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idtipo_pago, nombre, estatus, fecha_creacion, fecha_modificacion,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                FROM tipos_pagos 
                WHERE idtipo_pago = ?"
            );
            
            $this->setArray([$idtipo_pago]);
        
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("TiposPagosModel::ejecutarBusquedaTipoPagoPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminacionTipoPago(int $idtipo_pago){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE tipos_pagos SET estatus = ?, fecha_modificacion = NOW() WHERE idtipo_pago = ?");
            $this->setArray(['inactivo', $idtipo_pago]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("TiposPagosModel::ejecutarEliminacionTipoPago -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTodosTiposPagos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idtipo_pago, nombre, estatus, fecha_creacion, fecha_modificacion,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, '%d/%m/%Y') as fecha_modificacion_formato
                FROM tipos_pagos 
                ORDER BY nombre ASC"
            );
            
            $this->setArray([]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Tipos de pagos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("TiposPagosModel::ejecutarBusquedaTodosTiposPagos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener tipos de pagos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTiposPagosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idtipo_pago, nombre
                FROM tipos_pagos 
                WHERE estatus = ?
                ORDER BY nombre ASC"
            );
            
            $this->setArray(['activo']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Tipos de pagos activos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("TiposPagosModel::ejecutarBusquedaTiposPagosActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener tipos de pagos activos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos que usan las funciones privadas
    public function insertTipoPago(array $data){
        $this->setData($data);
        $nombre = $this->getData()['nombre'];

        if ($this->ejecutarVerificacionTipoPago($nombre)) {
            return [
                'status' => false,
                'message' => 'Ya existe un tipo de pago con ese nombre.',
                'tipo_pago_id' => null
            ];
        }

        return $this->ejecutarInsercionTipoPago($this->getData());
    }

    public function updateTipoPago(int $idtipo_pago, array $data){
        $this->setData($data);
        $this->setTipoPagoId($idtipo_pago);
        $nombre = $this->getData()['nombre'];

        if ($this->ejecutarVerificacionTipoPago($nombre, $this->getTipoPagoId())) {
            return [
                'status' => false,
                'message' => 'Ya existe otro tipo de pago con ese nombre.'
            ];
        }

        return $this->ejecutarActualizacionTipoPago($this->getTipoPagoId(), $this->getData());
    }

    public function selectTipoPagoById(int $idtipo_pago){
        $this->setTipoPagoId($idtipo_pago);
        return $this->ejecutarBusquedaTipoPagoPorId($this->getTipoPagoId());
    }

    public function deleteTipoPagoById(int $idtipo_pago){
        $this->setTipoPagoId($idtipo_pago);
        return $this->ejecutarEliminacionTipoPago($this->getTipoPagoId());
    }

    public function selectAllTiposPagos(){
        return $this->ejecutarBusquedaTodosTiposPagos();
    }

    public function selectTiposPagosActivos(){
        return $this->ejecutarBusquedaTiposPagosActivos();
    }
}
?>