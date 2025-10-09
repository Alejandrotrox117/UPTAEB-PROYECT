
   
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


    public function insertLote(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $config = $this->obtenerConfiguracion($db);
            $operariosRequeridos = ceil($data['volumen_estimado'] / $config['productividad_clasificacion']);

            if ($operariosRequeridos > $config['capacidad_maxima_planta']) {
                return [
                    'status' => false,
                    'message' => "Se requieren {$operariosRequeridos} operarios pero la capacidad máxima es {$config['capacidad_maxima_planta']}"
                ];
            }

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
                'idlote' => $loteId, // Agregado para compatibilidad
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

            $query = "SELECT estatus_lote FROM lotes_produccion WHERE idlote = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                return [
                    'status' => false,
                    'message' => 'El lote no existe.'
                ];
            }

            if ($lote['estatus_lote'] === 'FINALIZADO') {
                return [
                    'status' => false,
                    'message' => 'El lote ya está finalizado.'
                ];
            }

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
                'message' => 'Error al cerrar lote: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }


    public function registrarProcesoClasificacion(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

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

            $numeroMovimiento = 'CLA-' . date('YmdHis') . '-' . $data['idempleado'];
            $observaciones = "Clasificación - Lote: {$data['idlote']}, Operario: {$data['idempleado']}, ";
            $observaciones .= "Procesado: {$data['kg_procesados']} kg, Limpio: {$data['kg_limpios']} kg, ";
            $observaciones .= "Contaminantes: {$data['kg_contaminantes']} kg";
            if (!empty($data['observaciones'])) {
                $observaciones .= " - " . $data['observaciones'];
            }

            $query = "INSERT INTO movimientos_existencia (
            numero_movimiento, idproducto, idtipomovimiento, 
            cantidad_salida, stock_anterior, stock_resultante, 
            total, observaciones, estatus, fecha_creacion
        ) VALUES (?, ?, 3, ?, ?, ?, ?, ?, 'activo', NOW())";

            $queryStock = "SELECT existencia FROM producto WHERE idproducto = ?";
            $stmtStock = $db->prepare($queryStock);
            $stmtStock->execute([$data['idproducto_origen']]);
            $stockActual = $stmtStock->fetchColumn();

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimiento,
                $data['idproducto_origen'],
                $data['kg_procesados'],
                $stockActual + $data['kg_procesados'],
                $stockActual,
                $stockActual,
                $observaciones
            ]);

            if ($data['kg_limpios'] > 0) {
                $idProductoClasificado = $this->obtenerOCrearProductoClasificado($data['idproducto_origen'], $db);

                $query = "UPDATE producto 
                SET existencia = existencia + ?
                WHERE idproducto = ?";

                $stmt = $db->prepare($query);
                $stmt->execute([$data['kg_limpios'], $idProductoClasificado]);

                $numeroMovimientoEntrada = 'CLA-E-' . date('YmdHis') . '-' . $data['idempleado'];
                $observacionesEntrada = "Material clasificado - Lote: {$data['idlote']}, ";
                $observacionesEntrada .= "Material limpio obtenido: {$data['kg_limpios']} kg";

                $query = "INSERT INTO movimientos_existencia (
                numero_movimiento, idproducto, idtipomovimiento, 
                cantidad_entrada, stock_anterior, stock_resultante,
                total, observaciones, estatus, fecha_creacion
            ) VALUES (?, ?, 3, ?, ?, ?, ?, ?, 'activo', NOW())";

                $queryStockClasificado = "SELECT existencia FROM producto WHERE idproducto = ?";
                $stmtStockClasificado = $db->prepare($queryStockClasificado);
                $stmtStockClasificado->execute([$idProductoClasificado]);
                $stockClasificado = $stmtStockClasificado->fetchColumn();

                $stmt = $db->prepare($query);
                $stmt->execute([
                    $numeroMovimientoEntrada,
                    $idProductoClasificado,
                    $data['kg_limpios'],
                    $stockClasificado - $data['kg_limpios'],
                    $stockClasificado,
                    $stockClasificado,
                    $observacionesEntrada
                ]);
            }

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

            $config = $this->obtenerConfiguracion($db);
            if (
                $data['peso_paca'] < $config['peso_minimo_paca'] ||
                $data['peso_paca'] > $config['peso_maximo_paca']
            ) {
                throw new Exception("El peso debe estar entre {$config['peso_minimo_paca']} y {$config['peso_maximo_paca']} kg");
            }

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

            $numeroMovimientoSalida = 'EMP-S-' . date('YmdHis') . '-' . $data['idempleado'];
            $observacionesSalida = "Empaque - Material usado - Lote: {$data['idlote']}, ";
            $observacionesSalida .= "Operario: {$data['idempleado']}, Peso: {$data['peso_paca']} kg, ";
            $observacionesSalida .= "Calidad: {$data['calidad']}";
            if (!empty($data['observaciones'])) {
                $observacionesSalida .= " - " . $data['observaciones'];
            }

            $query = "INSERT INTO movimientos_existencia (
            numero_movimiento, idproducto, idtipomovimiento, 
            cantidad_salida, stock_anterior, stock_resultante,
            total, observaciones, estatus, fecha_creacion
        ) VALUES (?, ?, 4, ?, ?, ?, ?, ?, 'activo', NOW())";

            $queryStock = "SELECT existencia FROM producto WHERE idproducto = ?";
            $stmtStock = $db->prepare($queryStock);
            $stmtStock->execute([$data['idproducto_clasificado']]);
            $stockActual = $stmtStock->fetchColumn();

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimientoSalida,
                $data['idproducto_clasificado'],
                $data['peso_paca'],
                $stockActual + $data['peso_paca'],
                $stockActual,
                $stockActual,
                $observacionesSalida
            ]);

            $idProductoPacas = $this->obtenerOCrearProductoPacas($data['idproducto_clasificado'], $data['calidad'], $db);

            $query = "UPDATE producto 
            SET existencia = existencia + ?
            WHERE idproducto = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$data['peso_paca'], $idProductoPacas]);

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
            cantidad_entrada, stock_anterior, stock_resultante,
            total, observaciones, estatus, fecha_creacion
        ) VALUES (?, ?, 4, ?, ?, ?, ?, ?, 'activo', NOW())";

            $queryStockPacas = "SELECT existencia FROM producto WHERE idproducto = ?";
            $stmtStockPacas = $db->prepare($queryStockPacas);
            $stmtStockPacas->execute([$idProductoPacas]);
            $stockPacas = $stmtStockPacas->fetchColumn();

            $stmt = $db->prepare($query);
            $stmt->execute([
                $numeroMovimientoEntrada,
                $idProductoPacas,
                $data['peso_paca'],
                $stockPacas - $data['peso_paca'],
                $stockPacas,
                $stockPacas,
                $observacionesEntrada
            ]);

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
    // MÉTODOS PARA OBTENER PROCESOS POR LOTE
    // ========================================
    
    public function obtenerProcesosClasificacionPorLote($idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                me.idmovimiento_existencia,
                me.numero_movimiento,
                me.cantidad_salida as kg_procesados,
                me.observaciones,
                me.fecha_creacion,
                p.idproducto,
                p.nombre as producto_nombre,
                p.codigo as producto_codigo,
                DATE_FORMAT(me.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
            FROM movimientos_existencia me
            INNER JOIN producto p ON me.idproducto = p.idproducto
            WHERE me.idtipomovimiento = 3 
            AND me.observaciones LIKE CONCAT('%Lote: ', ?, '%')
            AND me.observaciones LIKE '%Clasificación%'
            AND me.cantidad_salida > 0
            ORDER BY me.fecha_creacion DESC";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $procesos = [];
            foreach ($movimientos as $mov) {
                // Extraer información de las observaciones
                $obs = $mov['observaciones'];
                
                // Extraer operario
                preg_match('/Operario: (\d+)/', $obs, $matchesOperario);
                $idempleado = isset($matchesOperario[1]) ? $matchesOperario[1] : null;
                
                // Extraer kg procesados, limpios y contaminantes
                preg_match('/Procesado: ([\d.]+) kg/', $obs, $matchesProcesado);
                preg_match('/Limpio: ([\d.]+) kg/', $obs, $matchesLimpio);
                preg_match('/Contaminantes: ([\d.]+) kg/', $obs, $matchesContaminantes);
                
                $kgProcesados = isset($matchesProcesado[1]) ? $matchesProcesado[1] : $mov['kg_procesados'];
                $kgLimpios = isset($matchesLimpio[1]) ? $matchesLimpio[1] : 0;
                $kgContaminantes = isset($matchesContaminantes[1]) ? $matchesContaminantes[1] : 0;
                
                // Obtener nombre del operario
                $nombreOperario = '-';
                if ($idempleado) {
                    $queryEmpleado = "SELECT CONCAT(nombre, ' ', apellido) as nombre_completo 
                                     FROM empleado WHERE idempleado = ?";
                    $stmtEmpleado = $db->prepare($queryEmpleado);
                    $stmtEmpleado->execute([$idempleado]);
                    $empleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
                    if ($empleado) {
                        $nombreOperario = $empleado['nombre_completo'];
                    }
                }
                
                $procesos[] = [
                    'idmovimiento' => $mov['idmovimiento_existencia'],
                    'numero_movimiento' => $mov['numero_movimiento'],
                    'idempleado' => $idempleado,
                    'operario_nombre' => $nombreOperario,
                    'empleado_nombre' => $nombreOperario,
                    'idproducto_origen' => $mov['idproducto'],
                    'producto_nombre' => $mov['producto_nombre'],
                    'nombre_producto' => $mov['producto_nombre'],
                    'producto_codigo' => $mov['producto_codigo'],
                    'kg_procesados' => $kgProcesados,
                    'kg_limpios' => $kgLimpios,
                    'kg_contaminantes' => $kgContaminantes,
                    'observaciones' => $obs,
                    'fecha_creacion' => $mov['fecha_creacion'],
                    'fecha_formato' => $mov['fecha_formato']
                ];
            }

            return $procesos;

        } catch (Exception $e) {
            error_log("Error al obtener procesos de clasificación: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    public function obtenerProcesosEmpaquePorLote($idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                me.idmovimiento_existencia,
                me.numero_movimiento,
                me.cantidad_entrada as peso_paca,
                me.observaciones,
                me.fecha_creacion,
                p.idproducto,
                p.nombre as producto_nombre,
                p.codigo as producto_codigo,
                DATE_FORMAT(me.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
            FROM movimientos_existencia me
            INNER JOIN producto p ON me.idproducto = p.idproducto
            WHERE me.idtipomovimiento = 4 
            AND me.observaciones LIKE CONCAT('%Lote: ', ?, '%')
            AND me.observaciones LIKE '%Paca empacada%'
            AND me.cantidad_entrada > 0
            ORDER BY me.fecha_creacion DESC";

            $stmt = $db->prepare($query);
            $stmt->execute([$idlote]);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $procesos = [];
            foreach ($movimientos as $mov) {
                // Extraer información de las observaciones
                $obs = $mov['observaciones'];
                
                // Extraer operario
                preg_match('/Operario: (\d+)/', $obs, $matchesOperario);
                $idempleado = isset($matchesOperario[1]) ? $matchesOperario[1] : null;
                
                // Extraer peso
                preg_match('/Peso: ([\d.]+) kg/', $obs, $matchesPeso);
                $pesoPaca = isset($matchesPeso[1]) ? $matchesPeso[1] : $mov['peso_paca'];
                
                // Extraer calidad
                preg_match('/Calidad: (\w+)/', $obs, $matchesCalidad);
                $calidad = isset($matchesCalidad[1]) ? $matchesCalidad[1] : 'ESTANDAR';
                
                // Extraer código de paca
                preg_match('/Código: (PACA-[\w-]+)/', $obs, $matchesCodigo);
                $codigoPaca = isset($matchesCodigo[1]) ? $matchesCodigo[1] : '-';
                
                // Obtener nombre del operario
                $nombreOperario = '-';
                if ($idempleado) {
                    $queryEmpleado = "SELECT CONCAT(nombre, ' ', apellido) as nombre_completo 
                                     FROM empleado WHERE idempleado = ?";
                    $stmtEmpleado = $db->prepare($queryEmpleado);
                    $stmtEmpleado->execute([$idempleado]);
                    $empleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
                    if ($empleado) {
                        $nombreOperario = $empleado['nombre_completo'];
                    }
                }
                
                // Extraer observaciones adicionales (después del último guion)
                $observacionesAdicionales = '';
                if (preg_match('/ - (.+)$/', $obs, $matchesObs)) {
                    $observacionesAdicionales = $matchesObs[1];
                }
                
                $procesos[] = [
                    'idmovimiento' => $mov['idmovimiento_existencia'],
                    'numero_movimiento' => $mov['numero_movimiento'],
                    'codigo_paca' => $codigoPaca,
                    'idempleado' => $idempleado,
                    'operario_nombre' => $nombreOperario,
                    'empleado_nombre' => $nombreOperario,
                    'idproducto_clasificado' => $mov['idproducto'],
                    'producto_nombre' => $mov['producto_nombre'],
                    'nombre_producto' => $mov['producto_nombre'],
                    'producto_codigo' => $mov['producto_codigo'],
                    'peso_paca' => $pesoPaca,
                    'calidad' => $calidad,
                    'observaciones' => $observacionesAdicionales,
                    'observaciones_completas' => $obs,
                    'fecha_creacion' => $mov['fecha_creacion'],
                    'fecha_formato' => $mov['fecha_formato']
                ];
            }

            return $procesos;

        } catch (Exception $e) {
            error_log("Error al obtener procesos de empaque: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }


    public function registrarProduccionDiariaLote(int $idlote, array $registros)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            foreach ($registros as $registro) {
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


    public function selectProcesosRecientes(string $fecha)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                DATE_FORMAT(me.fecha_creacion, '%d/%m/%Y %H:%i') as fecha,
                CASE 
                    WHEN me.idtipomovimiento = 3 THEN 'CLASIFICACIÓN'
                    WHEN me.idtipomovimiento = 4 THEN 'EMPAQUE'
                    ELSE 'OTRO'
                END as proceso,
                CASE 
                    WHEN me.cantidad_salida > 0 THEN CONCAT(me.cantidad_salida, ' kg procesados')
                    WHEN me.cantidad_entrada > 0 THEN CONCAT(me.cantidad_entrada, ' kg producidos')
                    ELSE 'N/A'
                END as cantidad,
                me.observaciones,
                p.nombre as producto,
                COALESCE(
                    CONCAT(e.nombre, ' ', e.apellido),
                    'No especificado'
                ) as operario
            FROM movimientos_existencia me
            LEFT JOIN producto p ON me.idproducto = p.idproducto
            LEFT JOIN empleado e ON e.idempleado = CAST(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(me.observaciones, 'Operario: ', -1), 
                    ',', 1
                ) AS UNSIGNED
            )
            WHERE DATE(me.fecha_creacion) = ?
            AND me.idtipomovimiento IN (3, 4)
            AND me.observaciones LIKE '%Operario:%'
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

    public function selectRegistrosNomina()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT 
                rp.idregistro,
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
           
            ORDER BY rp.fecha_jornada DESC, e.nombre ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
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
     public function registrarSolicitudPago(array $registros = [])
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
                    $querySueldo = "INSERT INTO sueldos (idempleado, monto, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmtSueldo = $db->prepare($querySueldo);
                    $stmtSueldo->execute([
                        $registro['idempleado'],
                        $monto,
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

    // ============================================================
    // MÉTODOS PARA REGISTRO_PRODUCCION
    // ============================================================

    /**
     * Inserta un nuevo registro de producción
     * Calcula automáticamente los salarios según configuración
     */
    public function insertarRegistroProduccion(array $data)
    {
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
            $stmtLote->execute([$data['idlote']]);
            if (!$stmtLote->fetch()) {
                throw new Exception("El lote especificado no existe");
            }

            // Validar que el empleado existe
            if (!empty($data['idempleado'])) {
                $queryEmpleado = "SELECT idempleado FROM empleado WHERE idempleado = ?";
                $stmtEmpleado = $db->prepare($queryEmpleado);
                $stmtEmpleado->execute([$data['idempleado']]);
                if (!$stmtEmpleado->fetch()) {
                    throw new Exception("El empleado especificado no existe");
                }
            }

            // Calcular salarios
            $salario_base_dia = floatval($config['salario_base'] ?? 30.00);
            $cantidad_producida = floatval($data['cantidad_producida']);
            
            // Pago por clasificación/trabajo según cantidad producida
            // Fórmula: beta * cantidad_producida (para clasificación)
            // O: gamma por cada unidad (para empaque)
            if ($data['tipo_movimiento'] === 'CLASIFICACION') {
                $beta = floatval($config['beta_clasificacion'] ?? 0.25);
                $pago_clasificacion_trabajo = $beta * $cantidad_producida;
            } else { // EMPAQUE
                $gamma = floatval($config['gamma_empaque'] ?? 5.00);
                $pago_clasificacion_trabajo = $gamma * $cantidad_producida;
            }

            // Salario total
            $salario_total = $salario_base_dia + $pago_clasificacion_trabajo;

            // Insertar registro
            $query = "INSERT INTO registro_produccion (
                idlote, idempleado, fecha_jornada, idproducto_producir, cantidad_producir,
                idproducto_terminado, cantidad_producida, salario_base_dia,
                pago_clasificacion_trabajo, salario_total, tipo_movimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['idlote'],
                $data['idempleado'] ?? null,
                $data['fecha_jornada'],
                $data['idproducto_producir'],
                floatval($data['cantidad_producir']),
                $data['idproducto_terminado'],
                $cantidad_producida,
                $salario_base_dia,
                $pago_clasificacion_trabajo,
                $salario_total,
                $data['tipo_movimiento'],
                $data['observaciones'] ?? ''
            ]);

            $idregistro = $db->lastInsertId();
            $db->commit();

            return [
                'status' => true,
                'message' => 'Registro de producción guardado exitosamente',
                'idregistro' => $idregistro,
                'salarios' => [
                    'salario_base_dia' => $salario_base_dia,
                    'pago_clasificacion_trabajo' => $pago_clasificacion_trabajo,
                    'salario_total' => $salario_total
                ]
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("[PRODUCCION] Error al insertar registro de producción: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al guardar registro: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Obtiene todos los registros de producción por lote
     */
    public function obtenerRegistrosPorLote($idlote)
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

    /**
     * Obtiene todos los registros de producción con filtros opcionales
     */
    public function selectAllRegistrosProduccion($filtros = [])
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

    /**
     * Actualiza un registro de producción
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
            $queryVerificar = "SELECT rp.idregistro, rp.estatus, rp.idlote 
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

            // Obtener configuración para recalcular salarios
            $config = $this->obtenerConfiguracion($db);

            // Recalcular salarios
            $salario_base_dia = floatval($config['salario_base'] ?? 30.00);
            $cantidad_producida = floatval($data['cantidad_producida']);
            
            if ($data['tipo_movimiento'] === 'CLASIFICACION') {
                $beta = floatval($config['beta_clasificacion'] ?? 0.25);
                $pago_clasificacion_trabajo = $beta * $cantidad_producida;
            } else {
                $gamma = floatval($config['gamma_empaque'] ?? 5.00);
                $pago_clasificacion_trabajo = $gamma * $cantidad_producida;
            }

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
            $queryVerificar = "SELECT rp.idregistro, rp.estatus, rp.idlote 
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
            
            $query = "DELETE FROM registro_produccion WHERE idregistro = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idregistro]);
            
            error_log("Registro eliminado exitosamente");

            return [
                'status' => true,
                'message' => 'Registro eliminado exitosamente',
                'idlote' => $registro['idlote']
            ];

        } catch (Exception $e) {
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
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Verificar que el lote existe y está en estado PLANIFICADO
            $query = "SELECT estatus_lote FROM lotes_produccion WHERE idlote = ?";
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
                    'message' => 'Solo se pueden editar lotes en estado PLANIFICADO'
                ];
            }

            // Validar y recalcular operarios requeridos si cambió el volumen
            if (isset($data['volumen_estimado'])) {
                $config = $this->obtenerConfiguracion($db);
                $operariosRequeridos = ceil($data['volumen_estimado'] / $config['productividad_clasificacion']);

                if ($operariosRequeridos > $config['capacidad_maxima_planta']) {
                    return [
                        'status' => false,
                        'message' => "Se requieren {$operariosRequeridos} operarios pero la capacidad máxima es {$config['capacidad_maxima_planta']}"
                    ];
                }
            }

            // Construir la consulta de actualización dinámicamente
            $updateFields = [];
            $updateValues = [];

            if (isset($data['fecha_jornada'])) {
                $updateFields[] = "fecha_jornada = ?";
                $updateValues[] = $data['fecha_jornada'];
            }

            if (isset($data['volumen_estimado'])) {
                $updateFields[] = "volumen_estimado = ?";
                $updateValues[] = $data['volumen_estimado'];
                $updateFields[] = "operarios_requeridos = ?";
                $updateValues[] = $operariosRequeridos;
            }

            if (isset($data['idsupervisor'])) {
                $updateFields[] = "idsupervisor = ?";
                $updateValues[] = $data['idsupervisor'];
            }

            if (isset($data['observaciones'])) {
                $updateFields[] = "observaciones = ?";
                $updateValues[] = $data['observaciones'];
            }

            if (empty($updateFields)) {
                return [
                    'status' => false,
                    'message' => 'No hay campos para actualizar'
                ];
            }

            $updateValues[] = $idlote;
            $query = "UPDATE lotes_produccion SET " . implode(", ", $updateFields) . " WHERE idlote = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute($updateValues);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Lote actualizado exitosamente'
            ];

        } catch (Exception $e) {
            $db->rollback();
            error_log("[PRODUCCION] Error al actualizar lote: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al actualizar lote: ' . $e->getMessage()
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
}


