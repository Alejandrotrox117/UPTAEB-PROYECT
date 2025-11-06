<?php
require_once "app/core/conexion.php";

require_once "app/models/bitacoraModel.php";

class SueldosModel 
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $sueldoId;
    private $message;
    private $status;
    
   
    const SUPER_USUARIO_ROL_ID = 1; 
 
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
                    idpersona, idempleado, monto, balance, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, 'POR_PAGAR', NOW(), NOW())"
            );
            
            $this->setArray([
                $data['idpersona'] ?? null,
                $data['idempleado'] ?? null,
                $data['monto'],
                $balance,
                isset($data['idmoneda']) && $data['idmoneda'] > 0 ? $data['idmoneda'] : 3, // Default a Bolívares (VES) si no se especifica o es 0
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
                    idmoneda = ?, observacion = ?, fecha_modificacion = NOW() 
                WHERE idsueldo = ?"
            );
            
            $this->setArray([
                $data['idpersona'] ?? null,
                $data['idempleado'] ?? null,
                $data['monto'],
                $nuevoBalance,
                isset($data['idmoneda']) && $data['idmoneda'] > 0 ? $data['idmoneda'] : 3, // Default a Bolívares (VES) si no se especifica o es 0
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
                    s.idsueldo, s.idpersona, s.idempleado, s.monto, s.balance, s.idmoneda,
                    s.observacion, s.estatus, s.fecha_creacion, s.fecha_modificacion,
                    CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) as nombre_persona,
                    p.identificacion as identificacion_persona,
                    CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')) as nombre_empleado,
                    e.identificacion as identificacion_empleado,
                    e.puesto,
                    m.codigo_moneda, m.nombre_moneda, m.valor as valor_moneda,
                    DATE_FORMAT(s.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(s.fecha_modificacion, ?) as fecha_modificacion_formato
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                LEFT JOIN monedas m ON s.idmoneda = m.idmoneda
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
                    s.idsueldo, s.idpersona, s.idempleado, s.monto, s.balance, s.idmoneda,
                    s.observacion, s.estatus, s.fecha_creacion, s.fecha_modificacion,
                    CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, '')) as nombre_persona,
                    p.identificacion as identificacion_persona,
                    CONCAT(COALESCE(e.nombre, ''), ' ', COALESCE(e.apellido, '')) as nombre_empleado,
                    e.identificacion as identificacion_empleado,
                    e.puesto,
                    m.codigo_moneda, m.nombre_moneda, m.valor as valor_moneda,
                    DATE_FORMAT(s.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(s.fecha_modificacion, ?) as fecha_modificacion_formato
                FROM sueldos s
                LEFT JOIN personas p ON s.idpersona = p.idpersona
                LEFT JOIN empleado e ON s.idempleado = e.idempleado
                LEFT JOIN monedas m ON s.idmoneda = m.idmoneda
                ORDER BY s.fecha_creacion DESC"
            );
            
            $this->setArray(['%d/%m/%Y', '%d/%m/%Y']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log temporal para debugging
            error_log("=== DEBUG SUELDOS MODEL ===");
            error_log("Total registros obtenidos: " . count($rawResults));
            foreach ($rawResults as $index => $row) {
                error_log("Registro $index:");
                error_log("  idsueldo: " . ($row['idsueldo'] ?? 'NULL'));
                error_log("  idpersona: " . ($row['idpersona'] ?? 'NULL'));
                error_log("  idempleado: " . ($row['idempleado'] ?? 'NULL'));
                error_log("  nombre_persona: " . ($row['nombre_persona'] ?? 'NULL'));
                error_log("  nombre_empleado: " . ($row['nombre_empleado'] ?? 'NULL'));
                error_log("  identificacion_persona: " . ($row['identificacion_persona'] ?? 'NULL'));
                error_log("  identificacion_empleado: " . ($row['identificacion_empleado'] ?? 'NULL'));
                error_log("  ---");
            }
            error_log("=== FIN DEBUG SUELDOS MODEL ===");
            
            $this->setResult($rawResults);
            
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

    // Función para obtener todas las monedas activas
    public function getMonedas(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idmoneda, codigo_moneda, nombre_moneda, valor 
                FROM monedas 
                WHERE estatus = 'activo' 
                ORDER BY codigo_moneda ASC"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Monedas obtenidas exitosamente.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::getMonedas - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener monedas: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Función para obtener la tasa de cambio más reciente de una moneda
    public function getTasaCambioActual($codigoMoneda)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT tasa_a_bs, fecha_publicacion_bcv 
                FROM historial_tasas_bcv 
                WHERE codigo_moneda = ? 
                ORDER BY fecha_publicacion_bcv DESC, fecha_creacion DESC 
                LIMIT 1"
            );
            
            $this->setArray([$codigoMoneda]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado) {
                return [
                    'status' => true,
                    'data' => $resultado,
                    'message' => 'Tasa obtenida exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'data' => null,
                    'message' => 'No se encontró tasa para la moneda ' . $codigoMoneda
                ];
            }
            
        } catch (Exception $e) {
            error_log("SueldosModel::getTasaCambioActual - Error: " . $e->getMessage());
            return [
                'status' => false,
                'data' => null,
                'message' => 'Error al obtener tasa: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // Función para convertir monto de sueldo a bolívares
    public function convertirMontoABolivares($idsueldo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener información del sueldo con su moneda
            $this->setQuery(
                "SELECT s.monto, s.balance, m.codigo_moneda, m.nombre_moneda
                FROM sueldos s
                LEFT JOIN monedas m ON s.idmoneda = m.idmoneda
                WHERE s.idsueldo = ?"
            );
            
            $this->setArray([$idsueldo]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $sueldo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sueldo) {
                return [
                    'status' => false,
                    'message' => 'Sueldo no encontrado',
                    'data' => null
                ];
            }
            
            // Si ya está en bolívares, retornar el balance directamente
            if ($sueldo['codigo_moneda'] === 'VES' || empty($sueldo['codigo_moneda'])) {
                return [
                    'status' => true,
                    'message' => 'Monto ya está en bolívares',
                    'data' => [
                        'monto_original' => $sueldo['balance'],
                        'monto_bolivares' => $sueldo['balance'],
                        'tasa_cambio' => 1.0000,
                        'codigo_moneda' => 'VES',
                        'fecha_tasa' => date('Y-m-d')
                    ]
                ];
            }
            
            // Obtener tasa de cambio actual
            $tasaInfo = $this->getTasaCambioActual($sueldo['codigo_moneda']);
            
            if (!$tasaInfo['status']) {
                return [
                    'status' => false,
                    'message' => 'No se pudo obtener tasa de cambio para ' . $sueldo['codigo_moneda'],
                    'data' => null
                ];
            }
            
            $tasa = $tasaInfo['data']['tasa_a_bs'];
            $montoBolivares = $sueldo['balance'] * $tasa;
            
            return [
                'status' => true,
                'message' => 'Conversión realizada exitosamente',
                'data' => [
                    'monto_original' => $sueldo['balance'],
                    'monto_bolivares' => round($montoBolivares, 2),
                    'tasa_cambio' => $tasa,
                    'codigo_moneda' => $sueldo['codigo_moneda'],
                    'fecha_tasa' => $tasaInfo['data']['fecha_publicacion_bcv']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::convertirMontoABolivares - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error en conversión: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }
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

    // Funciones públicas para acceso desde el controlador
    public function insertSueldo(array $data){
        return $this->ejecutarInsercionSueldo($data);
    }

    public function updateSueldo(int $idsueldo, array $data){
        return $this->ejecutarActualizacionSueldo($idsueldo, $data);
    }

    public function selectSueldoById(int $idsueldo){
        return $this->ejecutarBusquedaSueldoPorId($idsueldo);
    }

    public function deleteSueldo(int $idsueldo){
        return $this->ejecutarEliminacionSueldo($idsueldo);
    }

    public function selectAllSueldos(int $idUsuarioSesion = 0){
        return $this->ejecutarBusquedaTodosSueldos($idUsuarioSesion);
    }

    // Función para reactivar sueldo (cambio de estatus a POR_PAGAR)
    public function reactivarSueldo(int $idsueldo){
        return $this->ejecutarReactivacionSueldo($idsueldo);
    }

    // Función para procesar pago de sueldo
    public function procesarPagoSueldo($data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Iniciar transacción
            $db->beginTransaction();

            // 1. Obtener información del sueldo actual
            $this->setQuery(
                "SELECT s.monto, s.balance, s.estatus, s.idmoneda, m.codigo_moneda
                FROM sueldos s
                LEFT JOIN monedas m ON s.idmoneda = m.idmoneda
                WHERE s.idsueldo = ?"
            );
            
            $this->setArray([intval($data['idsueldo'])]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $sueldo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sueldo) {
                throw new Exception('Sueldo no encontrado');
            }

            if ($sueldo['estatus'] === 'PAGADO') {
                throw new Exception('El sueldo ya está completamente pagado');
            }

            // 2. Convertir monto del sueldo a bolívares si es necesario
            $conversionResult = $this->convertirMontoABolivares(intval($data['idsueldo']));
            if (!$conversionResult['status']) {
                throw new Exception('Error al convertir monto: ' . $conversionResult['message']);
            }

            $montoTotalBolivares = $conversionResult['data']['monto_bolivares'];
            $montoPago = floatval($data['monto']);

            // 3. Validar que el monto no exceda el balance
            if ($montoPago > $montoTotalBolivares) {
                throw new Exception('El monto a pagar no puede ser mayor al saldo pendiente');
            }

            // 4. Obtener información de persona/empleado del sueldo para el pago
            $this->setQuery(
                "SELECT idpersona, idempleado FROM sueldos WHERE idsueldo = ?"
            );
            $this->setArray([intval($data['idsueldo'])]);
            $stmtPersona = $db->prepare($this->getQuery());
            $stmtPersona->execute($this->getArray());
            $personaInfo = $stmtPersona->fetch(PDO::FETCH_ASSOC);
            
            // Determinar idpersona para el pago
            $idPersonaPago = null;
            if ($personaInfo['idpersona']) {
                $idPersonaPago = $personaInfo['idpersona'];
            } elseif ($personaInfo['idempleado']) {
                // Si es empleado, necesitamos buscar si tiene una persona asociada
                // o usar NULL ya que la tabla pagos permite idpersona NULL
                $idPersonaPago = null;
            }
            
            // 5. Insertar el pago en la tabla pagos
            $this->setQuery(
                "INSERT INTO pagos (
                    idpersona, idtipo_pago, idsueldotemp, monto, referencia, 
                    fecha_pago, observaciones, estatus, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo', NOW())"
            );
            
            $this->setArray([
                $idPersonaPago,
                intval($data['idtipo_pago']),
                intval($data['idsueldo']),
                $montoPago,
                $data['referencia'] ?? null,
                $data['fecha_pago'],
                $data['observaciones'] ?? null
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $pagoId = $db->lastInsertId();

            // 6. Actualizar balance del sueldo
            $nuevoBalance = $montoTotalBolivares - $montoPago;
            
            // Determinar nuevo estatus
            $nuevoEstatus = 'POR_PAGAR';
            if ($nuevoBalance <= 0) {
                $nuevoEstatus = 'PAGADO';
                $nuevoBalance = 0;
            } else {
                $nuevoEstatus = 'PAGO_FRACCIONADO';
            }

            // Convertir el nuevo balance a la moneda original del sueldo
            $nuevoBalanceMonedaOriginal = $nuevoBalance;
            if ($sueldo['codigo_moneda'] !== 'VES' && $sueldo['codigo_moneda'] !== null) {
                $tasaInfo = $this->getTasaCambioActual($sueldo['codigo_moneda']);
                if ($tasaInfo['status']) {
                    $nuevoBalanceMonedaOriginal = $nuevoBalance / $tasaInfo['data']['tasa_a_bs'];
                }
            }

            $this->setQuery(
                "UPDATE sueldos SET 
                    balance = ?, estatus = ?, fecha_modificacion = NOW() 
                WHERE idsueldo = ?"
            );
            
            $this->setArray([
                round($nuevoBalanceMonedaOriginal, 2),
                $nuevoEstatus,
                intval($data['idsueldo'])
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // Confirmar transacción
            $db->commit();

            return [
                'status' => true,
                'message' => 'Pago procesado exitosamente',
                'data' => [
                    'pago_id' => $pagoId,
                    'monto_pagado' => $montoPago,
                    'nuevo_balance' => $nuevoBalanceMonedaOriginal,
                    'nuevo_estatus' => $nuevoEstatus
                ]
            ];

        } catch (Exception $e) {
            $db->rollback();
            error_log("SueldosModel::procesarPagoSueldo - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al procesar pago: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // Función para obtener pagos asociados a un sueldo
    public function getPagosSueldo($idsueldo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idpago, p.monto, p.referencia, p.fecha_pago, 
                    p.observaciones, p.estatus, p.fecha_creacion,
                    tp.nombre as tipo_pago,
                    DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_formato,
                    DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato
                FROM pagos p
                LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
                WHERE p.idsueldotemp = ?
                ORDER BY p.fecha_creacion DESC"
            );
            
            $this->setArray([$idsueldo]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'message' => 'Pagos obtenidos exitosamente',
                'data' => $result
            ];
            
        } catch (Exception $e) {
            error_log("SueldosModel::getPagosSueldo - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener pagos: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
