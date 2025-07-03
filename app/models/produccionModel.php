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

    public function __construct() {}

    // ========================================
    // GESTIÓN DE LOTES
    // ========================================

    public function insertLote(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Calcular operarios requeridos: N_total = ⌈V_día/p_clasificación⌉
            $config = $this->obtenerConfiguracion($db);
            $operariosRequeridos = ceil($data['volumen_estimado'] / $config['productividad_clasificacion']);

            // Validar capacidad máxima
            if ($operariosRequeridos > $config['capacidad_maxima_planta']) {
                return [
                    'status' => false,
                    'message' => "Se requieren {$operariosRequeridos} operarios pero la capacidad máxima es {$config['capacidad_maxima_planta']}"
                ];
            }

            // Generar número de lote
            $numeroLote = $this->generarNumeroLote($data['fecha_jornada'], $db);

            $query = "INSERT INTO lotes_produccion (
                numero_lote, fecha_jornada, volumen_estimado, 
                operarios_requeridos, idsupervisor, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroLote,
                $data['fecha_jornada'],
                $data['volumen_estimado'],
                $operariosRequeridos,
                $data['idsupervisor'],
                $data['observaciones']
            ]);

            $loteId = $db->lastInsertId();

            return [
                'status' => true,
                'message' => 'Lote creado exitosamente.',
                'lote_id' => $loteId,
                'numero_lote' => $numeroLote,
                'operarios_requeridos' => $operariosRequeridos
            ];
        } catch (Exception $e) {
            error_log("Error al insertar lote: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al crear lote: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function selectAllLotes()
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

    public function selectLoteById(int $idlote)
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

    public function iniciarLoteProduccion(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "UPDATE lotes_produccion 
                SET estatus_lote = 'EN_PROCESO', fecha_inicio_real = NOW() 
                WHERE idlote = ? AND estatus_lote = 'PLANIFICADO'";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);

            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Lote iniciado exitosamente.'];
            } else {
                return ['status' => false, 'message' => 'No se pudo iniciar el lote.'];
            }
        } catch (Exception $e) {
            error_log("Error al iniciar lote: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al iniciar lote.'];
        } finally {
            $conexion->disconnect();
        }
    }

    public function cerrarLoteProduccion(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que todos los operarios tengan registros
            $query = "SELECT COUNT(*) as operarios_asignados,
                        COUNT(rp.idregistro) as operarios_con_registro
                FROM asignaciones_operarios a
                LEFT JOIN registro_produccion rp ON a.idempleado = rp.idempleado 
                    AND rp.idlote = a.idlote
                WHERE a.idlote = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($verificacion['operarios_asignados'] != $verificacion['operarios_con_registro']) {
                $faltantes = $verificacion['operarios_asignados'] - $verificacion['operarios_con_registro'];
                return [
                    'status' => false,
                    'message' => "Faltan registros de producción para {$faltantes} operarios."
                ];
            }

            // Cerrar el lote
            $query = "UPDATE lotes_produccion 
                SET estatus_lote = 'FINALIZADO', fecha_fin_real = NOW() 
                WHERE idlote = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Lote cerrado exitosamente.'
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al cerrar lote: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al cerrar lote.'
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // GESTIÓN DE OPERARIOS
    // ========================================

    // ========================================
    // MÉTODO CORREGIDO PARA ASIGNAR OPERARIOS
    // ========================================

    public function asignarOperariosLote(int $idlote, array $operarios)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que el lote existe y está en estado correcto
            $query = "SELECT estatus_lote FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                throw new Exception("El lote no existe");
            }

            if ($lote['estatus_lote'] !== 'PLANIFICADO') {
                throw new Exception("Solo se pueden asignar operarios a lotes planificados");
            }

            // Limpiar asignaciones existentes
            $query = "DELETE FROM asignaciones_operarios WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);

            $operariosAsignados = 0;

            // Validar y insertar nuevas asignaciones
            foreach ($operarios as $operario) {
                // Validar datos del operario
                if (
                    empty($operario['idempleado']) ||
                    empty($operario['tipo_tarea']) ||
                    empty($operario['turno'])
                ) {
                    throw new Exception("Datos incompletos del operario");
                }

                // Verificar que el empleado existe
                $query = "SELECT idempleado FROM empleado WHERE idempleado = ? AND estatus = 'Activo'";
                $stmt = $db->prepare($query);
                $stmt->execute([intval($operario['idempleado'])]);
                if (!$stmt->fetch()) {
                    throw new Exception("El empleado ID {$operario['idempleado']} no existe o no está activo");
                }

                // Validar tipo de tarea
                if (!in_array($operario['tipo_tarea'], ['CLASIFICACION', 'EMPAQUE'])) {
                    throw new Exception("Tipo de tarea inválido: {$operario['tipo_tarea']}");
                }

                // Validar turno
                if (!in_array($operario['turno'], ['MAÑANA', 'TARDE', 'NOCHE'])) {
                    throw new Exception("Turno inválido: {$operario['turno']}");
                }

                // Insertar asignación
                $query = "INSERT INTO asignaciones_operarios (
                idlote, idempleado, tipo_tarea, turno, observaciones, fecha_asignacion
            ) VALUES (?, ?, ?, ?, ?, NOW())";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    $idlote,
                    intval($operario['idempleado']),
                    $operario['tipo_tarea'],
                    $operario['turno'],
                    $operario['observaciones'] ?? ''
                ]);

                $operariosAsignados++;
            }

            // Actualizar contador en lote
            $query = "UPDATE lotes_produccion SET operarios_asignados = ? WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$operariosAsignados, $idlote]);

            $db->commit();

            return [
                'status' => true,
                'message' => "Se asignaron {$operariosAsignados} operarios al lote exitosamente.",
                'operarios_asignados' => $operariosAsignados
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al asignar operarios: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al asignar operarios: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // MÉTODO CORREGIDO PARA OBTENER OPERARIOS DISPONIBLES
    // ========================================

    public function selectOperariosDisponibles(string $fecha)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
            e.idempleado, 
            CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
            e.puesto,
            e.telefono_principal,
            CASE 
                WHEN a.idasignacion IS NOT NULL THEN 'ASIGNADO'
                ELSE 'DISPONIBLE'
            END as estatus_disponibilidad,
            COALESCE(l.numero_lote, '') as lote_asignado
        FROM empleado e
        LEFT JOIN asignaciones_operarios a ON e.idempleado = a.idempleado 
            AND EXISTS (
                SELECT 1 FROM lotes_produccion lp 
                WHERE lp.idlote = a.idlote 
                AND lp.fecha_jornada = ?
                AND lp.estatus_lote IN ('PLANIFICADO', 'EN_PROCESO')
            )
        LEFT JOIN lotes_produccion l ON a.idlote = l.idlote 
            AND l.fecha_jornada = ?
            AND l.estatus_lote IN ('PLANIFICADO', 'EN_PROCESO')
        WHERE e.estatus = 'Activo'
        ORDER BY e.nombre, e.apellido";

            $stmt = $db->prepare($query);
            $stmt->execute([$fecha, $fecha]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Operarios disponibles obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener operarios disponibles: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener operarios disponibles: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // MÉTODO CORREGIDO PARA OBTENER ASIGNACIONES
    // ========================================

    public function selectAsignacionesLote(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
            a.idasignacion, 
            a.idempleado,
            a.tipo_tarea, 
            a.turno, 
            a.estatus_asignacion,
            a.observaciones,
            CONCAT(e.nombre, ' ', e.apellido) as operario,
            e.telefono_principal,
            e.puesto
        FROM asignaciones_operarios a
        INNER JOIN empleado e ON a.idempleado = e.idempleado
        WHERE a.idlote = ?
        ORDER BY a.tipo_tarea, e.nombre, e.apellido";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Asignaciones obtenidas.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener asignaciones: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener asignaciones: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // PROCESOS DE PRODUCCIÓN
    // ========================================

    public function registrarProcesoClasificacion(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // 1. Descontar material origen del stock
            $query = "UPDATE producto 
            SET existencia = existencia - ?
            WHERE idproducto = ? AND existencia >= ?";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['kg_procesados'],
                $data['idproducto_origen'],
                $data['kg_procesados']
            ]);

            if ($stmt->rowCount() == 0) {
                throw new Exception("Stock insuficiente del producto origen");
            }

            // 2. Registrar movimiento de salida (material procesado)
            $numeroMovimiento = 'CLA-' . date('YmdHis') . '-' . $data['idempleado'];
            $observaciones = "Clasificación - Lote: {$data['idlote']}, Operario: {$data['idempleado']}, ";
            $observaciones .= "Procesado: {$data['kg_procesados']} kg, Limpio: {$data['kg_limpios']} kg, ";
            $observaciones .= "Contaminantes: {$data['kg_contaminantes']} kg";
            if (!empty($data['observaciones'])) {
                $observaciones .= " - " . $data['observaciones'];
            }

            $query = "INSERT INTO movimientos_existencia (
            numero_movimiento, idproducto, idtipomovimiento, 
            cantidad_salida, observaciones, fecha_creacion
        ) VALUES (?, ?, 3, ?, ?, NOW())";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimiento,
                $data['idproducto_origen'],
                $data['kg_procesados'],
                $observaciones
            ]);

            // 3. Si hay material limpio, agregarlo al inventario de productos clasificados
            if ($data['kg_limpios'] > 0) {
                // Buscar o crear producto clasificado correspondiente
                $idProductoClasificado = $this->obtenerOCrearProductoClasificado($data['idproducto_origen'], $db);

                // Incrementar stock del producto clasificado
                $query = "UPDATE producto 
                SET existencia = existencia + ?
                WHERE idproducto = ?";

                $stmt = $db->prepare($query);
                $stmt->execute([$data['kg_limpios'], $idProductoClasificado]);

                // Registrar movimiento de entrada para material clasificado
                $numeroMovimientoEntrada = 'CLA-E-' . date('YmdHis') . '-' . $data['idempleado'];
                $observacionesEntrada = "Material clasificado - Lote: {$data['idlote']}, ";
                $observacionesEntrada .= "Material limpio obtenido: {$data['kg_limpios']} kg";

                $query = "INSERT INTO movimientos_existencia (
                numero_movimiento, idproducto, idtipomovimiento, 
                cantidad_entrada, observaciones, fecha_creacion
            ) VALUES (?, ?, 3, ?, ?, NOW())";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    $numeroMovimientoEntrada,
                    $idProductoClasificado,
                    $data['kg_limpios'],
                    $observacionesEntrada
                ]);
            }

            // 4. Actualizar registro de producción del operario
            $this->actualizarRegistroProduccionOperario([
                'idlote' => $data['idlote'],
                'idempleado' => $data['idempleado'],
                'kg_clasificados_adicionales' => $data['kg_limpios'],
                'kg_contaminantes_adicionales' => $data['kg_contaminantes']
            ], $db);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Proceso de clasificación registrado exitosamente.',
                'movimiento' => $numeroMovimiento
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al registrar clasificación: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al registrar clasificación: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function registrarProcesoEmpaque(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Validar peso de paca
            $config = $this->obtenerConfiguracion($db);
            if (
                $data['peso_paca'] < $config['peso_minimo_paca'] ||
                $data['peso_paca'] > $config['peso_maximo_paca']
            ) {
                throw new Exception("El peso debe estar entre {$config['peso_minimo_paca']} y {$config['peso_maximo_paca']} kg");
            }

            // 1. Descontar material clasificado
            $query = "UPDATE producto 
            SET existencia = existencia - ?
            WHERE idproducto = ? AND existencia >= ?";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['peso_paca'],
                $data['idproducto_clasificado'],
                $data['peso_paca']
            ]);

            if ($stmt->rowCount() == 0) {
                throw new Exception("Stock insuficiente del producto clasificado");
            }

            // 2. Registrar movimiento de salida (material usado para empacar)
            $numeroMovimientoSalida = 'EMP-S-' . date('YmdHis') . '-' . $data['idempleado'];
            $observacionesSalida = "Empaque - Material usado - Lote: {$data['idlote']}, ";
            $observacionesSalida .= "Operario: {$data['idempleado']}, Peso: {$data['peso_paca']} kg, ";
            $observacionesSalida .= "Calidad: {$data['calidad']}";
            if (!empty($data['observaciones'])) {
                $observacionesSalida .= " - " . $data['observaciones'];
            }

            $query = "INSERT INTO movimientos_existencia (
            numero_movimiento, idproducto, idtipomovimiento, 
            cantidad_salida, observaciones, fecha_creacion
        ) VALUES (?, ?, 4, ?, ?, NOW())";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimientoSalida,
                $data['idproducto_clasificado'],
                $data['peso_paca'],
                $observacionesSalida
            ]);

            // 3. Obtener o crear producto "pacas" correspondiente
            $idProductoPacas = $this->obtenerOCrearProductoPacas($data['idproducto_clasificado'], $data['calidad'], $db);

            // 4. Incrementar stock de pacas
            $query = "UPDATE producto 
            SET existencia = existencia + ?
            WHERE idproducto = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$data['peso_paca'], $idProductoPacas]);

            // 5. Registrar movimiento de entrada (paca creada)
            $codigoPaca = 'PACA-' . date('Ymd') . '-' . str_pad($data['idempleado'], 3, '0', STR_PAD_LEFT) . '-' . date('His');
            $numeroMovimientoEntrada = 'EMP-E-' . date('YmdHis') . '-' . $data['idempleado'];

            $observacionesEntrada = "Paca empacada - Código: {$codigoPaca}, Lote: {$data['idlote']}, ";
            $observacionesEntrada .= "Operario: {$data['idempleado']}, Peso: {$data['peso_paca']} kg, ";
            $observacionesEntrada .= "Calidad: {$data['calidad']}";
            if (!empty($data['observaciones'])) {
                $observacionesEntrada .= " - " . $data['observaciones'];
            }

            $query = "INSERT INTO movimientos_existencia (
            numero_movimiento, idproducto, idtipomovimiento, 
            cantidad_entrada, observaciones, fecha_creacion
        ) VALUES (?, ?, 4, ?, ?, NOW())";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimientoEntrada,
                $idProductoPacas,
                $data['peso_paca'],
                $observacionesEntrada
            ]);

            // 6. Actualizar registro de producción
            $this->actualizarRegistroProduccionOperario([
                'idlote' => $data['idlote'],
                'idempleado' => $data['idempleado'],
                'pacas_armadas_adicionales' => 1
            ], $db);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Paca empacada exitosamente.',
                'codigo_paca' => $codigoPaca
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al registrar empaque: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al registrar empaque: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // NÓMINA Y PRODUCCIÓN DIARIA
    // ========================================

    public function registrarProduccionDiariaLote(int $idlote, array $registros)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            foreach ($registros as $registro) {
                // Insertar o actualizar registro de producción
                $query = "INSERT INTO registro_produccion (
                    fecha_jornada, idlote, idempleado, kg_clasificados,
                    kg_contaminantes, pacas_armadas, observaciones
                ) VALUES (
                    (SELECT fecha_jornada FROM lotes_produccion WHERE idlote = ?),
                    ?, ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    kg_clasificados = VALUES(kg_clasificados),
                    kg_contaminantes = VALUES(kg_contaminantes),
                    pacas_armadas = VALUES(pacas_armadas),
                    observaciones = VALUES(observaciones)";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    $idlote,
                    $idlote,
                    intval($registro['idempleado']),
                    floatval($registro['kg_clasificados']),
                    floatval($registro['kg_contaminantes']),
                    intval($registro['pacas_armadas']),
                    trim($registro['observaciones'] ?? '')
                ]);
            }

            $db->commit();

            return [
                'status' => true,
                'message' => 'Registros de producción guardados exitosamente.',
                'registros_procesados' => count($registros)
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al registrar producción diaria: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al registrar producción diaria.'
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function calcularNominaProduccion(string $fechaInicio, string $fechaFin)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Actualizar registros con cálculos (el trigger se encarga del cálculo)
            $query = "UPDATE registro_produccion SET
                estatus = 'CALCULADO'
                WHERE fecha_jornada BETWEEN ? AND ? 
                AND estatus = 'BORRADOR'";

            $stmt = $db->prepare($query);
            $stmt->execute([$fechaInicio, $fechaFin]);
            $registrosActualizados = $stmt->rowCount();

            $db->commit();

            return [
                'status' => true,
                'message' => "Nómina calculada exitosamente para {$registrosActualizados} registros.",
                'registros_actualizados' => $registrosActualizados
            ];
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al calcular nómina: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al calcular nómina.'
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // DATOS PARA TABLAS
    // ========================================

    public function selectProcesosRecientes(string $fecha)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                DATE_FORMAT(me.fecha_creacion, '%d/%m/%Y %H:%i') as fecha,
                CONCAT(e.nombre, ' ', e.apellido) as operario,
                me.tipo_proceso as proceso,
                CASE 
                    WHEN me.tipo_proceso = 'CLASIFICACION' THEN CONCAT(me.cantidad_salida, ' kg procesados')
                    WHEN me.tipo_proceso = 'EMPAQUE' THEN CONCAT(me.cantidad_salida, ' kg empacados')
                    ELSE CONCAT(me.cantidad_salida, ' kg')
                END as cantidad,
                me.observaciones,
                l.numero_lote
            FROM movimientos_existencia me
            LEFT JOIN empleado e ON me.idempleado = e.idempleado
            LEFT JOIN lotes_produccion l ON me.idlote = l.idlote
            WHERE DATE(me.fecha_creacion) = ?
            AND me.tipo_proceso IN ('CLASIFICACION', 'EMPAQUE')
            ORDER BY me.fecha_creacion DESC
            LIMIT 20";

            $stmt = $db->prepare($query);
            $stmt->execute([$fecha]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Procesos recientes obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener procesos recientes: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener procesos recientes.",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function selectRegistrosNomina(string $fechaInicio, string $fechaFin)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                DATE_FORMAT(rp.fecha_jornada, '%d/%m/%Y') as fecha,
                CONCAT(e.nombre, ' ', e.apellido) as operario,
                rp.kg_clasificados,
                rp.kg_contaminantes,
                rp.pacas_armadas,
                rp.tasa_error,
                rp.salario_total,
                rp.estatus,
                l.numero_lote
            FROM registro_produccion rp
            INNER JOIN empleado e ON rp.idempleado = e.idempleado
            INNER JOIN lotes_produccion l ON rp.idlote = l.idlote
            WHERE rp.fecha_jornada BETWEEN ? AND ?
            ORDER BY rp.fecha_jornada DESC, e.nombre ASC";

            $stmt = $db->prepare($query);
            $stmt->execute([$fechaInicio, $fechaFin]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => true,
                "message" => "Registros de nómina obtenidos.",
                "data" => $result
            ];
        } catch (Exception $e) {
            error_log("Error al obtener registros de nómina: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener registros de nómina.",
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // CONFIGURACIÓN
    // ========================================

    public function selectConfiguracionProduccion()
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

    public function updateConfiguracionProduccion(array $data)
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

    // ========================================
    // DATOS AUXILIARES
    // ========================================

    public function selectEmpleadosActivos()
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

    public function selectProductos(string $tipo = 'todos')
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

    // ========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ========================================

    private function obtenerConfiguracion($db)
    {
        $query = "SELECT * FROM configuracion_produccion 
            WHERE estatus = 'activo' 
            ORDER BY fecha_creacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            // Valores por defecto
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

        $query = "SELECT COUNT(*) as total FROM lotes_produccion WHERE fecha_jornada = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$fechaJornada]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $consecutivo = ($result['total'] ?? 0) + 1;
        return "LOTE-{$fecha}-" . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);
    }

    private function actualizarRegistroProduccionOperario(array $data, $db)
    {
        try {
            // Obtener fecha del lote
            $query = "SELECT fecha_jornada FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['idlote']]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                throw new Exception("Lote no encontrado");
            }

            // Verificar si ya existe registro
            $query = "SELECT idregistro FROM registro_produccion 
            WHERE fecha_jornada = ? AND idempleado = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$lote['fecha_jornada'], $data['idempleado']]);
            $registroExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registroExistente) {
                // Actualizar registro existente
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
                // Crear nuevo registro
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
        // Obtener información del producto clasificado
        $query = "SELECT nombre, idcategoria FROM producto WHERE idproducto = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$idProductoClasificado]);
        $productoClasificado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productoClasificado) {
            throw new Exception("Producto clasificado no encontrado");
        }

        // Buscar si ya existe un producto paca correspondiente
        $nombrePaca = "Paca " . str_replace(" Clasificado", "", $productoClasificado['nombre']) . " " . ucfirst(strtolower($calidad));

        $query = "SELECT idproducto FROM producto WHERE nombre = ? AND estatus = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombrePaca]);
        $productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoExistente) {
            return $productoExistente['idproducto'];
        }

        // Crear nuevo producto paca si no existe
        $query = "INSERT INTO producto (
        nombre, descripcion, idcategoria, unidad_medida, 
        precio, existencia, estatus, fecha_creacion
    ) VALUES (?, ?, ?, 'kg', 0, 0, 'activo', NOW())";

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
        // Obtener información del producto origen
        $query = "SELECT nombre, idcategoria FROM producto WHERE idproducto = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$idProductoOrigen]);
        $productoOrigen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productoOrigen) {
            throw new Exception("Producto origen no encontrado");
        }

        // Buscar si ya existe un producto clasificado correspondiente
        $nombreClasificado = $productoOrigen['nombre'] . " Clasificado";

        $query = "SELECT idproducto FROM producto WHERE nombre = ? AND estatus = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombreClasificado]);
        $productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoExistente) {
            return $productoExistente['idproducto'];
        }

        // Crear nuevo producto clasificado si no existe
        $query = "INSERT INTO producto (
        nombre, descripcion, idcategoria, unidad_medida, 
        precio, existencia, estatus, fecha_creacion
    ) VALUES (?, ?, ?, 'kg', 0, 0, 'activo', NOW())";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $nombreClasificado,
            "Material clasificado obtenido de: " . $productoOrigen['nombre'],
            $productoOrigen['idcategoria']
        ]);

        return $db->lastInsertId();
    }
}
