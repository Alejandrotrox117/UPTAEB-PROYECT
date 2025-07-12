<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";

class SueldosModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $sueldoId;
    private $message;
    private $status;
    
    // Definir constante para el rol de super usuario
    const SUPER_USUARIO_ROL_ID = 1; // IMPORTANTE: Ajustar según el ID real del rol de super usuario en tu BD
 
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

    public function getSueldoId(){
        return $this->sueldoId;
    }

    public function setSueldoId(?int $sueldoId){
        $this->sueldoId = $sueldoId;
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

    // Función privada para verificar si ya existe un sueldo para la misma persona/empleado
    private function ejecutarVerificacionSueldo(int $idpersona = null, int $idempleado = null, int $idSueldoExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            if ($idpersona !== null) {
                $this->setQuery("SELECT COUNT(*) as total FROM sueldos WHERE idpersona = ? AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $this->setArray([$idpersona]);
            } else if ($idempleado !== null) {
                $this->setQuery("SELECT COUNT(*) as total FROM sueldos WHERE idempleado = ? AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $this->setArray([$idempleado]);
            } else {
                return false;
            }

            if ($idSueldoExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idsueldo != ?");
                $array = $this->getArray();
                $array[] = $idSueldoExcluir;
                $this->setArray($array);
            }

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar sueldo existente: " . $e->getMessage());
            $exists = true;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    // Función privada para insertar sueldo
    private function ejecutarInsercionSueldo(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // El balance siempre es igual al monto cuando se crea (no se ha pagado nada)
            $balance = $data['monto'];
            
            $this->setQuery(
                "INSERT INTO sueldos (
                    idpersona, idempleado, monto, balance, observacion, estatus, fecha_creacion, fecha_modificacion
                ) VALUES (?, ?, ?, ?, ?, 'POR_PAGAR', NOW(), NOW())"
            );
            
            $this->setArray([
                $data['idpersona'] ?? null,
                $data['idempleado'] ?? null,
                $data['monto'],
                $balance,
                $data['observacion']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setSueldoId($db->lastInsertId());
            
            if ($this->getSueldoId()) {
                $this->setStatus(true);
                $this->setMessage('Sueldo registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de sueldo tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'sueldo_id' => $this->getSueldoId()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al insertar sueldo: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar sueldo: ' . $e->getMessage(),
                'sueldo_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para actualizar sueldo
    private function ejecutarActualizacionSueldo(int $idsueldo, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Si se cambia el monto, recalcular el balance (solo si estatus es POR_PAGAR)
            // En otros casos, mantener el balance actual
            $sqlBalance = "SELECT monto, balance, estatus FROM sueldos WHERE idsueldo = ?";
            $stmtBalance = $db->prepare($sqlBalance);
            $stmtBalance->execute([$idsueldo]);
            $sueldoActual = $stmtBalance->fetch(PDO::FETCH_ASSOC);
            
            $nuevoBalance = $data['balance'] ?? $sueldoActual['balance'];
            
            // Si el estatus es POR_PAGAR y se cambió el monto, recalcular balance
            if ($sueldoActual['estatus'] === 'POR_PAGAR') {
                $nuevoBalance = $data['monto'];
            }
            
            $this->setQuery(
                "UPDATE sueldos SET 
                    idpersona = ?, idempleado = ?, monto = ?, balance = ?, 
                    observacion = ?, fecha_modificacion = NOW() 
                WHERE idsueldo = ?"
            );
            
            $this->setArray([
                $data['idpersona'] ?? null,
                $data['idempleado'] ?? null,
                $data['monto'],
                $nuevoBalance,
                $data['observacion'],
                $idsueldo
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Sueldo actualizado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar el sueldo o no se realizaron cambios.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al actualizar sueldo: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar sueldo: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para buscar sueldo por ID
    private function ejecutarBusquedaSueldoPorId(int $idsueldo){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    s.idsueldo, s.idpersona, s.idempleado, s.monto, s.balance,
                    s.observacion, s.estatus, s.fecha_creacion, s.fecha_modificacion,
                    CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) as nombre_persona,
                    p.identificacion as identificacion_persona,
                    CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')) as nombre_empleado,
                    e.identificacion as identificacion_empleado,
                    e.puesto,
                    DATE_FORMAT(s.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(s.fecha_modificacion, ?) as fecha_modificacion_formato
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                WHERE s.idsueldo = ?"
            );
            
            $this->setArray(['%d/%m/%Y %H:%i', '%d/%m/%Y %H:%i', $idsueldo]);
        
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("SueldosModel::ejecutarBusquedaSueldoPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para eliminar sueldo (eliminación lógica)
    private function ejecutarEliminacionSueldo(int $idsueldo){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("DELETE FROM sueldos WHERE idsueldo = ?");
            $this->setArray([$idsueldo]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("SueldosModel::ejecutarEliminacionSueldo -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función privada para obtener todos los sueldos
    private function ejecutarBusquedaTodosSueldos(int $idUsuarioSesion = 0){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar si el usuario actual es super usuario
            $esSuperUsuarioActual = $this->esUsuarioActualSuperUsuario($idUsuarioSesion);
            
            $this->setQuery(
                "SELECT 
                    s.idsueldo, s.idpersona, s.idempleado, s.monto, s.balance,
                    s.observacion, s.estatus, s.fecha_creacion, s.fecha_modificacion,
                    CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) as nombre_persona,
                    p.identificacion as identificacion_persona,
                    CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')) as nombre_empleado,
                    e.identificacion as identificacion_empleado,
                    e.puesto,
                    DATE_FORMAT(s.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(s.fecha_modificacion, ?) as fecha_modificacion_formato
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                ORDER BY s.fecha_creacion DESC"
            );
            
            $this->setArray(['%d/%m/%Y', '%d/%m/%Y']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Sueldos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::ejecutarBusquedaTodosSueldos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener sueldos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Verificar si un usuario es super usuario
     */
    private function esSuperUsuario(int $idusuario){
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            error_log("SueldosModel::esSuperUsuario - Verificando usuario ID: $idusuario");
            error_log("SueldosModel::esSuperUsuario - Constante SUPER_USUARIO_ROL_ID: " . self::SUPER_USUARIO_ROL_ID);
            
            $this->setQuery("SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'ACTIVO'");
            $this->setArray([$idusuario]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                error_log("SueldosModel::esSuperUsuario - Usuario no encontrado o inactivo");
                return false;
            }
            
            $esSuperUsuario = ($usuario['idrol'] == self::SUPER_USUARIO_ROL_ID);
            error_log("SueldosModel::esSuperUsuario - Rol del usuario: " . $usuario['idrol'] . ", Es super usuario: " . ($esSuperUsuario ? 'SÍ' : 'NO'));
            
            return $esSuperUsuario;
            
        } catch (Exception $e) {
            error_log("SueldosModel::esSuperUsuario - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Verificar si el usuario actual de la sesión es super usuario
     */
    private function esUsuarioActualSuperUsuario(int $idUsuarioSesion){
        return $this->esSuperUsuario($idUsuarioSesion);
    }

    /**
     * Verificar si un usuario es super usuario (método público)
     */
    public function verificarEsSuperUsuario(int $idusuario){
        return $this->esSuperUsuario($idusuario);
    }

    // Función para obtener personas activas
    public function selectPersonasActivas(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idpersona, 
                    CONCAT(nombre, ' ', COALESCE(apellido, '')) as nombre_completo,
                    identificacion
                FROM personas 
                WHERE estatus = 'activo'
                ORDER BY nombre ASC, apellido ASC"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Personas activas obtenidas.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::selectPersonasActivas - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener personas activas: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función para obtener empleados activos
    public function selectEmpleadosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idempleado,
                    CONCAT(nombre, ' ', COALESCE(apellido, '')) as nombre_completo,
                    identificacion,
                    puesto
                FROM empleado 
                WHERE estatus = 'Activo'
                ORDER BY nombre ASC, apellido ASC"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Empleados activos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::selectEmpleadosActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener empleados activos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos que usan las funciones privadas
    public function insertSueldo(array $data){
        $this->setData($data);
        
        // Verificar si ya existe un sueldo reciente para esta persona/empleado
        $dataArray = $this->getData();
        $idpersona = $dataArray['idpersona'] ?? null;
        $idempleado = $dataArray['idempleado'] ?? null;

        if ($this->ejecutarVerificacionSueldo($idpersona, $idempleado)) {
            return [
                'status' => false,
                'message' => 'Ya existe un sueldo registrado para esta persona/empleado en los últimos 30 días.',
                'sueldo_id' => null
            ];
        }

        return $this->ejecutarInsercionSueldo($this->getData());
    }

    public function updateSueldo(int $idsueldo, array $data){
        $this->setData($data);
        $this->setSueldoId($idsueldo);
        
        $dataArray = $this->getData();
        $idpersona = $dataArray['idpersona'] ?? null;
        $idempleado = $dataArray['idempleado'] ?? null;

        if ($this->ejecutarVerificacionSueldo($idpersona, $idempleado, $this->getSueldoId())) {
            return [
                'status' => false,
                'message' => 'Ya existe otro sueldo registrado para esta persona/empleado en los últimos 30 días.'
            ];
        }

        return $this->ejecutarActualizacionSueldo($this->getSueldoId(), $this->getData());
    }

    public function selectSueldoById(int $idsueldo){
        $this->setSueldoId($idsueldo);
        return $this->ejecutarBusquedaSueldoPorId($this->getSueldoId());
    }

    public function deleteSueldoById(int $idsueldo){
        $this->setSueldoId($idsueldo);
        return $this->ejecutarInactivacionSueldo($this->getSueldoId());
    }

    public function reactivarSueldoById(int $idsueldo){
        $this->setSueldoId($idsueldo);
        return $this->ejecutarReactivacionSueldo($this->getSueldoId());
    }

    public function selectAllSueldos(int $idUsuarioSesion = 0){
        return $this->ejecutarBusquedaTodosSueldos($idUsuarioSesion);
    }

    public function buscarSueldos($termino)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    s.idsueldo, s.idpersona, s.idempleado, s.monto, s.balance,
                    s.observacion, s.estatus, s.fecha_creacion,
                    CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) as nombre_persona,
                    p.identificacion as identificacion_persona,
                    CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')) as nombre_empleado,
                    e.identificacion as identificacion_empleado,
                    e.puesto,
                    DATE_FORMAT(s.fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                WHERE (p.nombre LIKE ? OR p.apellido LIKE ? OR p.identificacion LIKE ? 
                       OR e.nombre LIKE ? OR e.apellido LIKE ? OR e.identificacion LIKE ?
                       OR s.observacion LIKE ?)
                ORDER BY s.fecha_creacion DESC
                LIMIT 50"
            );
            
            $param = "%{$termino}%";
            $this->setArray([$param, $param, $param, $param, $param, $param, $param]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Búsqueda completada.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::buscarSueldos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error en la búsqueda: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función para eliminar sueldo (cambio de estatus a INACTIVO)
    private function ejecutarInactivacionSueldo(int $idsueldo){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE sueldos SET estatus = 'INACTIVO', fecha_modificacion = NOW() WHERE idsueldo = ?");
            $this->setArray([$idsueldo]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("SueldosModel::ejecutarInactivacionSueldo -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función para reactivar sueldo (cambio de estatus a POR_PAGAR)
    private function ejecutarReactivacionSueldo(int $idsueldo){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("UPDATE sueldos SET estatus = 'POR_PAGAR', fecha_modificacion = NOW() WHERE idsueldo = ?");
            $this->setArray([$idsueldo]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("SueldosModel::ejecutarReactivacionSueldo -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }
}
