<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;
use DateTime;

class ProduccionModel extends Mysql
{
    // ============================================================
    // PROPIEDADES DE CONTROL
    // ============================================================
    private $query;
    private $array;
    private $data;
    private $result;
    private $message;
    private $status;

    // ============================================================
    // PROPIEDADES DE LOTES
    // ============================================================
    private $idlote;
    private $numero_lote;
    private $fecha_jornada;
    private $volumen_estimado;
    private $operarios_requeridos;
    private $operarios_asignados;
    private $idsupervisor;
    private $estatus_lote;
    private $observaciones;
    private $fecha_inicio_real;
    private $fecha_fin_real;

    // ============================================================
    // PROPIEDADES DE REGISTROS DE PRODUCCIÓN
    // ============================================================
    private $idregistro;
    private $idempleado;
    private $idproducto_producir;
    private $cantidad_producir;
    private $idproducto_terminado;
    private $cantidad_producida;
    private $salario_base_dia;
    private $pago_clasificacion_trabajo;
    private $salario_total;
    private $tipo_movimiento;

    public function __construct() {}

    // ============================================================
    // GETTERS Y SETTERS
    // ============================================================
    public function getQuery() {
        return $this->query;
    }
    public function setQuery(string $query) {
        $this->query = $query;
    }
    public function getArray() {
        return $this->array ?? [];
    }
    public function setArray(array $array) {
        $this->array = $array;
    }
    public function getData() {
        return $this->data ?? [];
    }
    public function setData(array $data) {
        $this->data = $data;
    }
    public function getResult() {
        return $this->result;
    }
    public function setResult($result) {
        $this->result = $result;
    }
    public function getMessage() {
        return $this->message ?? '';
    }
    public function setMessage(string $message) {
        $this->message = $message;
    }
    public function getStatus() {
        return $this->status ?? false;
    }
    public function setStatus(bool $status) {
        $this->status = $status;
    }

    // Getters/Setters de Lotes
    public function getIdLote() {
        return $this->idlote;
    }
    public function setIdLote($idlote) {
        $this->idlote = $idlote;
    }
    public function getNumeroLote() {
        return $this->numero_lote;
    }
    public function setNumeroLote($numero_lote) {
        $this->numero_lote = $numero_lote;
    }
    public function getFechaJornada() {
        return $this->fecha_jornada;
    }
    public function setFechaJornada($fecha_jornada) {
        $this->fecha_jornada = $fecha_jornada;
    }
    public function getVolumenEstimado() {
        return $this->volumen_estimado;
    }
    public function setVolumenEstimado($volumen_estimado) {
        $this->volumen_estimado = $volumen_estimado;
    }
    public function getIdSupervisor() {
        return $this->idsupervisor;
    }
    public function setIdSupervisor($idsupervisor) {
        $this->idsupervisor = $idsupervisor;
    }
    public function getIdEmpleado() {
        return $this->idempleado;
    }
    public function setIdEmpleado($idempleado) {
        $this->idempleado = $idempleado;
    }
    public function getTipoMovimiento() {
        return $this->tipo_movimiento;
    }
    public function setTipoMovimiento($tipo_movimiento) {
        $this->tipo_movimiento = $tipo_movimiento;
    }
    public function getOperariosRequeridos() {
        return $this->operarios_requeridos;
    }
    public function setOperariosRequeridos($operarios_requeridos) {
        $this->operarios_requeridos = $operarios_requeridos;
    }
    public function getOperariosAsignados() {
        return $this->operarios_asignados;
    }
    public function setOperariosAsignados($operarios_asignados) {
        $this->operarios_asignados = $operarios_asignados;
    }
    public function getEstatusLote() {
        return $this->estatus_lote;
    }
    public function setEstatusLote($estatus_lote) {
        $this->estatus_lote = $estatus_lote;
    }
    public function getObservaciones() {
        return $this->observaciones;
    }
    public function setObservaciones($observaciones) {
        $this->observaciones = $observaciones;
    }
    public function getFechaInicioReal() {
        return $this->fecha_inicio_real;
    }
    public function setFechaInicioReal($fecha_inicio_real) {
        $this->fecha_inicio_real = $fecha_inicio_real;
    }
    public function getFechaFinReal() {
        return $this->fecha_fin_real;
    }
    public function setFechaFinReal($fecha_fin_real) {
        $this->fecha_fin_real = $fecha_fin_real;
    }
    public function getIdRegistro() {
        return $this->idregistro;
    }
    public function setIdRegistro($idregistro) {
        $this->idregistro = $idregistro;
    }
    public function getIdProductoProducir() {
        return $this->idproducto_producir;
    }
    public function setIdProductoProducir($idproducto_producir) {
        $this->idproducto_producir = $idproducto_producir;
    }
    public function getCantidadProducir() {
        return $this->cantidad_producir;
    }
    public function setCantidadProducir($cantidad_producir) {
        $this->cantidad_producir = $cantidad_producir;
    }
    public function getIdProductoTerminado() {
        return $this->idproducto_terminado;
    }
    public function setIdProductoTerminado($idproducto_terminado) {
        $this->idproducto_terminado = $idproducto_terminado;
    }
    public function getCantidadProducida() {
        return $this->cantidad_producida;
    }
    public function setCantidadProducida($cantidad_producida) {
        $this->cantidad_producida = $cantidad_producida;
    }
    public function getSalarioBaseDia() {
        return $this->salario_base_dia;
    }
    public function setSalarioBaseDia($salario_base_dia) {
        $this->salario_base_dia = $salario_base_dia;
    }
    public function getPagoClasificacionTrabajo() {
        return $this->pago_clasificacion_trabajo;
    }
    public function setPagoClasificacionTrabajo($pago_clasificacion_trabajo) {
        $this->pago_clasificacion_trabajo = $pago_clasificacion_trabajo;
    }
    public function getSalarioTotal() {
        return $this->salario_total;
    }
    public function setSalarioTotal($salario_total) {
        $this->salario_total = $salario_total;
    }


    // ============================================================
    // MÉTODOS PRIVADOS - OPERACIONES DE BASE DE DATOS
    // ============================================================

    private function ejecutarInsercionLote(array $data)
    {
        // VALIDACIONES
        if (empty($data['idsupervisor'])) {
            $this->setStatus(false);
            $this->setMessage('El supervisor es requerido');
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        }

        if (!isset($data['volumen_estimado']) || $data['volumen_estimado'] <= 0) {
            $this->setStatus(false);
            $this->setMessage('El volumen debe ser mayor a cero');
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        }

        if (empty($data['fecha_jornada'])) {
            $this->setStatus(false);
            $this->setMessage('La fecha de jornada es requerida');
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        }

        // Validar formato de fecha
        $d = DateTime::createFromFormat('Y-m-d', $data['fecha_jornada']);
        if (!$d || $d->format('Y-m-d') !== $data['fecha_jornada']) {
            $this->setStatus(false);
            $this->setMessage('Formato de fecha inválido. Use YYYY-MM-DD');
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        }

        // Setear propiedades
        $this->setFechaJornada($data['fecha_jornada']);
        $this->setVolumenEstimado($data['volumen_estimado']);
        $this->setIdSupervisor($data['idsupervisor']);
        $this->setObservaciones($data['observaciones'] ?? '');

        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $config = $this->obtenerConfiguracion($db);
            
            // Validar que la configuración existe y tiene valores válidos
            if (!$config || !isset($config['productividad_clasificacion']) || $config['productividad_clasificacion'] <= 0) {
                $this->setStatus(false);
                $this->setMessage('Error en configuración de producción. Configure primero la productividad de clasificación.');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            $operariosRequeridos = ceil($this->getVolumenEstimado() / $config['productividad_clasificacion']);
            $this->setOperariosRequeridos($operariosRequeridos);

            if ($this->getOperariosRequeridos() > $config['capacidad_maxima_planta']) {
                $this->setStatus(false);
                $this->setMessage("Se requieren {$this->getOperariosRequeridos()} operarios pero la capacidad máxima es {$config['capacidad_maxima_planta']}");
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            // Intentar crear el lote con reintentos en caso de duplicados
            $maxIntentos = 3;
            $intentoActual = 0;
            $loteCreado = false;

            while ($intentoActual < $maxIntentos && !$loteCreado) {
                try {
                    $numeroLote = $this->generarNumeroLote($this->getFechaJornada(), $db);
                    $this->setNumeroLote($numeroLote);

                    $query = "INSERT INTO lotes_produccion (
                        numero_lote, fecha_jornada, volumen_estimado, 
                        operarios_requeridos, idsupervisor, observaciones
                    ) VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $this->getNumeroLote(),
                        $this->getFechaJornada(),
                        $this->getVolumenEstimado(),
                        $this->getOperariosRequeridos(),
                        $this->getIdSupervisor(),
                        $this->getObservaciones()
                    ]);

                    $loteId = $db->lastInsertId();
                    $this->setIdLote($loteId);
                    $loteCreado = true;

                } catch (PDOException $e) {
                    // Si es error de duplicado (código 23000), reintentar
                    if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $intentoActual++;
                        if ($intentoActual < $maxIntentos) {
                            usleep(rand(50000, 100000)); // Esperar 50-100ms
                            continue;
                        }
                    }
                    throw $e; // Si no es duplicado o se acabaron los intentos, propagar error
                }
            }

            if (!$loteCreado) {
                throw new Exception("No se pudo generar un número de lote único después de varios intentos");
            }

            $this->setStatus(true);
            $this->setMessage('Lote creado exitosamente.');

            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'idlote' => $this->getIdLote(),
                'lote_id' => $this->getIdLote(),
                'numero_lote' => $this->getNumeroLote(),
                'operarios_requeridos' => $this->getOperariosRequeridos()
            ];
        } catch (Exception $e) {
            error_log("Error al insertar lote: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error al crear lote: ' . $e->getMessage());
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaTodosLotes()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                l.idlote, l.numero_lote, l.fecha_jornada, l.volumen_estimado,
                l.operarios_requeridos, l.operarios_asignados, l.estatus_lote,
                l.observaciones, l.fecha_inicio_real, l.fecha_fin_real,
                CONCAT(e.nombre, ' ', e.apellido) as supervisor,
                DATE_FORMAT(l.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato,
                DATE_FORMAT(l.fecha_inicio_real, '%d/%m/%Y %H:%i') as fecha_inicio_formato,
                DATE_FORMAT(l.fecha_fin_real, '%d/%m/%Y %H:%i') as fecha_fin_formato
            FROM lotes_produccion l
            LEFT JOIN empleado e ON l.idsupervisor = e.idempleado
            ORDER BY l.fecha_jornada DESC, l.fecha_creacion DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Lotes obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener lotes: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener lotes: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaLotePorId(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                l.*,
                CONCAT(e.nombre, ' ', e.apellido) as supervisor,
                DATE_FORMAT(l.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato
            FROM lotes_produccion l
            LEFT JOIN empleado e ON l.idsupervisor = e.idempleado
            WHERE l.idlote = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result;
        } catch (Exception $e) {
            error_log("Error al obtener lote: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInicioLote(int $idlote)
    {
        $this->setIdLote($idlote);
        
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setEstatusLote('EN_PROCESO');
            
            $query = "UPDATE lotes_produccion 
                SET estatus_lote = ?, fecha_inicio_real = NOW() 
                WHERE idlote = ? AND estatus_lote = 'PLANIFICADO'";

            $stmt = $db->prepare($query);
            $stmt->execute([$this->getEstatusLote(), $this->getIdLote()]);

            if ($stmt->rowCount() > 0) {
                $this->setStatus(true);
                $this->setMessage('Lote iniciado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo iniciar el lote.');
            }
            
            return [
                'status' => $this->getStatus(), 
                'message' => $this->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error al iniciar lote: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error al iniciar lote.');
            return [
                'status' => $this->getStatus(), 
                'message' => $this->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarCierreLote(int $idlote)
    {
        $this->setIdLote($idlote);
        
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $query = "SELECT estatus_lote FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$this->getIdLote()]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                $this->setStatus(false);
                $this->setMessage('El lote no existe.');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            if ($lote['estatus_lote'] === 'FINALIZADO') {
                $this->setStatus(false);
                $this->setMessage('El lote ya está finalizado.');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            $this->setEstatusLote('FINALIZADO');
            
            $query = "UPDATE lotes_produccion 
                SET estatus_lote = ?, fecha_fin_real = NOW() 
                WHERE idlote = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$this->getEstatusLote(), $this->getIdLote()]);

            $db->commit();

            $this->setStatus(true);
            $this->setMessage('Lote cerrado exitosamente.');

            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al cerrar lote: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error al cerrar lote: ' . $e->getMessage());
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaConfiguracionProduccion()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT * FROM configuracion_produccion 
                WHERE estatus = 'activo' 
                ORDER BY fecha_creacion DESC LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    "status" => true,
                    "message" => "Configuración obtenida.",
                    "data" => $result
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "No se encontró configuración activa.",
                    "data" => []
                ];
            }
        } catch (Exception $e) {
            error_log("Error al obtener configuración: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener configuración.",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarActualizacionConfiguracionProduccion(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "UPDATE configuracion_produccion SET
                productividad_clasificacion = ?,
                capacidad_maxima_planta = ?,
                salario_base = ?,
                beta_clasificacion = ?,
                gamma_empaque = ?,
                umbral_error_maximo = ?,
                peso_minimo_paca = ?,
                peso_maximo_paca = ?,
                ultima_modificacion = NOW()
                WHERE estatus = 'activo'";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['productividad_clasificacion'],
                $data['capacidad_maxima_planta'],
                $data['salario_base'],
                $data['beta_clasificacion'],
                $data['gamma_empaque'],
                $data['umbral_error_maximo'],
                $data['peso_minimo_paca'],
                $data['peso_maximo_paca']
            ]);

            if ($stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'message' => 'Configuración actualizada exitosamente.'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo actualizar la configuración.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error al actualizar configuración: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al actualizar configuración.'
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaEmpleadosActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                idempleado,
                CONCAT(nombre, ' ', apellido) as nombre_completo,
                nombre,
                apellido,
                puesto,
                telefono_principal
            FROM empleado 
            WHERE estatus = 'Activo'
            ORDER BY nombre, apellido";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Empleados activos obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener empleados: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener empleados.",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaProductos(string $tipo = 'todos')
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $whereClause = "WHERE p.estatus = 'activo'";

            switch ($tipo) {
                case 'por_clasificar':
                    $whereClause .= " AND c.nombre LIKE '%material%'";
                    break;
                case 'clasificados':
                    $whereClause .= " AND c.nombre NOT LIKE '%paca%'";
                    break;
            }

            $query = "SELECT 
            p.idproducto,
            p.nombre,
            p.descripcion,
            p.unidad_medida,
            p.precio,
            p.existencia,
            p.moneda,
            c.nombre as categoria
        FROM producto p
        LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
        {$whereClause}
        ORDER BY p.nombre";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Productos obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener productos.",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function obtenerConfiguracion($db)
    {
        $query = "SELECT * FROM configuracion_produccion 
            WHERE estatus = 'activo' 
            ORDER BY fecha_creacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            return [
                'productividad_clasificacion' => 150.00,
                'capacidad_maxima_planta' => 50,
                'salario_base' => 30.00,
                'beta_clasificacion' => 0.25,
                'gamma_empaque' => 5.00,
                'umbral_error_maximo' => 5.00,
                'peso_minimo_paca' => 25.00,
                'peso_maximo_paca' => 35.00
            ];
        }

        return $config;
    }

    private function generarNumeroLote($fechaJornada, $db)
    {
        $fecha = date('Ymd', strtotime($fechaJornada));
        $intentos = 0;
        $maxIntentos = 10;

        while ($intentos < $maxIntentos) {
            // Buscar el último número de lote para esta fecha
            $query = "SELECT numero_lote FROM lotes_produccion 
                      WHERE numero_lote LIKE ? 
                      ORDER BY numero_lote DESC 
                      LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(["LOTE-{$fecha}-%"]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Extraer el consecutivo del último lote (ej: LOTE-20251102-011 -> 011)
                preg_match('/-(\d+)$/', $result['numero_lote'], $matches);
                $consecutivo = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            } else {
                $consecutivo = 1;
            }

            $numeroLote = "LOTE-{$fecha}-" . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);

            // Verificar que no exista este número de lote (por si acaso)
            $queryCheck = "SELECT COUNT(*) as existe FROM lotes_produccion WHERE numero_lote = ?";
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->execute([$numeroLote]);
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existe['existe'] == 0) {
                return $numeroLote;
            }

            $intentos++;
            // Si ya existe, esperar un momento aleatorio y reintentar
            usleep(rand(10000, 50000)); // 10-50 ms
        }

        // Si después de varios intentos no se pudo generar, usar timestamp
        return "LOTE-{$fecha}-" . substr(microtime(true) * 10000, -3);
    }

    private function actualizarRegistroProduccionOperario(array $data, $db)
    {
        try {
            $query = "SELECT fecha_jornada FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['idlote']]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                throw new Exception("Lote no encontrado");
            }

            $query = "SELECT idregistro FROM registro_produccion 
            WHERE fecha_jornada = ? AND idempleado = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$lote['fecha_jornada'], $data['idempleado']]);
            $registroExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registroExistente) {
                $updateFields = [];
                $updateValues = [];

                if (isset($data['kg_clasificados_adicionales'])) {
                    $updateFields[] = "kg_clasificados = kg_clasificados + ?";
                    $updateValues[] = $data['kg_clasificados_adicionales'];
                }

                if (isset($data['kg_contaminantes_adicionales'])) {
                    $updateFields[] = "kg_contaminantes = kg_contaminantes + ?";
                    $updateValues[] = $data['kg_contaminantes_adicionales'];
                }

                if (isset($data['pacas_armadas_adicionales'])) {
                    $updateFields[] = "pacas_armadas = pacas_armadas + ?";
                    $updateValues[] = $data['pacas_armadas_adicionales'];
                }

                if (!empty($updateFields)) {
                    $updateValues[] = $registroExistente['idregistro'];
                    $query = "UPDATE registro_produccion SET " .
                        implode(", ", $updateFields) . " WHERE idregistro = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute($updateValues);
                }
            } else {
                $query = "INSERT INTO registro_produccion (
                fecha_jornada, idlote, idempleado, kg_clasificados, 
                kg_contaminantes, pacas_armadas, estatus
            ) VALUES (?, ?, ?, ?, ?, ?, 'BORRADOR')";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    $lote['fecha_jornada'],
                    $data['idlote'],
                    $data['idempleado'],
                    $data['kg_clasificados_adicionales'] ?? 0,
                    $data['kg_contaminantes_adicionales'] ?? 0,
                    $data['pacas_armadas_adicionales'] ?? 0
                ]);
            }
        } catch (Exception $e) {
            throw new Exception("Error al actualizar registro de producción: " . $e->getMessage());
        }
    }
    private function obtenerOCrearProductoPacas($idProductoClasificado, $calidad, $db)
    {
        $query = "SELECT nombre, idcategoria FROM producto WHERE idproducto = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$idProductoClasificado]);
        $productoClasificado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productoClasificado) {
            throw new Exception("Producto clasificado no encontrado");
        }

        $nombrePaca = "Paca " . str_replace(" Clasificado", "", $productoClasificado['nombre']) . " " . ucfirst(strtolower($calidad));

        $query = "SELECT idproducto FROM producto WHERE nombre = ? AND estatus = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombrePaca]);
        $productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoExistente) {
            return $productoExistente['idproducto'];
        }

        $query = "INSERT INTO producto (
        nombre, descripcion, idcategoria, unidad_medida, 
        precio, existencia, moneda, estatus, fecha_creacion
    ) VALUES (?, ?, ?, 'KG', 0, 0, 'USD', 'activo', NOW())";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $nombrePaca,
            "Paca empacada de calidad {$calidad} - Material: " . $productoClasificado['nombre'],
            $productoClasificado['idcategoria']
        ]);

        return $db->lastInsertId();
    }
    private function obtenerOCrearProductoClasificado($idProductoOrigen, $db)
    {
        $query = "SELECT nombre, idcategoria FROM producto WHERE idproducto = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$idProductoOrigen]);
        $productoOrigen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productoOrigen) {
            throw new Exception("Producto origen no encontrado");
        }

        $nombreClasificado = $productoOrigen['nombre'] . " Clasificado";

        $query = "SELECT idproducto FROM producto WHERE nombre = ? AND estatus = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombreClasificado]);
        $productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoExistente) {
            return $productoExistente['idproducto'];
        }

        $query = "INSERT INTO producto (
        nombre, descripcion, idcategoria, unidad_medida, 
        precio, existencia, moneda, estatus, fecha_creacion
    ) VALUES (?, ?, ?, 'KG', 0, 0, 'USD', 'activo', NOW())";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $nombreClasificado,
            "Material clasificado obtenido de: " . $productoOrigen['nombre'],
            $productoOrigen['idcategoria']
        ]);

        return $db->lastInsertId();
    }

    private function ejecutarRegistroSolicitudPago(array $registros = [])
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            // Si no se pasan registros, buscar todos en estatus 'BORRADOR'
            if (empty($registros)) {
                $query = "SELECT idregistro FROM registro_produccion WHERE estatus = 'BORRADOR'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $registros = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'idregistro');
            }
            if (empty($registros)) {
                error_log('[NOMINA] No hay registros en estado BORRADOR para registrar pago.');
                return [
                    'status' => false,
                    'message' => 'No hay registros en estado BORRADOR para registrar pago.',
                    'data' => []
                ];
            }
            $db->beginTransaction();
            $insertados = 0;
            $errores = [];
            foreach ($registros as $idregistro) {
                // Obtener datos del registro SOLO en BORRADOR
                $query = "SELECT * FROM registro_produccion WHERE idregistro = ? AND estatus = 'BORRADOR'";
                $stmt = $db->prepare($query);
                $stmt->execute([$idregistro]);
                $registro = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$registro) {
                    $errores[] = "Registro no encontrado o estatus incorrecto: idregistro=$idregistro";
                    continue;
                }
                // Validar campos requeridos
                if (empty($registro['idempleado']) || empty($registro['fecha_jornada'])) {
                    $errores[] = "Campos requeridos vacíos en registro idregistro=$idregistro: idempleado={$registro['idempleado']}, fecha_jornada={$registro['fecha_jornada']}, salario_total=" . ($registro['salario_total'] ?? 'NULL');
                    continue;
                }
                // Permitir salario_total en 0 si no está definido
                $monto = isset($registro['salario_total']) ? $registro['salario_total'] : 0;
                // Asumimos idmoneda=2 para USD
                // Consultar el idmoneda correspondiente a USD
                $queryMoneda = "SELECT idmoneda FROM monedas WHERE codigo_moneda = 'USD' AND estatus = 'activo' LIMIT 1";
                $stmtMoneda = $db->prepare($queryMoneda);
                $stmtMoneda->execute();
                $idmoneda = $stmtMoneda->fetchColumn();
                if (!$idmoneda) {
                    $idmoneda = 2; // Valor por defecto si no se encuentra USD
                }       try {
                    // El balance siempre es igual al monto cuando se crea (no se ha pagado nada)
                    $balance = $monto;
                    $querySueldo = "INSERT INTO sueldos (idempleado, monto, balance, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmtSueldo = $db->prepare($querySueldo);
                    $stmtSueldo->execute([
                        $registro['idempleado'],
                        $monto,
                        $balance,
                        $idmoneda,
                        'Nómina generada desde producción',
                        'POR_PAGAR'
                    ]);
                    // Actualizar estatus del registro
                    $queryUpdate = "UPDATE registro_produccion SET estatus = 'ENVIADO' WHERE idregistro = ?";
                    $stmtUpdate = $db->prepare($queryUpdate);
                    $stmtUpdate->execute([$idregistro]);
                    $insertados++;
                } catch (Exception $e) {
                    $errores[] = "Error SQL en registro idregistro=$idregistro: " . $e->getMessage();
                }
            }
            $db->commit();
            $msg = "Solicitud de pago registrada para {$insertados} registros.";
            if (!empty($errores)) {
                $msg .= " Errores: " . implode(' | ', $errores);
                error_log('[NOMINA] ' . $msg);
            }
            return [
                'status' => $insertados > 0,
                'message' => $msg,
                'data' => $registros
            ];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("[NOMINA] Error al registrar solicitud de pago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al registrar solicitud de pago: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionRegistroProduccion(array $data)
    {
        // Setear propiedades desde el array de entrada
        $this->setIdLote($data['idlote']);
        $this->setIdEmpleado($data['idempleado'] ?? null);
        $this->setFechaJornada($data['fecha_jornada']);
        $this->setIdProductoProducir($data['idproducto_producir']);
        $this->setCantidadProducir(floatval($data['cantidad_producir']));
        $this->setIdProductoTerminado($data['idproducto_terminado']);
        $this->setCantidadProducida(floatval($data['cantidad_producida']));
        $this->setTipoMovimiento($data['tipo_movimiento']);
        $this->setObservaciones($data['observaciones'] ?? '');
        
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Obtener configuración activa
            $config = $this->obtenerConfiguracion($db);

            // Validar que el lote existe
            $queryLote = "SELECT idlote FROM lotes_produccion WHERE idlote = ?";
            $stmtLote = $db->prepare($queryLote);
            $stmtLote->execute([$this->getIdLote()]);
            if (!$stmtLote->fetch()) {
                throw new Exception("El lote especificado no existe");
            }

            // Validar que el empleado existe
            if (!empty($this->getIdEmpleado())) {
                $queryEmpleado = "SELECT idempleado FROM empleado WHERE idempleado = ?";
                $stmtEmpleado = $db->prepare($queryEmpleado);
                $stmtEmpleado->execute([$this->getIdEmpleado()]);
                if (!$stmtEmpleado->fetch()) {
                    throw new Exception("El empleado especificado no existe");
                }
            }

            // Calcular salarios usando getters
            $salario_base_dia = floatval($config['salario_base'] ?? 30.00);
            $this->setSalarioBaseDia($salario_base_dia);
            
            // Pago por trabajo según precio dinámico de proceso-producto
            $precio_unitario_proceso = 0.0;
            $productoBase = ($this->getTipoMovimiento() === 'CLASIFICACION') 
                ? $this->getIdProductoProducir()
                : $this->getIdProductoTerminado();

            if ($productoBase) {
                $precio_unitario_proceso = $this->getPrecioProceso($db, $this->getTipoMovimiento(), intval($productoBase));
            }

            if ($precio_unitario_proceso <= 0) {
                // Fallback a configuración estática anterior
                if ($this->getTipoMovimiento() === 'CLASIFICACION') {
                    $precio_unitario_proceso = floatval($config['beta_clasificacion'] ?? 0.25);
                } else { // EMPAQUE
                    $precio_unitario_proceso = floatval($config['gamma_empaque'] ?? 5.00);
                }
            }

            $pago_clasificacion_trabajo = $precio_unitario_proceso * $this->getCantidadProducida();
            $this->setPagoClasificacionTrabajo($pago_clasificacion_trabajo);

            // Salario total
            $salario_total = $this->getSalarioBaseDia() + $this->getPagoClasificacionTrabajo();
            $this->setSalarioTotal($salario_total);

            // Insertar registro usando getters
            $query = "INSERT INTO registro_produccion (
                idlote, idempleado, fecha_jornada, idproducto_producir, cantidad_producir,
                idproducto_terminado, cantidad_producida, salario_base_dia,
                pago_clasificacion_trabajo, salario_total, tipo_movimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $this->getIdLote(),
                $this->getIdEmpleado(),
                $this->getFechaJornada(),
                $this->getIdProductoProducir(),
                $this->getCantidadProducir(),
                $this->getIdProductoTerminado(),
                $this->getCantidadProducida(),
                $this->getSalarioBaseDia(),
                $this->getPagoClasificacionTrabajo(),
                $this->getSalarioTotal(),
                $this->getTipoMovimiento(),
                $this->getObservaciones()
            ]);

            $idregistro = $db->lastInsertId();
            $this->setIdRegistro($idregistro);
            
            // Actualizar inventario: restar producto a producir, sumar producto terminado
            $this->actualizarInventarioProductos($db, 
                $this->getIdProductoProducir(), 
                -$this->getCantidadProducir(), 
                $this->getIdProductoTerminado(), 
                $this->getCantidadProducida()
            );
            
            $db->commit();

            $this->setStatus(true);
            $this->setMessage('Registro de producción guardado exitosamente');

            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'idregistro' => $this->getIdRegistro(),
                'salarios' => [
                    'salario_base_dia' => $this->getSalarioBaseDia(),
                    'pago_clasificacion_trabajo' => $this->getPagoClasificacionTrabajo(),
                    'salario_total' => $this->getSalarioTotal()
                ]
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("[PRODUCCION] Error al insertar registro de producción: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error al guardar registro: ' . $e->getMessage());
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaRegistrosPorLote($idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            error_log("=== obtenerRegistrosPorLote MODEL ===");
            error_log("ID Lote: " . $idlote);
            
            $query = "SELECT 
                rp.idregistro,
                rp.idlote,
                rp.idempleado,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado,
                rp.fecha_jornada,
                DATE_FORMAT(rp.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato,
                pp.nombre as producto_producir_codigo,
                pp.descripcion as producto_producir_nombre,
                rp.cantidad_producir,
                pt.nombre as producto_terminado_codigo,
                pt.descripcion as producto_terminado_nombre,
                rp.cantidad_producida,
                rp.salario_base_dia,
                rp.pago_clasificacion_trabajo,
                rp.salario_total,
                rp.tipo_movimiento,
                rp.observaciones,
                rp.estatus,
                DATE_FORMAT(rp.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                DATE_FORMAT(rp.ultima_modificacion, '%d/%m/%Y %H:%i') as ultima_modificacion_formato
            FROM registro_produccion rp
            INNER JOIN producto pp ON rp.idproducto_producir = pp.idproducto
            INNER JOIN producto pt ON rp.idproducto_terminado = pt.idproducto
            LEFT JOIN empleado e ON rp.idempleado = e.idempleado
            WHERE rp.idlote = ?
            ORDER BY rp.fecha_jornada DESC, rp.fecha_creacion DESC";

            error_log("Query preparada, ejecutando...");
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Registros encontrados: " . count($registros));

            // Calcular totales
            $totales = [
                'total_registros' => count($registros),
                'total_cantidad_producir' => 0,
                'total_cantidad_producida' => 0,
                'total_salario_base' => 0,
                'total_pago_trabajo' => 0,
                'total_salario_general' => 0,
                'registros_clasificacion' => 0,
                'registros_empaque' => 0
            ];

            foreach ($registros as $registro) {
                $totales['total_cantidad_producir'] += floatval($registro['cantidad_producir']);
                $totales['total_cantidad_producida'] += floatval($registro['cantidad_producida']);
                $totales['total_salario_base'] += floatval($registro['salario_base_dia']);
                $totales['total_pago_trabajo'] += floatval($registro['pago_clasificacion_trabajo']);
                $totales['total_salario_general'] += floatval($registro['salario_total']);
                
                if ($registro['tipo_movimiento'] === 'CLASIFICACION') {
                    $totales['registros_clasificacion']++;
                } else {
                    $totales['registros_empaque']++;
                }
            }
            
            error_log("Totales calculados: " . json_encode($totales));

            return [
                'status' => true,
                'message' => 'Registros obtenidos exitosamente',
                'data' => $registros,
                'totales' => $totales
            ];

        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al obtener registros por lote: " . $e->getMessage());
            error_log("[PRODUCCION] Stack trace: " . $e->getTraceAsString());
            return [
                'status' => false,
                'message' => 'Error al obtener registros: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaTodosRegistrosProduccion($filtros = [])
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $where = [];
            $params = [];

            if (!empty($filtros['fecha_desde'])) {
                $where[] = "rp.fecha_jornada >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "rp.fecha_jornada <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['tipo_movimiento'])) {
                $where[] = "rp.tipo_movimiento = ?";
                $params[] = $filtros['tipo_movimiento'];
            }

            if (!empty($filtros['idlote'])) {
                $where[] = "rp.idlote = ?";
                $params[] = $filtros['idlote'];
            }

            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $query = "SELECT 
                rp.idregistro,
                l.numero_lote,
                l.estatus_lote,
                rp.idempleado,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado,
                rp.fecha_jornada,
                DATE_FORMAT(rp.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato,
                pp.nombre as producto_producir_codigo,
                pp.descripcion as producto_producir_nombre,
                rp.cantidad_producir,
                pt.nombre as producto_terminado_codigo,
                pt.descripcion as producto_terminado_nombre,
                rp.cantidad_producida,
                rp.salario_base_dia,
                rp.pago_clasificacion_trabajo,
                rp.salario_total,
                rp.tipo_movimiento,
                rp.observaciones,
                rp.estatus
            FROM registro_produccion rp
            INNER JOIN lotes_produccion l ON rp.idlote = l.idlote
            LEFT JOIN empleado e ON rp.idempleado = e.idempleado
            INNER JOIN producto pp ON rp.idproducto_producir = pp.idproducto
            INNER JOIN producto pt ON rp.idproducto_terminado = pt.idproducto
            $whereClause
            ORDER BY rp.fecha_jornada DESC, rp.fecha_creacion DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'message' => 'Registros obtenidos exitosamente',
                'data' => $registros
            ];

        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al obtener todos los registros: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener registros: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ============================================================
    // MÉTODOS PÚBLICOS - LLAMADOS DESDE EL CONTROLADOR
    // ============================================================

    public function registrarSolicitudPago(array $registros = [])
    {
        return $this->ejecutarRegistroSolicitudPago($registros);
    }

    public function insertarRegistroProduccion(array $data)
    {
        return $this->ejecutarInsercionRegistroProduccion($data);
    }

    public function obtenerRegistrosPorLote($idlote)
    {
        return $this->ejecutarConsultaRegistrosPorLote($idlote);
    }

    public function selectAllRegistrosProduccion($filtros = [])
    {
        return $this->ejecutarConsultaTodosRegistrosProduccion($filtros);
    }

    /**
     * Obtiene todos los registros de producción con filtros opcionales
     */
    public function actualizarRegistroProduccion($idregistro, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            error_log("=== actualizarRegistroProduccion MODEL ===");
            error_log("ID Registro: " . $idregistro);
            
            // Verificar que el registro existe y su estado
            $queryVerificar = "SELECT rp.*, rp.idregistro, rp.estatus, rp.idlote,
                                      rp.idproducto_producir, rp.cantidad_producir,
                                      rp.idproducto_terminado, rp.cantidad_producida
                              FROM registro_produccion rp
                              WHERE rp.idregistro = ?";
            $stmtVerificar = $db->prepare($queryVerificar);
            $stmtVerificar->execute([$idregistro]);
            $registro = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return [
                    'status' => false,
                    'message' => 'Registro no encontrado'
                ];
            }

            // Solo permitir edición si el REGISTRO está en estado BORRADOR
            if ($registro['estatus'] !== 'BORRADOR') {
                return [
                    'status' => false,
                    'message' => 'Solo se pueden editar registros en estado BORRADOR. Estado actual: ' . $registro['estatus']
                ];
            }
            
            $db->beginTransaction();
            
            // PASO 1: Revertir las cantidades anteriores
            // Sumar lo que se había restado (producto a producir)
            // Restar lo que se había sumado (producto terminado)
            $this->actualizarInventarioProductos($db,
                intval($registro['idproducto_producir']),
                floatval($registro['cantidad_producir']), // Sumar (revertir la resta anterior)
                intval($registro['idproducto_terminado']),
                -floatval($registro['cantidad_producida']) // Restar (revertir la suma anterior)
            );

            // Obtener configuración para recalcular salarios
            $config = $this->obtenerConfiguracion($db);

            // Recalcular salarios con precio dinámico
            $salario_base_dia = floatval($config['salario_base'] ?? 30.00);
            $cantidad_producida = floatval($data['cantidad_producida']);

            $productoBase = ($data['tipo_movimiento'] === 'CLASIFICACION') 
                ? ($data['idproducto_producir'] ?? null) 
                : ($data['idproducto_terminado'] ?? null);

            $precio_unitario_proceso = 0.0;
            if ($productoBase) {
                $precio_unitario_proceso = $this->getPrecioProceso($db, $data['tipo_movimiento'], intval($productoBase));
            }

            if ($precio_unitario_proceso <= 0) {
                if ($data['tipo_movimiento'] === 'CLASIFICACION') {
                    $precio_unitario_proceso = floatval($config['beta_clasificacion'] ?? 0.25);
                } else {
                    $precio_unitario_proceso = floatval($config['gamma_empaque'] ?? 5.00);
                }
            }

            $pago_clasificacion_trabajo = $precio_unitario_proceso * $cantidad_producida;

            $salario_total = $salario_base_dia + $pago_clasificacion_trabajo;

            $query = "UPDATE registro_produccion SET 
                fecha_jornada = ?,
                idproducto_producir = ?,
                cantidad_producir = ?,
                idproducto_terminado = ?,
                cantidad_producida = ?,
                salario_base_dia = ?,
                pago_clasificacion_trabajo = ?,
                salario_total = ?,
                tipo_movimiento = ?,
                observaciones = ?
            WHERE idregistro = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['fecha_jornada'],
                $data['idproducto_producir'],
                floatval($data['cantidad_producir']),
                $data['idproducto_terminado'],
                $cantidad_producida,
                $salario_base_dia,
                $pago_clasificacion_trabajo,
                $salario_total,
                $data['tipo_movimiento'],
                $data['observaciones'] ?? '',
                $idregistro
            ]);
            
            // PASO 2: Aplicar las nuevas cantidades
            // Restar nuevo producto a producir, sumar nuevo producto terminado
            $this->actualizarInventarioProductos($db,
                intval($data['idproducto_producir']),
                -floatval($data['cantidad_producir']), // Restar
                intval($data['idproducto_terminado']),
                floatval($data['cantidad_producida']) // Sumar
            );

            $db->commit();
            
            error_log("Registro actualizado exitosamente");

            return [
                'status' => true,
                'message' => 'Registro actualizado exitosamente'
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("[PRODUCCION] Error al actualizar registro: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al actualizar registro: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ============================================================
    // CONFIGURACIÓN DINÁMICA DE PRECIOS POR PROCESO/PRODUCTO
    // Tabla sugerida: configuracion_produccion_precios
    //  - idprecio (PK), tipo_proceso ('CLASIFICACION'|'EMPAQUE'), idproducto (FK)
    //  - unidad_base ('KG'|'UNIDAD'), precio_unitario (DECIMAL), moneda (VARCHAR),
    //  - vigente_desde (DATE), vigente_hasta (DATE NULL), estatus ('activo')
    // ============================================================

    /**
     * Devuelve el precio unitario vigente para un par (tipo_proceso, idproducto).
     * Si no encuentra un precio activo, retorna 0 (para que el caller aplique fallback).
     */
    private function getPrecioProceso($db, string $tipo_proceso, int $idproducto): float
    {
        try {
            $sql = "SELECT salario_unitario
                    FROM configuracion_salarios_proceso
                    WHERE tipo_proceso = ? AND idproducto = ? AND estatus = 'activo'
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$tipo_proceso, $idproducto]);
            $salario = $stmt->fetchColumn();
            return $salario !== false ? floatval($salario) : 0.0;
        } catch (Exception $e) {
            error_log("[PRODUCCION] getPrecioProceso error: " . $e->getMessage());
            return 0.0;
        }
    }

    /** Obtiene listado de salarios configurados por proceso/producto */
    public function selectPreciosProceso()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $sql = "SELECT csp.idconfig_salario, csp.tipo_proceso, csp.idproducto, p.nombre AS producto_nombre,
                           p.unidad_medida, csp.unidad_base, csp.salario_unitario, csp.moneda,
                           csp.estatus, DATE_FORMAT(csp.fecha_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion
                    FROM configuracion_salarios_proceso csp
                    INNER JOIN producto p ON p.idproducto = csp.idproducto
                    ORDER BY csp.estatus DESC, csp.ultima_modificacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => true, 'data' => $data, 'message' => 'Salarios obtenidos'];
        } catch (Exception $e) {
            return ['status' => false, 'data' => [], 'message' => 'Error al obtener salarios: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    /** Crea un salario por proceso/producto */
    public function createPrecioProceso(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            // Validaciones
            $tipo = strtoupper($data['tipo_proceso'] ?? '');
            $idproducto = intval($data['idproducto'] ?? 0);
            $salario = floatval($data['salario_unitario'] ?? 0);
            $unidad_base = $data['unidad_base'] ?? null;
            $moneda = $data['moneda'] ?? 'USD';

            error_log("[PRODUCCION] createPrecioProceso - Datos recibidos: " . json_encode($data));
            error_log("[PRODUCCION] createPrecioProceso - Validados: tipo=$tipo, idproducto=$idproducto, salario=$salario");

            if (!in_array($tipo, ['CLASIFICACION', 'EMPAQUE']) || $idproducto <= 0 || $salario <= 0) {
                error_log("[PRODUCCION] createPrecioProceso - Validación fallida");
                return ['status' => false, 'message' => 'Datos inválidos: tipo proceso, producto o salario incorrectos'];
            }

            // Si no se especifica unidad, usar la del producto
            if (!$unidad_base) {
                $q = $db->prepare("SELECT unidad_medida FROM producto WHERE idproducto = ?");
                $q->execute([$idproducto]);
                $unidad_base = $q->fetchColumn() ?: 'KG';
                error_log("[PRODUCCION] createPrecioProceso - Unidad base obtenida: $unidad_base");
            }

            $sql = "INSERT INTO configuracion_salarios_proceso
                    (tipo_proceso, idproducto, salario_unitario, unidad_base, moneda, estatus)
                    VALUES (?, ?, ?, ?, ?, 'activo')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$tipo, $idproducto, $salario, $unidad_base, $moneda]);
            error_log("[PRODUCCION] createPrecioProceso - Registro insertado exitosamente");
            return ['status' => true, 'message' => 'Salario configurado correctamente'];
        } catch (Exception $e) {
            error_log("[PRODUCCION] createPrecioProceso - ERROR: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al crear salario: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    /** Actualiza un salario por proceso/producto */
    public function updatePrecioProceso(int $idconfig_salario, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $campos = [];
            $params = [];
            foreach (['tipo_proceso','idproducto','unidad_base','salario_unitario','moneda','estatus'] as $c) {
                if (isset($data[$c])) { 
                    $campos[] = "$c = ?"; 
                    $params[] = $data[$c]; 
                }
            }
            if (empty($campos)) {
                return ['status' => false, 'message' => 'Sin cambios'];
            }
            $sql = "UPDATE configuracion_salarios_proceso SET " . implode(', ', $campos) . ", ultima_modificacion = NOW() WHERE idconfig_salario = ?";
            $params[] = $idconfig_salario;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ['status' => true, 'message' => 'Salario actualizado'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al actualizar salario: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    /** Elimina (lógico) un salario por proceso/producto */
    public function deletePrecioProceso(int $idconfig_salario)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        try {
            $sql = "UPDATE configuracion_salarios_proceso SET estatus = 'inactivo', ultima_modificacion = NOW() WHERE idconfig_salario = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idconfig_salario]);
            return ['status' => true, 'message' => 'Salario desactivado'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error al desactivar salario: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Elimina un registro de producción
     * Solo permite eliminar si está en estado BORRADOR
     */
    public function eliminarRegistroProduccion($idregistro)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            error_log("=== eliminarRegistroProduccion MODEL ===");
            error_log("ID Registro: " . $idregistro);
            
            // Verificar que el registro existe y su estado
            $queryVerificar = "SELECT rp.*, rp.idregistro, rp.estatus, rp.idlote,
                                      rp.idproducto_producir, rp.cantidad_producir,
                                      rp.idproducto_terminado, rp.cantidad_producida
                              FROM registro_produccion rp
                              WHERE rp.idregistro = ?";
            $stmtVerificar = $db->prepare($queryVerificar);
            $stmtVerificar->execute([$idregistro]);
            $registro = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return [
                    'status' => false,
                    'message' => 'Registro no encontrado'
                ];
            }

            // Solo permitir eliminación si el REGISTRO está en estado BORRADOR
            if ($registro['estatus'] !== 'BORRADOR') {
                return [
                    'status' => false,
                    'message' => 'Solo se pueden eliminar registros en estado BORRADOR. Estado actual: ' . $registro['estatus']
                ];
            }
            
            $db->beginTransaction();
            
            // Revertir las cantidades antes de eliminar
            // Sumar lo que se había restado (producto a producir)
            // Restar lo que se había sumado (producto terminado)
            $this->actualizarInventarioProductos($db,
                intval($registro['idproducto_producir']),
                floatval($registro['cantidad_producir']), // Sumar (revertir la resta)
                intval($registro['idproducto_terminado']),
                -floatval($registro['cantidad_producida']) // Restar (revertir la suma)
            );
            
            $query = "DELETE FROM registro_produccion WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);
            
            $db->commit();
            
            error_log("Registro eliminado exitosamente");

            return [
                'status' => true,
                'message' => 'Registro eliminado exitosamente',
                'idlote' => $registro['idlote']
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("[PRODUCCION] Error al eliminar registro: " . $e->getMessage());
            error_log("[PRODUCCION] Stack trace: " . $e->getTraceAsString());
            return [
                'status' => false,
                'message' => 'Error al eliminar registro: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Marca un registro de producción como PAGADO
     */
    public function marcarRegistroComoPagado($idregistro)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar que el registro existe y está en estado ENVIADO
            $query = "SELECT estatus, idempleado, salario_total FROM registro_produccion WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return [
                    'status' => false,
                    'message' => 'Registro no encontrado'
                ];
            }

            if ($registro['estatus'] !== 'ENVIADO') {
                return [
                    'status' => false,
                    'message' => 'Solo se pueden marcar como pagados los registros en estado ENVIADO. Estado actual: ' . $registro['estatus']
                ];
            }

            // Actualizar estado a PAGADO
            $query = "UPDATE registro_produccion SET estatus = 'PAGADO' WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);

            // Opcional: También actualizar el registro en la tabla sueldos si existe
            $queryUpdateSueldo = "UPDATE sueldos s 
                                 INNER JOIN registro_produccion rp ON s.idempleado = rp.idempleado 
                                 SET s.estatus = 'PAGADO' 
                                 WHERE rp.idregistro = ? 
                                 AND s.estatus = 'POR_PAGAR'
                                 AND ABS(s.monto - rp.salario_total) < 0.01";
            $stmtSueldo = $db->prepare($queryUpdateSueldo);
            $stmtSueldo->execute([$idregistro]);

            return [
                'status' => true,
                'message' => 'Registro marcado como PAGADO exitosamente'
            ];

        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al marcar como pagado: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al marcar como pagado: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Cancela un registro de producción
     */
    public function cancelarRegistroProduccion($idregistro)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Verificar que el registro existe
            $query = "SELECT estatus FROM registro_produccion WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                return [
                    'status' => false,
                    'message' => 'Registro no encontrado'
                ];
            }

            if ($registro['estatus'] === 'PAGADO') {
                return [
                    'status' => false,
                    'message' => 'No se pueden cancelar registros que ya han sido pagados'
                ];
            }

            if ($registro['estatus'] === 'CANCELADO') {
                return [
                    'status' => false,
                    'message' => 'El registro ya está cancelado'
                ];
            }

            // Actualizar estado a CANCELADO
            $query = "UPDATE registro_produccion SET estatus = 'CANCELADO' WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);

            // Si estaba ENVIADO, también cancelar el sueldo asociado
            if ($registro['estatus'] === 'ENVIADO') {
                $queryUpdateSueldo = "UPDATE sueldos s 
                                     INNER JOIN registro_produccion rp ON s.idempleado = rp.idempleado 
                                     SET s.estatus = 'CANCELADO' 
                                     WHERE rp.idregistro = ? 
                                     AND s.estatus = 'POR_PAGAR'
                                     AND ABS(s.monto - rp.salario_total) < 0.01";
                $stmtSueldo = $db->prepare($queryUpdateSueldo);
                $stmtSueldo->execute([$idregistro]);
            }

            return [
                'status' => true,
                'message' => 'Registro cancelado exitosamente'
            ];

        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al cancelar registro: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al cancelar registro: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Obtiene un registro de producción por ID con toda su información
     */
    public function getRegistroById($idregistro)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                rp.*,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado,
                l.numero_lote,
                l.estatus_lote,
                pp.nombre as producto_producir_nombre,
                pp.descripcion as producto_producir_descripcion,
                pt.nombre as producto_terminado_nombre,
                pt.descripcion as producto_terminado_descripcion,
                DATE_FORMAT(rp.fecha_jornada, '%Y-%m-%d') as fecha_jornada_input
            FROM registro_produccion rp
            LEFT JOIN empleado e ON rp.idempleado = e.idempleado
            LEFT JOIN lotes_produccion l ON rp.idlote = l.idlote
            LEFT JOIN producto pp ON rp.idproducto_producir = pp.idproducto
            LEFT JOIN producto pt ON rp.idproducto_terminado = pt.idproducto
            WHERE rp.idregistro = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registro) {
                return [
                    'status' => true,
                    'data' => $registro
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Registro no encontrado'
                ];
            }

        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al obtener registro: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener registro: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Actualiza un lote de producción
     * Solo permite actualizar lotes en estado PLANIFICADO
     */
    public function actualizarLote($idlote, array $data)
    {
        $this->setIdLote($idlote);
        
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que el lote existe y está en estado PLANIFICADO
            $query = "SELECT estatus_lote FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$this->getIdLote()]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                $this->setStatus(false);
                $this->setMessage('El lote no existe');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            if ($lote['estatus_lote'] !== 'PLANIFICADO') {
                $this->setStatus(false);
                $this->setMessage('Solo se pueden editar lotes en estado PLANIFICADO');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            // Validar y recalcular operarios requeridos si cambió el volumen
            if (isset($data['volumen_estimado'])) {
                $this->setVolumenEstimado($data['volumen_estimado']);
                $config = $this->obtenerConfiguracion($db);
                $operariosRequeridos = ceil($this->getVolumenEstimado() / $config['productividad_clasificacion']);
                $this->setOperariosRequeridos($operariosRequeridos);

                if ($this->getOperariosRequeridos() > $config['capacidad_maxima_planta']) {
                    $this->setStatus(false);
                    $this->setMessage("Se requieren {$this->getOperariosRequeridos()} operarios pero la capacidad máxima es {$config['capacidad_maxima_planta']}");
                    return [
                        'status' => $this->getStatus(),
                        'message' => $this->getMessage()
                    ];
                }
            }

            // Setear propiedades desde data
            if (isset($data['fecha_jornada'])) {
                $this->setFechaJornada($data['fecha_jornada']);
            }
            if (isset($data['idsupervisor'])) {
                $this->setIdSupervisor($data['idsupervisor']);
            }
            if (isset($data['observaciones'])) {
                $this->setObservaciones($data['observaciones']);
            }

            // Construir la consulta de actualización dinámicamente
            $updateFields = [];
            $updateValues = [];

            if (isset($data['fecha_jornada'])) {
                $updateFields[] = "fecha_jornada = ?";
                $updateValues[] = $this->getFechaJornada();
            }

            if (isset($data['volumen_estimado'])) {
                $updateFields[] = "volumen_estimado = ?";
                $updateValues[] = $this->getVolumenEstimado();
                $updateFields[] = "operarios_requeridos = ?";
                $updateValues[] = $this->getOperariosRequeridos();
            }

            if (isset($data['idsupervisor'])) {
                $updateFields[] = "idsupervisor = ?";
                $updateValues[] = $this->getIdSupervisor();
            }

            if (isset($data['observaciones'])) {
                $updateFields[] = "observaciones = ?";
                $updateValues[] = $this->getObservaciones();
            }

            if (empty($updateFields)) {
                $this->setStatus(false);
                $this->setMessage('No hay campos para actualizar');
                return [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage()
                ];
            }

            $updateValues[] = $this->getIdLote();
            $query = "UPDATE lotes_produccion SET " . implode(", ", $updateFields) . " WHERE idlote = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute($updateValues);

            $db->commit();

            $this->setStatus(true);
            $this->setMessage('Lote actualizado exitosamente');

            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al actualizar lote: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error al actualizar lote: ' . $e->getMessage());
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Elimina un lote de producción
     * Solo permite eliminar lotes en estado PLANIFICADO
     */
    public function eliminarLote($idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que el lote existe y está en estado PLANIFICADO
            $query = "SELECT estatus_lote, numero_lote FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                return [
                    'status' => false,
                    'message' => 'El lote no existe'
                ];
            }

            if ($lote['estatus_lote'] !== 'PLANIFICADO') {
                return [
                    'status' => false,
                    'message' => 'Solo se pueden eliminar lotes en estado PLANIFICADO'
                ];
            }

            // Verificar si tiene registros de producción
            $query = "SELECT COUNT(*) as total FROM registro_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $registros = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registros['total'] > 0) {
                return [
                    'status' => false,
                    'message' => 'No se puede eliminar el lote porque tiene registros de producción asociados'
                ];
            }

            // Eliminar el lote
            $query = "DELETE FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Lote eliminado exitosamente',
                'numero_lote' => $lote['numero_lote']
            ];

        } catch (Exception $e) {
            $db->rollback();
            error_log("[PRODUCCION] Error al eliminar lote: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al eliminar lote: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ============================================================
    // MÉTODOS PÚBLICOS - LLAMADOS DESDE EL CONTROLADOR
    // ============================================================

    public function insertLote(array $data)
    {
        return $this->ejecutarInsercionLote($data);
    }

    public function selectAllLotes()
    {
        return $this->ejecutarConsultaTodosLotes();
    }

    public function selectLoteById(int $idlote)
    {
        return $this->ejecutarConsultaLotePorId($idlote);
    }

    public function iniciarLoteProduccion(int $idlote)
    {
        return $this->ejecutarInicioLote($idlote);
    }

    public function cerrarLoteProduccion(int $idlote)
    {
        return $this->ejecutarCierreLote($idlote);
    }

    public function selectConfiguracionProduccion()
    {
        return $this->ejecutarConsultaConfiguracionProduccion();
    }

    public function updateConfiguracionProduccion(array $data)
    {
        return $this->ejecutarActualizacionConfiguracionProduccion($data);
    }

    public function selectEmpleadosActivos()
    {
        return $this->ejecutarConsultaEmpleadosActivos();
    }

    public function selectProductos(string $tipo = 'todos')
    {
        return $this->ejecutarConsultaProductos($tipo);
    }

    /**
     * Actualiza las cantidades de productos en el inventario
     * @param PDO $db Conexión a la base de datos (debe estar en transacción)
     * @param int $idProductoProducir ID del producto a producir
     * @param float $ajusteProducir Cantidad a ajustar (negativo para restar, positivo para sumar)
     * @param int $idProductoTerminado ID del producto terminado
     * @param float $ajusteTerminado Cantidad a ajustar (positivo para sumar, negativo para restar)
     * @throws Exception Si el stock resultante es negativo
     */
    private function actualizarInventarioProductos($db, $idProductoProducir, $ajusteProducir, $idProductoTerminado, $ajusteTerminado)
    {
        try {
            // Actualizar producto a producir
            $queryStockProducir = "SELECT existencia FROM producto WHERE idproducto = ?";
            $stmt = $db->prepare($queryStockProducir);
            $stmt->execute([$idProductoProducir]);
            $stockActualProducir = floatval($stmt->fetchColumn());
            
            $nuevoStockProducir = $stockActualProducir + $ajusteProducir;
            
            if ($nuevoStockProducir < 0) {
                throw new Exception("Stock insuficiente del producto a producir. Disponible: {$stockActualProducir}, Ajuste: {$ajusteProducir}");
            }
            
            $queryUpdateProducir = "UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?";
            $stmt = $db->prepare($queryUpdateProducir);
            $stmt->execute([$nuevoStockProducir, $idProductoProducir]);
            
            // Actualizar producto terminado
            $queryStockTerminado = "SELECT existencia FROM producto WHERE idproducto = ?";
            $stmt = $db->prepare($queryStockTerminado);
            $stmt->execute([$idProductoTerminado]);
            $stockActualTerminado = floatval($stmt->fetchColumn());
            
            $nuevoStockTerminado = $stockActualTerminado + $ajusteTerminado;
            
            if ($nuevoStockTerminado < 0) {
                throw new Exception("Stock insuficiente del producto terminado. Disponible: {$stockActualTerminado}, Ajuste: {$ajusteTerminado}");
            }
            
            $queryUpdateTerminado = "UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?";
            $stmt = $db->prepare($queryUpdateTerminado);
            $stmt->execute([$nuevoStockTerminado, $idProductoTerminado]);
            
            error_log("[PRODUCCION] Inventario actualizado - Producto a producir ID:{$idProductoProducir} ({$stockActualProducir} -> {$nuevoStockProducir}), Producto terminado ID:{$idProductoTerminado} ({$stockActualTerminado} -> {$nuevoStockTerminado})");
            
        } catch (Exception $e) {
            error_log("[PRODUCCION] Error al actualizar inventario: " . $e->getMessage());
            throw $e;
        }
    }

}

