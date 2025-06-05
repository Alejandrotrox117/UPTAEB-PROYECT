<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";
class ProveedoresModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $proveedorId;
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

    public function getProveedorId(){
        return $this->proveedorId;
    }

    public function setProveedorId(?int $proveedorId){
        $this->proveedorId = $proveedorId;
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

    private function ejecutarVerificacionProveedor(string $identificacion, int $idProveedorExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM proveedor WHERE identificacion = ?");
            $this->setArray([$identificacion]);
            if ($idProveedorExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idproveedor != ?");
                $array = $this->getArray();
                $array[] = $idProveedorExcluir;
                $this->setArray($array);
            }
            $stmt = $db->prepare($this->getQuery());

            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar proveedor existente: " . $e->getMessage());
            $exists = true;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    // Función privada para insertar proveedor
    private function ejecutarInsercionProveedor(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "INSERT INTO proveedor (
                    nombre, apellido, identificacion, fecha_nacimiento, 
                    direccion, correo_electronico, estatus, telefono_principal, 
                    observaciones, genero, fecha_cracion, fecha_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            
            $this->setArray([
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                $data['direccion'],
                $data['correo_electronico'],
                'ACTIVO',
                $data['telefono_principal'],
                $data['observaciones'],
                $data['genero']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setProveedorId($db->lastInsertId());
            
            if ($this->getProveedorId()) {
                $this->setStatus(true);
                $this->setMessage('Proveedor registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de proveedor tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'proveedor_id' => $this->getProveedorId()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al insertar proveedor: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar proveedor: ' . $e->getMessage(),
                'proveedor_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para actualizar proveedor
    private function ejecutarActualizacionProveedor(int $idproveedor, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "UPDATE proveedor SET 
                    nombre = ?, apellido = ?, identificacion = ?, 
                    fecha_nacimiento = ?, direccion = ?, correo_electronico = ?, 
                    telefono_principal = ?, observaciones = ?, genero = ?, 
                    fecha_modificacion = NOW() 
                WHERE idproveedor = ?"
            );
            
            $this->setArray([
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                $data['direccion'],
                $data['correo_electronico'],
                $data['telefono_principal'],
                $data['observaciones'],
                $data['genero'],
                $idproveedor
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Proveedor actualizado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar el proveedor o no se realizaron cambios.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar proveedor: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para buscar proveedor por ID
    private function ejecutarBusquedaProveedorPorId(int $idproveedor){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento,
                    direccion, correo_electronico, estatus, telefono_principal,
                    observaciones, genero, fecha_cracion, fecha_modificacion,
                    DATE_FORMAT(fecha_nacimiento, ?) as fecha_nacimiento_formato,
                    DATE_FORMAT(fecha_cracion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, ?) as fecha_modificacion_formato
                FROM proveedor 
                WHERE idproveedor = ?"
            );
            
            $this->setArray(['%d/%m/%Y', '%d/%m/%Y %H:%i', '%d/%m/%Y %H:%i', $idproveedor]);
        
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("ProveedoresModel::ejecutarBusquedaProveedorPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para eliminar proveedor
    private function ejecutarEliminacionProveedor(int $idproveedor){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE proveedor SET estatus = ?, fecha_modificacion = NOW() WHERE idproveedor = ?");
            $this->setArray(['INACTIVO', $idproveedor]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("ProveedoresModel::ejecutarEliminacionProveedor -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTodosProveedores(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento,
                    direccion, correo_electronico, estatus, telefono_principal,
                    observaciones, genero, fecha_cracion, fecha_modificacion,
                    DATE_FORMAT(fecha_nacimiento, ?) as fecha_nacimiento_formato,
                    DATE_FORMAT(fecha_cracion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, ?) as fecha_modificacion_formato
                FROM proveedor 
                ORDER BY nombre ASC, apellido ASC"
            );
            
            $this->setArray(['%d/%m/%Y', '%d/%m/%Y', '%d/%m/%Y']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Proveedores obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProveedoresModel::ejecutarBusquedaTodosProveedores - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener proveedores: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener proveedores activos
    private function ejecutarBusquedaProveedoresActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idproveedor, nombre, apellido, identificacion, telefono_principal,
                    CONCAT(nombre, ?, apellido) as nombre_completo
                FROM proveedor 
                WHERE estatus = ?
                ORDER BY nombre ASC, apellido ASC"
            );
            
            $this->setArray([' ', 'ACTIVO']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Proveedores activos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProveedoresModel::ejecutarBusquedaProveedoresActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener proveedores activos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos que usan las funciones privadas
    public function insertProveedor(array $data){
        $this->setData($data);
        $identificacion = $this->getData()['identificacion'];

        if ($this->ejecutarVerificacionProveedor($identificacion)) {
            return [
                'status' => false,
                'message' => 'Ya existe un proveedor con esa identificación.',
                'proveedor_id' => null
            ];
        }

        return $this->ejecutarInsercionProveedor($this->getData());
    }

    public function updateProveedor(int $idproveedor, array $data){
        $this->setData($data);
        $this->setProveedorId($idproveedor);
        $identificacion = $this->getData()['identificacion'];

        if ($this->ejecutarVerificacionProveedor($identificacion, $this->getProveedorId())) {
            return [
                'status' => false,
                'message' => 'Ya existe otro proveedor con esa identificación.'
            ];
        }

        return $this->ejecutarActualizacionProveedor($this->getProveedorId(), $this->getData());
    }

    public function selectProveedorById(int $idproveedor){
        $this->setProveedorId($idproveedor);
        return $this->ejecutarBusquedaProveedorPorId($this->getProveedorId());
    }

    public function deleteProveedorById(int $idproveedor){
        $this->setProveedorId($idproveedor);
        return $this->ejecutarEliminacionProveedor($this->getProveedorId());
    }

    public function selectAllProveedores(){
        return $this->ejecutarBusquedaTodosProveedores();
    }

    public function selectProveedoresActivos(){
        return $this->ejecutarBusquedaProveedoresActivos();
    }
}
?>