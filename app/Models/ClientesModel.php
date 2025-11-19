<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;

class ClientesModel 
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $clienteId;
    private $message;
    private $status;   
    private $idcliente;
    private $nombre;
    private $apellido;
    private $cedula;
    private $telefono_principal;
    private $direccion;
    private $estatus;
    private $observaciones;
    private $fecha_creacion;
    private $ultima_modificacion;
    private $fecha_eliminacion;

    const SUPER_USUARIO_ROL_ID = 1;

    public function __construct()
    {
    
    }

    //  GETTERS Y SETTERS GENERALES 
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

    public function getClienteId(){
        return $this->clienteId;
    }

    public function setClienteId(?int $clienteId){
        $this->clienteId = $clienteId;
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

  
    public function getIdcliente(): ?int
    {
        return $this->idcliente;
    }

    public function setIdcliente(?int $idcliente): void
    {
        $this->idcliente = $idcliente;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): void
    {
        $this->apellido = $apellido;
    }

    public function getCedula(): ?string
    {
        return $this->cedula;
    }

    public function setCedula(?string $cedula): void
    {
        $this->cedula = $cedula;
    }

    public function getTelefonoPrincipal(): ?string
    {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal(?string $telefono_principal): void
    {
        $this->telefono_principal = $telefono_principal;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): void
    {
        $this->direccion = $direccion;
    }

    public function getEstatus(): ?string
    {
        return $this->estatus;
    }

    public function setEstatus(?string $estatus): void
    {
        $this->estatus = $estatus;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getFechaCreacion(): ?string
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion(?string $fecha_creacion): void
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getUltimaModificacion(): ?string
    {
        return $this->ultima_modificacion;
    }

    public function setUltimaModificacion(?string $ultima_modificacion): void
    {
        $this->ultima_modificacion = $ultima_modificacion;
    }

    public function getFechaEliminacion(): ?string
    {
        return $this->fecha_eliminacion;
    }

    public function setFechaEliminacion(?string $fecha_eliminacion): void
    {
        $this->fecha_eliminacion = $fecha_eliminacion;
    }

   

    /**
     * Verificar si existe cliente por cédula
     */
    private function ejecutarVerificacionClientePorCedula(string $cedula, int $idClienteExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM cliente WHERE cedula = ?");
            $this->setArray([$cedula]);
            
            if ($idClienteExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idcliente != ?");
                $currentArray = $this->getArray();
                $currentArray[] = $idClienteExcluir;
                $this->setArray($currentArray);
            }
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar cliente existente por cédula: " . $e->getMessage());
            $exists = true; // Asumir que existe en caso de error por seguridad
        } finally {
            $conexion->disconnect();
        }
        
        return $exists;
    }

    /**
     * Función privada para insertar cliente
     */
    private function ejecutarInsercionCliente(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $this->setQuery(
                "INSERT INTO cliente (
                    cedula, nombre, apellido, direccion, telefono_principal,
                    estatus, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $this->setArray([
                $data['cedula'],
                $data['nombre'],
                $data['apellido'],
                $data['direccion'],
                $data['telefono_principal'],
                $data['estatus'] ?? 'activo',
                $data['observaciones'] ?? ''
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setClienteId($db->lastInsertId());
            
            if ($this->getClienteId()) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Cliente registrado exitosamente.');
            } else {
                $db->rollBack();
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de cliente tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'cliente_id' => $this->getClienteId()
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $conexion->disconnect();
            error_log("Error al insertar cliente: " . $e->getMessage());
            
         
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cedula') !== false) {
                    $mensaje = 'La cédula ya está registrada.';
                } else {
                    $mensaje = 'Datos duplicados. Verifique la información.';
                }
            } else {
                $mensaje = 'Error de base de datos al registrar cliente: ' . $e->getMessage();
            }
            
            $resultado = [
                'status' => false,
                'message' => $mensaje,
                'cliente_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para actualizar cliente
     */
    private function ejecutarActualizacionCliente(int $idcliente, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $this->setQuery(
                "UPDATE cliente SET 
                    cedula = ?, nombre = ?, apellido = ?, direccion = ?, 
                    telefono_principal = ?, estatus = ?, observaciones = ?
                WHERE idcliente = ?"
            );
            
            $this->setArray([
                $data['cedula'],
                $data['nombre'],
                $data['apellido'],
                $data['direccion'],
                $data['telefono_principal'],
                $data['estatus'] ?? 'activo',
                $data['observaciones'] ?? '',
                $idcliente
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Cliente actualizado exitosamente.');
            } else {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('No se realizaron cambios en el cliente (datos idénticos).');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $conexion->disconnect();
            error_log("Error al actualizar cliente: " . $e->getMessage());
            
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cedula') !== false) {
                    $mensaje = 'La cédula ya está registrada por otro cliente.';
                } else {
                    $mensaje = 'Datos duplicados. Verifique la información.';
                }
            } else {
                $mensaje = 'Error de base de datos al actualizar cliente: ' . $e->getMessage();
            }
            
            $resultado = [
                'status' => false,
                'message' => $mensaje
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar cliente por ID
     */
    private function ejecutarBusquedaClientePorId(int $idcliente){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $this->setQuery(
                "SELECT 
                    idcliente, cedula, nombre, apellido, direccion, 
                    telefono_principal, estatus, observaciones,
                    fecha_creacion, ultima_modificacion, fecha_eliminacion,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(ultima_modificacion, '%d/%m/%Y %H:%i') as ultima_modificacion_formato
                FROM cliente 
                WHERE idcliente = ?"
            );
            $this->setArray([$idcliente]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            $resultado = $this->getResult() ?: false;
        } catch (Exception $e) {
            error_log("ClientesModel::ejecutarBusquedaClientePorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }
        return $resultado;
    }

    /**
     * Función privada para eliminar (desactivar) cliente
     */
    private function ejecutarEliminacionCliente(int $idcliente){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();
            
            $this->setQuery("UPDATE cliente SET estatus = 'inactivo', fecha_eliminacion = NOW() WHERE idcliente = ?");
            $this->setArray([$idcliente]);
            
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
            error_log("ClientesModel::ejecutarEliminacionCliente -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    
   private function esSuperUsuario(int $idusuario){
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
              error_log("ClientesModel::esSuperUsuario - Verificando usuario ID: $idusuario");
             error_log("ClientesModel::esSuperUsuario - Constante SUPER_USUARIO_ROL_ID: " . self::SUPER_USUARIO_ROL_ID);
            $this->setQuery("SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'ACTIVO'");
            $this->setArray([$idusuario]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                $rolUsuario = intval($usuario['idrol']);
                  error_log("ClientesModel::esSuperUsuario - Rol del usuario: $rolUsuario");
                $esSuperUsuario = $rolUsuario === self::SUPER_USUARIO_ROL_ID;
                 error_log("ClientesModel::esSuperUsuario - Es super usuario: " . ($esSuperUsuario ? 'SÍ' : 'NO'));
                return $esSuperUsuario;
            } else {
                error_log("ClientesModel::esSuperUsuario - Usuario no encontrado o inactivo");
              
                return false;
            }
        } catch (Exception $e) {
            error_log("ClientesModel::esSuperUsuario - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

     /**
     * Verificar si el usuario actual es super usuario
     */
    private function esUsuarioActualSuperUsuario(int $idUsuarioSesion){
        return $this->esSuperUsuario($idUsuarioSesion);
    }

   



   private function ejecutarBusquedaTodosClientes(int $idUsuarioSesion = 0){
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();

    try {
        $esSuperUsuarioActual = $this->esUsuarioActualSuperUsuario($idUsuarioSesion);
        
        $whereClause = "";
        if (!$esSuperUsuarioActual) {
            $whereClause = " WHERE estatus = 'activo'";
        }

        $this->setQuery(
            "SELECT
            idcliente, cedula, nombre, apellido, direccion,
            telefono_principal, estatus, observaciones,
            fecha_creacion, ultima_modificacion,
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato,
            DATE_FORMAT(ultima_modificacion, '%d/%m/%Y') as ultima_modificacion_formato
            FROM cliente" . $whereClause . "
            ORDER BY estatus DESC, nombre ASC"
        );

        $this->setArray([]);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

        $resultado = [
            'status' => true,
            'message' => 'Clientes obtenidos.',
            'data' => $this->getResult()
        ];

    } catch (Exception $e) {
        error_log("ClientesModel::ejecutarBusquedaTodosClientes - Error: " . $e->getMessage());
        $resultado = [
            'status' => false,
            'message' => 'Error al obtener clientes: ' . $e->getMessage(),
            'data' => []
        ];
    } finally {
        $conexion->disconnect();
    }

    return $resultado;
}

    /**
     * Función privada para obtener clientes activos
     */
    private function ejecutarBusquedaClientesActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idcliente, cedula, nombre, apellido, direccion, 
                    telefono_principal, estatus, observaciones
                FROM cliente 
                WHERE estatus = 'activo'
                ORDER BY nombre ASC"
            );
            
            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                'status' => true,
                'message' => 'Clientes activos obtenidos.',
                'data' => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ClientesModel::ejecutarBusquedaClientesActivos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar clientes por criterio
     */
    private function ejecutarBusquedaClientesPorCriterio(string $criterio){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idcliente, nombre, apellido, cedula
                FROM cliente
                WHERE (nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ?)
                AND estatus = 'activo'
                ORDER BY nombre ASC
                LIMIT 10"
            );
            
            $param = "%{$criterio}%";
            $this->setArray([$param, $param, $param]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                'status' => true,
                'message' => 'Búsqueda completada.',
                'data' => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ClientesModel::ejecutarBusquedaClientesPorCriterio - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    //  MÉTODOS PÚBLICOS QUE USAN LAS FUNCIONES PRIVADAS

    /**
     * Insertar nuevo cliente
     */
    public function insertCliente(array $data){
        $this->setData($data);
        $dataArray = $this->getData();
        $cedula = $dataArray['cedula'];


        if ($this->ejecutarVerificacionClientePorCedula($cedula)) {
            return [
                'status' => false,
                'message' => 'La cédula ya está registrada. Por favor, utilice otra.',
                'cliente_id' => null
            ];
        }

        return $this->ejecutarInsercionCliente($this->getData());
    }

    /**
     * Actualizar cliente existente
     */
    public function updateCliente(int $idcliente, array $data){
        $this->setData($data);
        $this->setClienteId($idcliente);
        $dataArray = $this->getData();
        $cedula = $dataArray['cedula'];

  
        if ($this->ejecutarVerificacionClientePorCedula($cedula, $this->getClienteId())) {
            return [
                'status' => false,
                'message' => 'La cédula ya está registrada por otro cliente.'
            ];
        }

        return $this->ejecutarActualizacionCliente($this->getClienteId(), $this->getData());
    }

    /**
     * Obtener cliente por ID
     */
    public function selectClienteById(int $idcliente){
        $this->setClienteId($idcliente);
        return $this->ejecutarBusquedaClientePorId($this->getClienteId());
    }

    /**
     * Eliminar (desactivar) cliente por ID
     */
    public function deleteClienteById(int $idcliente){
        $this->setClienteId($idcliente);
        return $this->ejecutarEliminacionCliente($this->getClienteId());
    }


     /**
     * Verificar si un usuario es super usuario (método público)
     */
    public function verificarEsSuperUsuario(int $idusuario){
        return $this->esSuperUsuario($idusuario);
    }



    /**
     * Obtener todos los clientes
     */
    public function selectAllClientes(int $idUsuarioSesion = 0){
    return $this->ejecutarBusquedaTodosClientes($idUsuarioSesion);
}


    /**
     * Obtener clientes activos solamente
     */
    public function selectAllClientesActivos(){
        return $this->ejecutarBusquedaClientesActivos();
    }

    /**
     * Buscar clientes por criterio
     */
    public function buscarClientes(string $criterio){
        return $this->ejecutarBusquedaClientesPorCriterio($criterio);
    }

    /**
     * Método adicional para verificaciones externas
     */
    public function selectClienteByCedula(string $cedula, int $idClienteExcluir = 0){
        return $this->ejecutarVerificacionClientePorCedula($cedula, $idClienteExcluir > 0 ? $idClienteExcluir : null) 
               ? ['cedula' => $cedula] : false;
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function getEstadisticasClientes(): array
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT estatus, COUNT(*) as cantidad FROM cliente GROUP BY estatus");
            $this->setArray([]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $result = $this->getResult();
            $estadisticas = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
            
            foreach ($result as $row) {
                $estadisticas['total'] += $row['cantidad'];
                if ($row['estatus'] === 'activo') {
                    $estadisticas['activos'] = $row['cantidad'];
                } elseif ($row['estatus'] === 'inactivo') {
                    $estadisticas['inactivos'] = $row['cantidad'];
                }
            }
            
            return $estadisticas;
            
        } catch (Exception $e) {
            error_log("ClientesModel::getEstadisticasClientes - Error: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Insertar cliente completo con todos los campos del modal
     */
    public function insertClienteCompleto(array $data){
        $this->setData($data);
        $dataArray = $this->getData();
        $cedula = $dataArray['cedula'];

        // Verificar si ya existe la cédula
        if ($this->ejecutarVerificacionClientePorCedula($cedula)) {
            return [
                'status' => false,
                'message' => 'La identificación ya está registrada. Por favor, utilice otra.',
                'cliente_id' => null
            ];
        }

        return $this->ejecutarInsercionClienteCompleto($this->getData());
    }

    /**
     * Ejecutar inserción completa de cliente con todos los campos
     */
    private function ejecutarInsercionClienteCompleto(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Usar la misma tabla que el método original: cliente
            $this->setQuery(
                "INSERT INTO cliente (
                    cedula, nombre, apellido, direccion, telefono_principal,
                    estatus, observaciones, fecha_creacion, ultima_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $this->setArray([
                $data['cedula'], // identificacion mapeada a cedula
                $data['nombre'],
                $data['apellido'],
                $data['direccion'],
                $data['telefono_principal'],
                $data['estatus'] ?? 'Activo',
                $data['observaciones'] ?? ''
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setClienteId($db->lastInsertId());
            
            if ($this->getClienteId()) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Cliente registrado exitosamente.');
            } else {
                $db->rollBack();
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de cliente tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'cliente_id' => $this->getClienteId()
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $conexion->disconnect();
            error_log("Error al insertar cliente completo: " . $e->getMessage());
            
            // Manejar errores específicos de duplicación
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cedula') !== false) {
                    $mensaje = 'La identificación ya está registrada.';
                } else {
                    $mensaje = 'Ya existe un registro con estos datos.';
                }
            } else {
                $mensaje = 'Error al registrar el cliente.';
            }
            
            $resultado = [
                'status' => false,
                'message' => $mensaje,
                'cliente_id' => null
            ];
        }
        
        $conexion->disconnect();
        return $resultado;
    }
    public function reactivarCliente(int $idcliente){
    $conexion = new Conexion();
    $conexion->connect();
    $db = $conexion->get_conectGeneral();
    try {
        $this->setQuery("SELECT idcliente, estatus FROM cliente WHERE idcliente = ?");
        $this->setArray([$idcliente]);
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            return [
                'status' => false,
                'message' => 'Cliente no encontrado'
            ];
        }
        if ($cliente['estatus'] === 'activo') {
            return [
                'status' => false,
                'message' => 'El cliente ya está activo'
            ];
        }
        $this->setQuery("UPDATE cliente SET estatus = 'activo', ultima_modificacion = NOW() WHERE idcliente = ?");
        $this->setArray([$idcliente]);
        $stmt = $db->prepare($this->getQuery());
        $resultado = $stmt->execute($this->getArray());
        if ($resultado && $stmt->rowCount() > 0) {
            $resultado = [
                'status' => true,
                'message' => 'Cliente reactivado exitosamente'
            ];
        } else {
            $resultado = [
                'status' => false,
                'message' => 'No se pudo reactivar el cliente'
            ];
        }
    } catch (Exception $e) {
        error_log("ClientesModel::reactivarCliente - Error: " . $e->getMessage());
        $resultado = [
            'status' => false,
            'message' => 'Error al reactivar cliente: ' . $e->getMessage()
        ];
    } finally {
        $conexion->disconnect();
    }
    return $resultado;
}

}
?>
