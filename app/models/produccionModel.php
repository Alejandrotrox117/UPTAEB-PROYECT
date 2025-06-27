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
    private $loteId;
    private $message;
    private $status;

    public function __construct()
    {
        // Constructor vacío siguiendo el patrón del proyecto
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

    public function getLoteId(){
        return $this->loteId;
    }

    public function setLoteId(?int $loteId){
        $this->loteId = $loteId;
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

    // ========================================
    // MÉTODOS PRIVADOS PARA GESTIÓN DE LOTES
    // ========================================

    private function calcularOperariosRequeridos($volumenEstimado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener productividad de configuración
            $this->setQuery("SELECT productividad_clasificacion, capacidad_maxima_planta FROM configuracion_produccion WHERE estatus = 'activo' ORDER BY fecha_creacion DESC LIMIT 1");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$config) {
                $productividad = 150; // Valor por defecto
                $capacidadMaxima = 50;
            } else {
                $productividad = $config['productividad_clasificacion'];
                $capacidadMaxima = $config['capacidad_maxima_planta'];
            }

            // Calcular N_total = ⌈V_día/p_clasificación⌉
            $operariosRequeridos = ceil($volumenEstimado / $productividad);
            
            // Validar que no exceda la capacidad máxima de planta
            if ($operariosRequeridos > $capacidadMaxima) {
                return [
                    'status' => false,
                    'operarios_requeridos' => $operariosRequeridos,
                    'capacidad_maxima' => $capacidadMaxima,
                    'message' => "Se requieren {$operariosRequeridos} operarios pero la capacidad máxima es {$capacidadMaxima}"
                ];
            }

            return [
                'status' => true,
                'operarios_requeridos' => $operariosRequeridos,
                'capacidad_maxima' => $capacidadMaxima
            ];

        } catch (Exception $e) {
            error_log("Error al calcular operarios requeridos: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al calcular operarios requeridos'
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function generarNumeroLote($fechaJornada)
    {
        // Generar número de lote: LOTE-YYYYMMDD-XXX
        $fecha = date('Ymd', strtotime($fechaJornada));
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Contar lotes existentes para la fecha
            $this->setQuery("SELECT COUNT(*) as total FROM lotes_produccion WHERE fecha_jornada = ?");
            $this->setArray([$fechaJornada]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $consecutivo = ($result['total'] ?? 0) + 1;
            $numeroLote = "LOTE-{$fecha}-" . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);
            
            return $numeroLote;

        } catch (Exception $e) {
            error_log("Error al generar número de lote: " . $e->getMessage());
            return "LOTE-{$fecha}-001";
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionLote(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Calcular operarios requeridos
            $calculoOperarios = $this->calcularOperariosRequeridos($data['volumen_estimado']);
            if (!$calculoOperarios['status']) {
                return [
                    'status' => false,
                    'message' => $calculoOperarios['message'],
                    'lote_id' => null
                ];
            }

            // Generar número de lote
            $numeroLote = $this->generarNumeroLote($data['fecha_jornada']);

            $this->setQuery(
                "INSERT INTO lotes_produccion (
                    numero_lote, fecha_jornada, volumen_estimado, 
                    operarios_requeridos, idsupervisor, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            $this->setArray([
                $numeroLote,
                $data['fecha_jornada'],
                $data['volumen_estimado'],
                $calculoOperarios['operarios_requeridos'],
                $data['idsupervisor'],
                $data['observaciones']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setLoteId($db->lastInsertId());
            
            if ($this->getLoteId()) {
                $this->setStatus(true);
                $this->setMessage('Lote de producción registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de lote tras registro.');
            }
            
            return [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'lote_id' => $this->getLoteId(),
                'numero_lote' => $numeroLote,
                'operarios_requeridos' => $calculoOperarios['operarios_requeridos']
            ];
            
        } catch (Exception $e) {
            error_log("Error al insertar lote: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al registrar lote: ' . $e->getMessage(),
                'lote_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaTodosLotes()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    l.idlote, l.numero_lote, l.fecha_jornada, l.volumen_estimado,
                    l.operarios_requeridos, l.operarios_asignados, l.estatus_lote,
                    l.observaciones, l.fecha_inicio_real, l.fecha_fin_real,
                    CONCAT(e.nombre, ' ', e.apellido) as supervisor,
                    DATE_FORMAT(l.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato,
                    DATE_FORMAT(l.fecha_inicio_real, '%d/%m/%Y %H:%i') as fecha_inicio_formato,
                    DATE_FORMAT(l.fecha_fin_real, '%d/%m/%Y %H:%i') as fecha_fin_formato,
                    DATE_FORMAT(l.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    -- Estadísticas del lote
                    COALESCE(stats.total_kg_clasificados, 0) as total_kg_clasificados,
                    COALESCE(stats.total_kg_contaminantes, 0) as total_kg_contaminantes,
                    COALESCE(stats.total_pacas_armadas, 0) as total_pacas_armadas,
                    COALESCE(stats.promedio_tasa_error, 0) as promedio_tasa_error,
                    COALESCE(stats.total_nomina, 0) as total_nomina
                FROM lotes_produccion l
                LEFT JOIN empleado e ON l.idsupervisor = e.idempleado
                LEFT JOIN (
                    SELECT 
                        idlote,
                        SUM(kg_clasificados) as total_kg_clasificados,
                        SUM(kg_contaminantes) as total_kg_contaminantes,
                        SUM(pacas_armadas) as total_pacas_armadas,
                        AVG(tasa_error) as promedio_tasa_error,
                        SUM(salario_total) as total_nomina
                    FROM registro_produccion 
                    GROUP BY idlote
                ) stats ON l.idlote = stats.idlote
                ORDER BY l.fecha_jornada DESC, l.fecha_creacion DESC"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Lotes obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaTodosLotes - Error: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener lotes: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaLotePorId(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    l.*,
                    CONCAT(e.nombre, ' ', e.apellido) as supervisor,
                    DATE_FORMAT(l.fecha_jornada, '%d/%m/%Y') as fecha_jornada_formato,
                    DATE_FORMAT(l.fecha_inicio_real, '%d/%m/%Y %H:%i') as fecha_inicio_formato,
                    DATE_FORMAT(l.fecha_fin_real, '%d/%m/%Y %H:%i') as fecha_fin_formato
                FROM lotes_produccion l
                LEFT JOIN empleado e ON l.idsupervisor = e.idempleado
                WHERE l.idlote = ?"
            );
            
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (Exception $e) {
            error_log("ProduccionModel::ejecutarBusquedaLotePorId -> " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    // ========================================
    // MÉTODOS PRIVADOS PARA ASIGNACIONES
    // ========================================

    private function ejecutarAsignacionOperarios(int $idlote, array $operarios)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Limpiar asignaciones existentes
            $this->setQuery("DELETE FROM asignaciones_operarios WHERE idlote = ?");
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            $operariosAsignados = 0;
            
            // Insertar nuevas asignaciones
            foreach ($operarios as $operario) {
                $this->setQuery(
                    "INSERT INTO asignaciones_operarios (
                        idlote, idempleado, tipo_tarea, turno, observaciones
                    ) VALUES (?, ?, ?, ?, ?)"
                );
                
                $this->setArray([
                    $idlote,
                    intval($operario['idempleado']),
                    $operario['tipo_tarea'],
                    $operario['turno'] ?? 'MAÑANA',
                    $operario['observaciones'] ?? ''
                ]);
                
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute($this->getArray());
                $operariosAsignados++;
            }

            // Actualizar contador en lote
            $this->setQuery("UPDATE lotes_produccion SET operarios_asignados = ? WHERE idlote = ?");
            $this->setArray([$operariosAsignados, $idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

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

    private function ejecutarBusquedaOperariosDisponibles(string $fecha)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
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
                    )
                LEFT JOIN lotes_produccion l ON a.idlote = l.idlote AND l.fecha_jornada = ?
                WHERE e.estatus = 'Activo'
                ORDER BY e.nombre, e.apellido"
            );
            
            $this->setArray([$fecha, $fecha]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Operarios disponibles obtenidos.",
                "data" => $this->getResult()
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
    // MÉTODOS PRIVADOS PARA PROCESOS
    // ========================================

    private function ejecutarRegistroClasificacion(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // 1. Descontar material "Por Clasificar" del stock
            $this->setQuery(
                "UPDATE producto 
                SET 
                    existencia = existencia - ?,
                    ultima_modificacion = NOW()
                WHERE idproducto = ? AND existencia >= ?"
            );
            
            $this->setArray([
                $data['kg_procesados'],
                $data['idproducto_origen'],
                $data['kg_procesados']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Stock insuficiente del producto origen");
            }

            // 2. Sumar material clasificado al stock
            $this->setQuery(
                "UPDATE producto 
                SET 
                    existencia = existencia + ?,
                    ultima_modificacion = NOW()
                WHERE idproducto = ?"
            );
            
            $this->setArray([
              
                $data['kg_limpios'],
                $data['idproducto_clasificado']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // 3. Registrar movimiento de salida (material por clasificar)
            $numeroMovimientoSalida = 'MOV-CLA-' . date('YmdHis') . '-' . $data['idempleado'];
            $this->setQuery(
                "INSERT INTO movimientos_existencia (
                    numero_movimiento, idproducto, idtipomovimiento, idlote, 
                    idempleado, cantidad_salida, tipo_proceso, observaciones,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $this->setArray([
                $numeroMovimientoSalida,
                $data['idproducto_origen'],
                3, // Tipo movimiento clasificación
                $data['idlote'],
                $data['idempleado'],
                $data['kg_procesados'],
                'CLASIFICACION',
                'Salida por clasificación: ' . ($data['observaciones'] ?? '')
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // 4. Registrar movimiento de entrada (material clasificado)
            $numeroMovimientoEntrada = 'MOV-CLA-E-' . date('YmdHis') . '-' . $data['idempleado'];
            $this->setQuery(
                "INSERT INTO movimientos_existencia (
                    numero_movimiento, idproducto, idtipomovimiento, idlote, 
                    idempleado, cantidad_entrada, tipo_proceso, peso_contaminante,
                    observaciones, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $this->setArray([
                $numeroMovimientoEntrada,
                $data['idproducto_clasificado'],
                3, // Tipo movimiento clasificación
                $data['idlote'],
                $data['idempleado'],
                $data['kg_limpios'],
                'CLASIFICACION',
                $data['kg_contaminantes'],
                'Entrada por clasificación: ' . ($data['observaciones'] ?? '')
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // 5. Actualizar o crear registro de producción del operario
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
                'movimiento_salida' => $numeroMovimientoSalida,
                'movimiento_entrada' => $numeroMovimientoEntrada
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

    private function ejecutarRegistroEmpaque(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            // Validar peso de paca dentro de rangos permitidos
            $config = $this->obtenerConfiguracionProduccion($db);
            if ($data['peso_paca'] < $config['peso_minimo_paca'] || 
                $data['peso_paca'] > $config['peso_maximo_paca']) {
                throw new Exception("El peso de la paca debe estar entre {$config['peso_minimo_paca']} y {$config['peso_maximo_paca']} kg");
            }

            // 1. Descontar material clasificado del stock
            $this->setQuery(
                "UPDATE producto 
                SET 
                    existencia = existencia - ?,
                    ultima_modificacion = NOW()
                WHERE idproducto = ? AND existencia >= ?"
            );
            
            $this->setArray([
                $data['peso_paca'],
                $data['idproducto_clasificado'],
                $data['peso_paca']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Stock insuficiente del producto clasificado");
            }

            // 2. Crear la paca
            $codigoPaca = 'PACA-' . date('Ymd') . '-' . str_pad($data['idempleado'], 3, '0', STR_PAD_LEFT) . '-' . date('His');
            $this->setQuery(
                "INSERT INTO pacas (
                    codigo_paca, idproducto_origen, idproducto_paca, peso_paca,
                    idusuario_creador, idlote, calidad, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            // Asumir que existe un producto "Paca" con ID específico, o usar el mismo producto clasificado
            $idProductoPaca = $data['idproducto_clasificado']; // Modificar según lógica de negocio
            
            $this->setArray([
                $codigoPaca,
                $data['idproducto_clasificado'],
                $idProductoPaca,
                $data['peso_paca'],
                $data['idempleado'],
                $data['idlote'],
                $data['calidad'],
                $data['observaciones']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $idPaca = $db->lastInsertId();

            // 3. Registrar movimiento de empaque
            $numeroMovimiento = 'MOV-EMP-' . date('YmdHis') . '-' . $data['idempleado'];
            $this->setQuery(
                "INSERT INTO movimientos_existencia (
                    numero_movimiento, idproducto, idtipomovimiento, idlote, 
                    idempleado, cantidad_salida, tipo_proceso, idpaca,
                    observaciones, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $this->setArray([
                $numeroMovimiento,
                $data['idproducto_clasificado'],
                4, // Tipo movimiento empaque
                $data['idlote'],
                $data['idempleado'],
                $data['peso_paca'],
                'EMPAQUE',
                $idPaca,
                'Empaque en paca ' . $codigoPaca . ': ' . ($data['observaciones'] ?? '')
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // 4. Actualizar registro de producción del operario
            $this->actualizarRegistroProduccionOperario([
                'idlote' => $data['idlote'],
                'idempleado' => $data['idempleado'],
                'pacas_armadas_adicionales' => 1
            ], $db);

            $db->commit();
            
            return [
                'status' => true,
                'message' => 'Paca empacada exitosamente.',
                'codigo_paca' => $codigoPaca,
                'idpaca' => $idPaca,
                'movimiento' => $numeroMovimiento
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

    private function actualizarRegistroProduccionOperario(array $data, $db)
    {
        try {
            // Obtener fecha del lote
            $this->setQuery("SELECT fecha_jornada FROM lotes_produccion WHERE idlote = ?");
            $this->setArray([$data['idlote']]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lote) {
                throw new Exception("Lote no encontrado");
            }

            // Verificar si ya existe registro para este operario en esta fecha
            $this->setQuery(
                "SELECT idregistro FROM registro_produccion 
                WHERE fecha_jornada = ? AND idempleado = ?"
            );
            $this->setArray([$lote['fecha_jornada'], $data['idempleado']]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
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
                    $this->setQuery(
                        "UPDATE registro_produccion SET " . 
                        implode(", ", $updateFields) . " WHERE idregistro = ?"
                    );
                    $this->setArray($updateValues);
                    $stmt = $db->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                }
            } else {
                // Crear nuevo registro
                $this->setQuery(
                    "INSERT INTO registro_produccion (
                        fecha_jornada, idlote, idempleado, kg_clasificados, 
                        kg_contaminantes, pacas_armadas, estatus
                    ) VALUES (?, ?, ?, ?, ?, ?, 'BORRADOR')"
                );
                
                $this->setArray([
                    $lote['fecha_jornada'],
                    $data['idlote'],
                    $data['idempleado'],
                    $data['kg_clasificados_adicionales'] ?? 0,
                    $data['kg_contaminantes_adicionales'] ?? 0,
                    $data['pacas_armadas_adicionales'] ?? 0
                ]);
                
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute($this->getArray());
            }

        } catch (Exception $e) {
            throw new Exception("Error al actualizar registro de producción: " . $e->getMessage());
        }
    }

    private function obtenerConfiguracionProduccion($db)
    {
        $this->setQuery(
            "SELECT * FROM configuracion_produccion 
            WHERE estatus = 'activo' 
            ORDER BY fecha_creacion DESC LIMIT 1"
        );
        $stmt = $db->prepare($this->getQuery());
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            // Valores por defecto
            return [
                'peso_minimo_paca' => 25.00,
                'peso_maximo_paca' => 35.00,
                'salario_base' => 30.00,
                'beta_clasificacion' => 0.25,
                'gamma_empaque' => 5.00,
                'umbral_error_maximo' => 5.00,
                'penalizacion_beta' => 0.10
            ];
        }

        return $config;
    }

    // ========================================
    // MÉTODOS PÚBLICOS
    // ========================================

    public function insertLote(array $data)
    {
        $this->setData($data);
        return $this->ejecutarInsercionLote($this->getData());
    }

    public function selectAllLotes()
    {
        return $this->ejecutarBusquedaTodosLotes();
    }

    public function selectLoteById(int $idlote)
    {
        $this->setLoteId($idlote);
        return $this->ejecutarBusquedaLotePorId($this->getLoteId());
    }

    public function iniciarLoteProduccion(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "UPDATE lotes_produccion 
                SET estatus_lote = 'EN_PROCESO', fecha_inicio_real = NOW() 
                WHERE idlote = ? AND estatus_lote = 'PLANIFICADO'"
            );
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Lote iniciado exitosamente.'];
            } else {
                return ['status' => false, 'message' => 'No se pudo iniciar el lote. Verifique el estado actual.'];
            }

        } catch (Exception $e) {
            error_log("Error al iniciar lote: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al iniciar lote: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    public function asignarOperariosLote(int $idlote, array $operarios)
    {
        return $this->ejecutarAsignacionOperarios($idlote, $operarios);
    }

    public function selectOperariosDisponibles(string $fecha)
    {
        return $this->ejecutarBusquedaOperariosDisponibles($fecha);
    }

    public function selectAsignacionesLote(int $idlote)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    a.idasignacion, a.tipo_tarea, a.turno, a.estatus_asignacion,
                    a.hora_inicio, a.hora_fin, a.observaciones,
                    CONCAT(e.nombre, ' ', e.apellido) as operario,
                    e.idempleado, e.telefono_principal
                FROM asignaciones_operarios a
                INNER JOIN empleado e ON a.idempleado = e.idempleado
                WHERE a.idlote = ?
                ORDER BY a.tipo_tarea, e.nombre"
            );
            
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Asignaciones obtenidas.",
                "data" => $this->getResult()
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

    public function registrarProcesoClasiificacion(array $data)
    {
        return $this->ejecutarRegistroClasificacion($data);
    }

    public function registrarProcesoEmpaque(array $data)
    {
        return $this->ejecutarRegistroEmpaque($data);
    }

    public function registrarProduccionDiariaLote(int $idlote, array $registros)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            foreach ($registros as $registro) {
                // Calcular tasa de error individual
                $tasaError = 0;
                if ($registro['kg_clasificados'] > 0) {
                    $tasaError = ($registro['kg_contaminantes'] / $registro['kg_clasificados']) * 100;
                }

                // Insertar o actualizar registro de producción
                $this->setQuery(
                    "INSERT INTO registro_produccion (
                        fecha_jornada, idlote, idempleado, kg_clasificados,
                        kg_contaminantes, pacas_armadas, tasa_error, observaciones
                    ) VALUES (
                        (SELECT fecha_jornada FROM lotes_produccion WHERE idlote = ?),
                        ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        kg_clasificados = VALUES(kg_clasificados),
                        kg_contaminantes = VALUES(kg_contaminantes),
                        pacas_armadas = VALUES(pacas_armadas),
                        tasa_error = VALUES(tasa_error),
                        observaciones = VALUES(observaciones)"
                );
                
                $this->setArray([
                    $idlote, $idlote,
                    intval($registro['idempleado']),
                    floatval($registro['kg_clasificados']),
                    floatval($registro['kg_contaminantes']),
                    intval($registro['pacas_armadas']),
                    $tasaError,
                    trim($registro['observaciones'] ?? '')
                ]);
                
                $stmt = $db->prepare($this->getQuery());
                $stmt->execute($this->getArray());
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
                'message' => 'Error al registrar producción diaria: ' . $e->getMessage()
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

            // Obtener configuración
            $config = $this->obtenerConfiguracionProduccion($db);

            // Calcular tasa de error general del periodo
            $this->setQuery(
                "SELECT 
                    SUM(kg_clasificados) as total_clasificados,
                    SUM(kg_contaminantes) as total_contaminantes
                FROM registro_produccion 
                WHERE fecha_jornada BETWEEN ? AND ?"
            );
            $this->setArray([$fechaInicio, $fechaFin]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $totales = $stmt->fetch(PDO::FETCH_ASSOC);

            $tasaErrorGeneral = 0;
            if ($totales['total_clasificados'] > 0) {
                $tasaErrorGeneral = ($totales['total_contaminantes'] / $totales['total_clasificados']) * 100;
            }

            // Determinar si aplicar penalización
            $aplicarPenalizacion = $tasaErrorGeneral > $config['umbral_error_maximo'];
            $betaEfectivo = $aplicarPenalizacion ? 
                ($config['beta_clasificacion'] - $config['penalizacion_beta']) : 
                $config['beta_clasificacion'];

            // Actualizar registros con cálculos de nómina
            $this->setQuery(
                "UPDATE registro_produccion SET
                    salario_base_dia = ?,
                    bono_clasificacion = ? * kg_clasificados,
                    bono_empaque = ? * pacas_armadas,
                    penalizacion = CASE WHEN ? = 1 THEN ? * kg_clasificados ELSE 0 END,
                    salario_total = ? + (? * kg_clasificados) + (? * pacas_armadas),
                    estatus = 'CALCULADO',
                    fecha_calculo = NOW()
                WHERE fecha_jornada BETWEEN ? AND ? 
                AND estatus = 'BORRADOR'"
            );

            $penalizacionPorKg = $aplicarPenalizacion ? 
                ($config['beta_clasificacion'] - $betaEfectivo) : 0;

            $this->setArray([
                $config['salario_base'],           // salario_base_dia
                $betaEfectivo,                     // multiplicador bono_clasificacion
                $config['gamma_empaque'],          // multiplicador bono_empaque
                $aplicarPenalizacion ? 1 : 0,      // flag para penalización
                $penalizacionPorKg,                // monto penalización por kg
                $config['salario_base'],           // salario base en cálculo total
                $betaEfectivo,                     // beta efectivo en cálculo total
                $config['gamma_empaque'],          // gamma en cálculo total
                $fechaInicio,
                $fechaFin
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $registrosActualizados = $stmt->rowCount();

            // Generar alertas si hay alta tasa de error
            if ($aplicarPenalizacion) {
                $this->generarAlertaErrorAlto($tasaErrorGeneral, $fechaInicio, $fechaFin, $db);
            }

            $db->commit();
            
            return [
                'status' => true,
                'message' => "Nómina calculada exitosamente para {$registrosActualizados} registros.",
                'registros_actualizados' => $registrosActualizados,
                'tasa_error_general' => round($tasaErrorGeneral, 2),
                'penalizacion_aplicada' => $aplicarPenalizacion,
                'beta_efectivo' => $betaEfectivo
            ];
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error al calcular nómina: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al calcular nómina: ' . $e->getMessage()
            ];
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

            // Verificar que todos los operarios tengan registros de producción
            $this->setQuery(
                "SELECT COUNT(*) as operarios_asignados,
                        COUNT(rp.idregistro) as operarios_con_registro
                FROM asignaciones_operarios a
                LEFT JOIN registro_produccion rp ON a.idempleado = rp.idempleado 
                    AND rp.idlote = a.idlote
                WHERE a.idlote = ?"
            );
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($verificacion['operarios_asignados'] != $verificacion['operarios_con_registro']) {
                $faltantes = $verificacion['operarios_asignados'] - $verificacion['operarios_con_registro'];
                return [
                    'status' => false,
                    'message' => "No se puede cerrar el lote. Faltan registros de producción para {$faltantes} operarios."
                ];
            }

            // Verificar que todos los registros estén calculados
            $this->setQuery(
                "SELECT COUNT(*) as registros_pendientes
                FROM registro_produccion 
                WHERE idlote = ? AND estatus = 'BORRADOR'"
            );
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $pendientes = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pendientes['registros_pendientes'] > 0) {
                return [
                    'status' => false,
                    'message' => "No se puede cerrar el lote. Hay {$pendientes['registros_pendientes']} registros pendientes de cálculo de nómina."
                ];
            }

            // Cerrar el lote
            $this->setQuery(
                "UPDATE lotes_produccion 
                SET estatus_lote = 'FINALIZADO', fecha_fin_real = NOW() 
                WHERE idlote = ?"
            );
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

            // Actualizar estatus de asignaciones
            $this->setQuery(
                "UPDATE asignaciones_operarios 
                SET estatus_asignacion = 'COMPLETADO' 
                WHERE idlote = ?"
            );
            $this->setArray([$idlote]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

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

    private function generarAlertaErrorAlto($tasaError, $fechaInicio, $fechaFin, $db)
    {
        try {
            $this->setQuery(
                "INSERT INTO alertas_produccion (
                    tipo_alerta, nivel_prioridad, mensaje, datos_adicionales, 
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, NOW())"
            );

            $mensaje = "Tasa de error alta detectada: {$tasaError}% en el periodo {$fechaInicio} a {$fechaFin}";
            $datosAdicionales = json_encode([
                'tasa_error' => $tasaError,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'accion_recomendada' => 'Revisar procesos de capacitación'
            ]);

            $this->setArray([
                'ERROR_ALTO',
                'ALTA',
                $mensaje,
                $datosAdicionales
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());

        } catch (Exception $e) {
            error_log("Error al generar alerta: " . $e->getMessage());
        }
    }

    // ========================================
    // MÉTODOS DE CONSULTA Y REPORTES
    // ========================================

    public function selectProduccionDiaria(string $fecha)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT * FROM vista_produccion_diaria 
                WHERE fecha_jornada = ? 
                ORDER BY numero_lote"
            );
            
            $this->setArray([$fecha]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Producción diaria obtenida.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener producción diaria: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener producción diaria: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function selectProductividadOperarios(string $fechaInicio, string $fechaFin)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    e.idempleado,
                    CONCAT(e.nombre, ' ', e.apellido) as operario,
                    COUNT(rp.idregistro) as dias_trabajados,
                    AVG(rp.kg_clasificados) as promedio_kg_clasificados,
                    AVG(rp.pacas_armadas) as promedio_pacas_armadas,
                    AVG(rp.tasa_error) as promedio_tasa_error,
                    AVG(rp.salario_total) as promedio_salario_diario,
                    SUM(rp.salario_total) as total_salarios,
                    SUM(rp.kg_clasificados) as total_kg_clasificados,
                    SUM(rp.pacas_armadas) as total_pacas_armadas
                FROM empleado e
                INNER JOIN registro_produccion rp ON e.idempleado = rp.idempleado
                WHERE e.estatus = 'Activo' 
                AND rp.fecha_jornada BETWEEN ? AND ?
                GROUP BY e.idempleado, e.nombre, e.apellido
                ORDER BY total_kg_clasificados DESC"
            );
            
            $this->setArray([$fechaInicio, $fechaFin]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Productividad de operarios obtenida.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener productividad: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener productividad: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function selectAlertasProduccion(string $estatus = 'PENDIENTE')
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    a.*,
                    DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(a.fecha_revision, '%d/%m/%Y %H:%i') as fecha_revision_formato,
                    CONCAT(e.nombre, ' ', e.apellido) as empleado_afectado,
                    l.numero_lote
                FROM alertas_produccion a
                LEFT JOIN empleado e ON a.idempleado = e.idempleado
                LEFT JOIN lotes_produccion l ON a.idlote = l.idlote
                WHERE a.estatus = ?
                ORDER BY a.nivel_prioridad DESC, a.fecha_creacion DESC"
            );
            
            $this->setArray([$estatus]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Alertas obtenidas.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener alertas: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener alertas: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function selectPacasProducidas(string $fechaInicio, string $fechaFin)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.*,
                    CONCAT(e.nombre, ' ', e.apellido) as operario_empacador,
                    l.numero_lote,
                    l.fecha_jornada,
                    po.nombre as producto_origen,
                    pp.nombre as producto_paca,
                    DATE_FORMAT(p.fecha_empaque, '%d/%m/%Y %H:%i') as fecha_empaque_formato
                FROM pacas p
                INNER JOIN empleado e ON p.idusuario_creador = e.idempleado
                INNER JOIN lotes_produccion l ON p.idlote = l.idlote
                INNER JOIN producto po ON p.idproducto_origen = po.idproducto
                INNER JOIN producto pp ON p.idproducto_paca = pp.idproducto
                WHERE DATE(p.fecha_empaque) BETWEEN ? AND ?
                ORDER BY p.fecha_empaque DESC"
            );
            
            $this->setArray([$fechaInicio, $fechaFin]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                "status" => true,
                "message" => "Pacas producidas obtenidas.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener pacas: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al obtener pacas: " . $e->getMessage(),
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
            $this->setQuery(
                "SELECT * FROM configuracion_produccion 
                WHERE estatus = 'activo' 
                ORDER BY fecha_creacion DESC LIMIT 1"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            if ($this->getResult()) {
                return [
                    "status" => true,
                    "message" => "Configuración obtenida.",
                    "data" => $this->getResult()
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
                "message" => "Error al obtener configuración: " . $e->getMessage(),
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
            $this->setQuery(
                "UPDATE configuracion_produccion SET
                    productividad_clasificacion = ?,
                    capacidad_maxima_planta = ?,
                    salario_base = ?,
                    beta_clasificacion = ?,
                    gamma_empaque = ?,
                    umbral_error_maximo = ?,
                    penalizacion_beta = ?,
                    peso_minimo_paca = ?,
                    peso_maximo_paca = ?,
                    ultima_modificacion = NOW()
                WHERE estatus = 'activo'"
            );
            
            $this->setArray([
                $data['productividad_clasificacion'],
                $data['capacidad_maxima_planta'],
                $data['salario_base'],
                $data['beta_clasificacion'],
                $data['gamma_empaque'],
                $data['umbral_error_maximo'],
                $data['penalizacion_beta'],
                $data['peso_minimo_paca'],
                $data['peso_maximo_paca']
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
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
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function resolverAlerta(int $idalerta, int $idusuario, string $observaciones)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "UPDATE alertas_produccion SET
                    estatus = 'RESUELTA',
                    idusuario_revision = ?,
                    fecha_revision = NOW(),
                    observaciones_revision = ?
                WHERE idalerta = ?"
            );
            
            $this->setArray([$idusuario, $observaciones, $idalerta]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
            if ($stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'message' => 'Alerta resuelta exitosamente.'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo resolver la alerta.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error al resolver alerta: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al resolver alerta: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    public function generarReporteProduccion(string $fechaInicio, string $fechaFin, string $tipo)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $reporte = [];

            switch ($tipo) {
                case 'general':
                    // Resumen general del periodo
                    $this->setQuery(
                        "SELECT 
                            COUNT(DISTINCT l.idlote) as total_lotes,
                            COUNT(DISTINCT rp.idempleado) as operarios_participantes,
                            SUM(rp.kg_clasificados) as total_kg_clasificados,
                            SUM(rp.kg_contaminantes) as total_kg_contaminantes,
                            SUM(rp.pacas_armadas) as total_pacas_armadas,
                            AVG(rp.tasa_error) as promedio_tasa_error,
                            SUM(rp.salario_total) as total_nomina,
                            SUM(l.volumen_estimado) as volumen_total_estimado
                        FROM lotes_produccion l
                        LEFT JOIN registro_produccion rp ON l.idlote = rp.idlote
                        WHERE l.fecha_jornada BETWEEN ? AND ?"
                    );
                    
                    $this->setArray([$fechaInicio, $fechaFin]);
                    $stmt = $db->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                    $reporte['resumen'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Top operarios
                    $this->setQuery(
                        "SELECT 
                            CONCAT(e.nombre, ' ', e.apellido) as operario,
                            SUM(rp.kg_clasificados) as total_kg,
                            SUM(rp.pacas_armadas) as total_pacas,
                            AVG(rp.tasa_error) as promedio_error,
                            SUM(rp.salario_total) as total_salario
                        FROM empleado e
                        INNER JOIN registro_produccion rp ON e.idempleado = rp.idempleado
                        WHERE rp.fecha_jornada BETWEEN ? AND ?
                        GROUP BY e.idempleado, e.nombre, e.apellido
                        ORDER BY total_kg DESC
                        LIMIT 10"
                    );
                    
                    $this->setArray([$fechaInicio, $fechaFin]);
                    $stmt = $db->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                    $reporte['top_operarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'nomina':
                    // Reporte detallado de nómina
                    $this->setQuery(
                        "SELECT 
                            CONCAT(e.nombre, ' ', e.apellido) as operario,
                            rp.fecha_jornada,
                            rp.kg_clasificados,
                            rp.pacas_armadas,
                            rp.salario_base_dia,
                            rp.bono_clasificacion,
                            rp.bono_empaque,
                            rp.penalizacion,
                            rp.salario_total,
                            rp.tasa_error
                        FROM registro_produccion rp
                        INNER JOIN empleado e ON rp.idempleado = e.idempleado
                        WHERE rp.fecha_jornada BETWEEN ? AND ?
                        ORDER BY rp.fecha_jornada DESC, e.nombre"
                    );
                    
                    $this->setArray([$fechaInicio, $fechaFin]);
                    $stmt = $db->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                    $reporte['detalle_nomina'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'pacas':
                    // Reporte de pacas producidas
                    $this->setQuery(
                        "SELECT 
                            p.codigo_paca,
                            p.peso_paca,
                            p.calidad,
                            CONCAT(e.nombre, ' ', e.apellido) as operario,
                            l.numero_lote,
                            DATE_FORMAT(p.fecha_empaque, '%d/%m/%Y %H:%i') as fecha_empaque
                        FROM pacas p
                        INNER JOIN empleado e ON p.idusuario_creador = e.idempleado
                        FROM pacas p
                        INNER JOIN empleado e ON p.idusuario_creador = e.idempleado
                        INNER JOIN lotes_produccion l ON p.idlote = l.idlote
                        WHERE DATE(p.fecha_empaque) BETWEEN ? AND ?
                        ORDER BY p.fecha_empaque DESC"
                    );
                    
                    $this->setArray([$fechaInicio, $fechaFin]);
                    $stmt = $db->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                    $reporte['pacas_producidas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }

            return [
                "status" => true,
                "message" => "Reporte generado exitosamente.",
                "data" => $reporte
            ];
            
        } catch (Exception $e) {
            error_log("Error al generar reporte: " . $e->getMessage());
            return [
                "status" => false,
                "message" => "Error al generar reporte: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
?>