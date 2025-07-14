<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";

class ClientesModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $clienteId;
    private $message;
    private $status;

    // Propiedades específicas del cliente (basadas en la tabla real)
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

    public function __construct()
    {
        // Constructor vacío como en UsuariosModel
    }

    //  GETTERS Y SETTERS GENERALES (igual que UsuariosModel)
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

    //  GETTERS Y SETTERS ESPECÍFICOS DEL CLIENTE (basados en tabla real)
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

    //  FUNCIONES PRIVADAS ENCAPSULADAS (corregidas según tabla real)

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
                'activo', // Estado por defecto según la tabla
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
            
            // Manejar errores específicos de duplicación
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

    /**
     * Función privada para obtener todos los clientes
     */
    private function ejecutarBusquedaTodosClientes(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idcliente, cedula, nombre, apellido, direccion, 
                    telefono_principal, estatus, observaciones,
                    fecha_creacion, ultima_modificacion,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato,
                    DATE_FORMAT(ultima_modificacion, '%d/%m/%Y') as ultima_modificacion_formato
                FROM cliente
                ORDER BY nombre ASC"
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
     * Obtener todos los clientes
     */
    public function selectAllClientes(){
        return $this->ejecutarBusquedaTodosClientes();
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

            // Verificar qué campos existen en la tabla personas
            $this->setQuery(
                "INSERT INTO personas (
                    nombre, apellido, identificacion, telefono_principal, 
                    fecha_nacimiento, genero, correo_electronico, direccion, 
                    observaciones, tipo_persona, estatus, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $this->setArray([
                $data['nombre'],
                $data['apellido'],
                $data['cedula'], // identificacion
                $data['telefono_principal'],
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                !empty($data['genero']) ? $data['genero'] : null,
                !empty($data['correo_electronico']) ? $data['correo_electronico'] : null,
                $data['direccion'],
                $data['observaciones'] ?? '',
                'cliente', // tipo_persona
                $data['estatus'] ?? 'Activo'
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
                if (strpos($e->getMessage(), 'identificacion') !== false) {
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
}
?>
