<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class ProduccionModel extends Mysql
{
    private $db;
    private $conexion;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
        
        // Obtener el ID del usuario desde la sesión
        $idUsuario = $this->obtenerIdUsuarioSesion();
        if ($idUsuario) {
            $this->setUsuarioActual($idUsuario);
        }
    }

    /**
     * Obtiene el ID del usuario desde la sesión.
     * @return int|null El ID del usuario o null si no está disponible.
     */
    private function obtenerIdUsuarioSesion(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    /**
     * Establece la variable de sesión MySQL @usuario_actual.
     * @param int $idUsuario El ID del usuario actual.
     */
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("ProduccionModel::setUsuarioActual - Error: " . $e->getMessage());
        }
    }

    // ===== GESTIÓN DE LOTES DE PRODUCCIÓN =====

    /**
     * Crea un nuevo lote de producción en la base de datos.
     * @param array $data Datos del lote a crear.
     * @return array Resultado de la operación (status, message, id).
     */
    public function createLote(array $data): array
    {
        try {
            $this->db->beginTransaction(); // Inicia una transacción

            $sql = "INSERT INTO produccion (
                        numero_produccion,
                        supervisor_id,
                        meta_total,
                        fecha_inicio,
                        fecha_estimada_fin,
                        fecha_final,
                        estado_produccion,
                        observaciones,
                        estatus
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')"; // Estatus por defecto es ACTIVO

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['numero_lote'],
                $data['supervisor'],
                $data['meta_total'],
                $data['fecha_inicio'],
                $data['fecha_estimada_fin'] ?? null, // Permite valores NULL
                $data['fecha_final'] ?? null,      // Permite valores NULL
                $data['estado'] ?? 'BORRADOR',    // Estado por defecto es BORRADOR
                $data['observaciones'] ?? ''      // Observaciones pueden ser vacías
            ]);

            if ($result) {
                $idLote = $this->db->lastInsertId(); // Obtiene el ID del lote recién insertado
                $this->db->commit(); // Confirma la transacción
                return ['status' => true, 'message' => 'Lote creado exitosamente', 'id' => $idLote];
            } else {
                throw new Exception("Error al insertar los datos del lote.");
            }

        } catch (Exception $e) {
            $this->db->rollBack(); // Deshace la transacción si ocurre un error
            error_log("ProduccionModel::createLote - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al crear el lote: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza un lote de producción existente.
     * @param array $data Datos del lote a actualizar.
     * @return array Resultado de la operación (status, message).
     */
    public function updateLote(array $data): array
    {
        try {
            $sql = "UPDATE produccion SET
                        numero_produccion = ?,
                        supervisor_id = ?,
                        meta_total = ?,
                        fecha_inicio = ?,
                        fecha_estimada_fin = ?,
                        fecha_final = ?,
                        estado_produccion = ?,
                        observaciones = ?
                    WHERE idproduccion = ?";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['numero_lote'],
                $data['supervisor'],
                $data['meta_total'],
                $data['fecha_inicio'],
                $data['fecha_estimada_fin'] ?? null,
                $data['fecha_final'] ?? null,
                $data['estado'],
                $data['observaciones'] ?? '',
                $data['idproduccion'] // Clave primaria para la actualización
            ]);

            if ($result) {
                return ['status' => true, 'message' => 'Lote actualizado exitosamente'];
            } else {
                // Si PDO::execute() retorna false, puede indicar un error o que no se encontró el registro
                // Es buena práctica verificar si se afectaron filas.
                // $rowCount = $stmt->rowCount();
                // if ($rowCount === 0) throw new Exception("Lote no encontrado o no hubo cambios.");
                throw new Exception("Error al actualizar el lote.");
            }

        } catch (Exception $e) {
            error_log("ProduccionModel::updateLote - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al actualizar el lote: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina (marca como inactivo) un lote de producción.
     * @param int $idproduccion El ID del lote a eliminar.
     * @return array Resultado de la operación (status, message).
     */
    public function deleteLote(int $idproduccion): array
    {
        try {
            $sql = "UPDATE produccion SET estatus = 'INACTIVO' WHERE idproduccion = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$idproduccion]);

            if ($result) {
                return ['status' => true, 'message' => 'Lote eliminado exitosamente'];
            } else {
                throw new Exception("Error al eliminar el lote.");
            }

        } catch (Exception $e) {
            error_log("ProduccionModel::deleteLote - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al eliminar el lote: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene la lista de lotes activos para mostrar en la tabla y selects.
     * Filtra por estatus ACTIVO y estados de producción que no sean FINALIZADA.
     * @return array Lista de lotes activos.
     */
    public function getLotesActivos(): array
    {
        try {
            // Selecciona campos clave y une con la tabla de empleados para obtener el nombre del supervisor.
            // Usa la vista 'vista_produccion_completa' si está diseñada para esto.
            $sql = "SELECT 
                        p.idproduccion,
                        p.numero_produccion,
                        p.meta_total,
                        p.producido_total,
                        p.porcentaje_avance,
                        p.estado_produccion,
                        p.fecha_inicio,
                        p.fecha_estimada_fin,
                        p.fecha_final,
                        p.observaciones,
                        CONCAT(e.nombre, ' ', e.apellido) as supervisor_nombre
                    FROM produccion p
                    LEFT JOIN empleado e ON p.supervisor_id = e.idempleado
                    WHERE p.estatus = 'ACTIVO' 
                    AND p.estado_produccion != 'FINALIZADA' -- Considera lotes en progreso
                    ORDER BY p.fecha_inicio DESC"; // Ordena por fecha de inicio

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("ProduccionModel::getLotesActivos - Error: " . $e->getMessage());
            return []; // Devuelve array vacío en caso de error
        }
    }

    /**
     * Obtiene la información detallada de un lote específico por su ID.
     * @param int $idproduccion El ID del lote.
     * @return array Resultado con status y data si se encuentra, o status y message si no.
     */
    public function getLoteById(int $idproduccion): array
    {
        try {
            $sql = "SELECT * 
                    FROM produccion 
                    WHERE idproduccion = ? AND estatus = 'ACTIVO'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproduccion]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lote) {
                // Si se quiere mostrar el nombre del supervisor en lugar de ID:
                if ($lote['supervisor_id']) {
                    $sqlSupervisor = "SELECT nombre, apellido FROM empleado WHERE idempleado = ?";
                    $stmtSupervisor = $this->db->prepare($sqlSupervisor);
                    $stmtSupervisor->execute([$lote['supervisor_id']]);
                    $supervisor = $stmtSupervisor->fetch(PDO::FETCH_ASSOC);
                    $lote['supervisor_nombre'] = $supervisor ? $supervisor['nombre'] . ' ' . $supervisor['apellido'] : 'N/D';
                } else {
                    $lote['supervisor_nombre'] = 'N/D';
                }
                
                return ['status' => true, 'data' => $lote];
            } else {
                return ['status' => false, 'message' => 'Lote no encontrado o inactivo.'];
            }

        } catch (Exception $e) {
            error_log("ProduccionModel::getLoteById - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener el lote.'];
        }
    }

    /**
     * Obtiene el detalle completo de un lote, incluyendo la información general del lote y sus registros de trabajo.
     * @param int $idproduccion El ID del lote.
     * @return array Resultado con status, data (lote y detalles) o message.
     */
    public function getDetalleLote(int $idproduccion): array
    {
        try {
            // Obtener información general del lote y el nombre del supervisor
            $sqlLote = "SELECT 
                            p.*,
                            CONCAT(e.nombre, ' ', e.apellido) as supervisor_nombre
                        FROM produccion p
                        LEFT JOIN empleado e ON p.supervisor_id = e.idempleado
                        WHERE p.idproduccion = ?";
            
            $stmt = $this->db->prepare($sqlLote);
            $stmt->execute([$idproduccion]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lote) {
                return ['status' => false, 'message' => 'Lote no encontrado.'];
            }

            // Obtener los registros de trabajo asociados a este lote
            $sqlDetalle = "SELECT 
                                dp.iddetalle_produccion,
                                dp.cantidad_asignada,
                                dp.cantidad_producida,
                                dp.estado_detalle,
                                dp.fecha_inicio_proceso,
                                dp.fecha_fin_proceso,
                                dp.observaciones,
                                dp.unidad_medida,
                                CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                                pr.nombre_proceso,
                                prod.nombre_producto
                            FROM detalle_produccion dp
                            LEFT JOIN empleado e ON dp.idempleado = e.idempleado
                            LEFT JOIN procesos pr ON dp.idproceso = pr.idproceso
                            LEFT JOIN producto prod ON dp.idproducto = prod.idproducto
                            WHERE dp.idproduccion = ?";
            $stmt = $this->db->prepare($sqlDetalle);
            $stmt->execute([$idproduccion]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true, 
                'data' => [
                    'lote' => $lote,
                    'detalles' => $detalles
                ]
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::getDetalleLote - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener el detalle del lote.'];
        }
    }

    // ===== REGISTRO DIARIO DE TRABAJO =====

    /**
     * Registra un nuevo evento de trabajo diario de un empleado en un proceso específico.
     * @param array $data Datos del registro (lote, proceso, empleado, cantidades, etc.).
     * @return array Resultado de la operación (status, message).
     */
    public function registrarTrabajo(array $data): array
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO detalle_produccion (
                        idproduccion,
                        idproducto,
                        idproceso,
                        idempleado,
                        cantidad_asignada,
                        cantidad_producida,
                        estado_detalle,
                        fecha_inicio_proceso,
                        fecha_fin_proceso,
                        observaciones,
                        unidad_medida
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['lote'],
                $data['producto'],
                $data['proceso'],
                $data['empleado'],
                $data['cantidad_asignada'],
                $data['cantidad_producida'] ?? 0, // Por defecto 0 si no se proporciona
                $data['estado'] ?? 'PENDIENTE', // Estado por defecto PENDIENTE
                $data['fecha_inicio'] ?? date('Y-m-d H:i:s'), // Fecha actual si no se proporciona
                $data['fecha_fin'] ?? null,      // Permite NULL
                $data['observaciones'] ?? '',    // Permite string vacío
                $data['unidad'] // La unidad de medida obtenida del select
            ]);

            if ($result) {
                // Aquí se podría disparar un trigger para actualizar el avance del lote o inventario
                $this->db->commit();
                return ['status' => true, 'message' => 'Trabajo registrado exitosamente'];
            } else {
                throw new Exception("Error al registrar el trabajo.");
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ProduccionModel::registrarTrabajo - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al registrar el trabajo: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene los registros diarios de trabajo para una fecha específica.
     * @param string|null $fecha La fecha para filtrar los registros (formato YYYY-MM-DD). Si es null, usa la fecha actual.
     * @return array Lista de registros diarios.
     */
    public function getRegistrosDiarios(?string $fecha = null): array
    {
        try {
            // Si no se especifica fecha, usa la fecha actual
            $fecha = $fecha ?? date('Y-m-d');
            
            // Consulta que une las tablas necesarias para obtener nombres y datos relevantes
            $sql = "SELECT 
                        dp.iddetalle_produccion,
                        dp.cantidad_asignada,
                        dp.cantidad_producida,
                        dp.estado_detalle,
                        dp.fecha_inicio_proceso,
                        dp.fecha_fin_proceso,
                        dp.observaciones,
                        dp.unidad_medida,
                        CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                        pr.nombre_proceso,
                        prod.nombre_producto
                    FROM detalle_produccion dp
                    LEFT JOIN empleado e ON dp.idempleado = e.idempleado
                    LEFT JOIN procesos pr ON dp.idproceso = pr.idproceso
                    LEFT JOIN producto prod ON dp.idproducto = prod.idproducto
                    WHERE DATE(dp.fecha_inicio_proceso) = ? -- Filtra por fecha del inicio del proceso
                    ORDER BY dp.fecha_inicio_proceso DESC"; // Ordena cronológicamente

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("ProduccionModel::getRegistrosDiarios - Error: " . $e->getMessage());
            return []; // Devuelve array vacío si hay error
        }
    }

    // ===== CONTROL DE CALIDAD =====

    /**
     * Registra un nuevo control de calidad.
     * @param array $data Datos del control de calidad.
     * @return array Resultado de la operación.
     */
    public function registrarControlCalidad(array $data): array
    {
        try {
            $sql = "INSERT INTO control_calidad (
                        idproduccion,           -- FK al lote (opcional, podría ser null si es general)
                        idproceso,              -- FK al proceso
                        idempleado_inspector,   -- FK al empleado inspector
                        calificacion,           -- Calificación del 1 al 10
                        porcentaje_humedad,     -- Opcional
                        estado_calidad,         -- APROBADO, REPROCESO, RECHAZADO
                        observaciones,          -- Comentarios adicionales
                        fecha_inspeccion        -- Fecha y hora de la inspección
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"; // NOW() para la fecha/hora actual

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['lote'] ?? null, // Permitir null si no aplica a un lote específico
                $data['proceso_calidad'],
                $data['inspector'],
                $data['calificacion'],
                $data['humedad'] ?? null, // Permitir null
                $data['estado_calidad'] ?? 'APROBADO', // Estado por defecto APROBADO
                $data['observaciones_calidad'] ?? ''    // Permitir string vacío
            ]);

            if ($result) {
                // Aquí se podría implementar lógica para actualizar el estado del lote o registro
                return ['status' => true, 'message' => 'Control de calidad registrado exitosamente'];
            } else {
                throw new Exception("Error al registrar el control de calidad.");
            }

        } catch (Exception $e) {
            error_log("ProduccionModel::registrarControlCalidad - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al registrar el control de calidad: ' . $e->getMessage()];
        }
    }

    // ===== ESTADÍSTICAS =====

    /**
     * Obtiene las estadísticas diarias clave para el dashboard.
     * @param string|null $fecha La fecha para obtener las estadísticas.
     * @return array Datos estadísticos.
     */
    public function getEstadisticasDiarias(?string $fecha = null): array
    {
        try {
            $fecha = $fecha ?? date('Y-m-d'); // Usa la fecha actual si no se proporciona
            
            // 1. Empleados trabajando hoy (que tengan al menos un registro de trabajo)
            $sqlEmpleados = "SELECT COUNT(DISTINCT dp.idempleado) as total 
                           FROM detalle_produccion dp
                           WHERE DATE(dp.fecha_inicio_proceso) = ?";
            $stmt = $this->db->prepare($sqlEmpleados);
            $stmt->execute([$fecha]);
            $empleados = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // 2. Kg clasificados hoy (sumatoria de cantidad_producida en procesos de CLASIFICACIÓN)
            $sqlKg = "SELECT COALESCE(SUM(dp.cantidad_producida), 0) as total 
                     FROM detalle_produccion dp
                     JOIN procesos p ON dp.idproceso = p.idproceso
                     WHERE DATE(dp.fecha_inicio_proceso) = ? 
                     AND p.nombre_proceso LIKE '%CLASIFICACION%'"; // Busca procesos que contengan 'CLASIFICACION'
            $stmt = $this->db->prepare($sqlKg);
            $stmt->execute([$fecha]);
            $kgClasificados = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // 3. Pacas producidas hoy (sumatoria de cantidad_producida en procesos de EMPACADO)
            $sqlPacas = "SELECT COALESCE(SUM(dp.cantidad_producida), 0) as total 
                        FROM detalle_produccion dp
                        JOIN procesos p ON dp.idproceso = p.idproceso
                        WHERE DATE(dp.fecha_inicio_proceso) = ? 
                        AND p.nombre_proceso LIKE '%EMPACADO%'"; // Busca procesos que contengan 'EMPACADO'
            $stmt = $this->db->prepare($sqlPacas);
            $stmt->execute([$fecha]);
            $pacasProducidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // 4. Lotes activos (aquellos que no están finalizados ni inactivos)
            $sqlLotes = "SELECT COUNT(*) as total 
                        FROM produccion 
                        WHERE estatus = 'ACTIVO' 
                        AND estado_produccion IN ('BORRADOR', 'EN_PROCESO')"; // Considera borrador y en proceso como activos
            $stmt = $this->db->query($sqlLotes);
            $lotesActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Devolver todos los resultados en un array asociativo
            return [
                'empleados_trabajando' => $empleados,
                'kg_clasificados' => $kgClasificados,
                'pacas_producidas' => $pacasProducidas,
                'lotes_activos' => $lotesActivos
            ];

        } catch (Exception $e) {
            error_log("ProduccionModel::getEstadisticasDiarias - Error: " . $e->getMessage());
            // Devolver valores por defecto en caso de error
            return [
                'empleados_trabajando' => 0,
                'kg_clasificados' => 0,
                'pacas_producidas' => 0,
                'lotes_activos' => 0
            ];
        }
    }

    // ===== DATOS PARA SELECTS =====

    /**
     * Obtiene la lista de procesos activos con su unidad de medida.
     * @return array Lista de procesos.
     */
    public function getProcesos(): array
    {
        try {
            // Selecciona campos de procesos y ordena por secuencia y nombre.
            $sql = "SELECT idproceso, nombre_proceso, unidad_medida, secuencia_orden
                   FROM procesos 
                   WHERE estatus = 'ACTIVO' 
                   ORDER BY secuencia_orden, nombre_proceso";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ProduccionModel::getProcesos - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista de empleados activos.
     * @return array Lista de empleados con ID, nombre, apellido e identificación.
     */
    public function getEmpleados(): array
    {
        try {
            $sql = "SELECT idempleado, nombre, apellido, identificacion 
                   FROM empleado 
                   WHERE estatus = 'activo' 
                   ORDER BY nombre, apellido";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ProduccionModel::getEmpleados - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista de empleados que actúan como supervisores.
     * Se basa en criterios de cargo (se pueden ajustar) o devuelve todos los empleados si no hay supervisores definidos.
     * @return array Lista de supervisores.
     */
    public function getSupervisores(): array
    {
        try {
            // Intenta obtener empleados con cargos específicos de supervisor.
            $sql = "SELECT idempleado, nombre, apellido 
                   FROM empleado 
                   WHERE estatus = 'activo' 
                   AND (cargo LIKE '%supervisor%' OR cargo LIKE '%jefe%' OR cargo LIKE '%coordinador%') -- Ajustar según tus cargos
                   ORDER BY nombre, apellido";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si la consulta no devuelve resultados, se asume que todos los empleados activos pueden ser supervisores.
            if (empty($result)) {
                return $this->getEmpleados(); // Devuelve todos los empleados activos
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("ProduccionModel::getSupervisores - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista de productos activos con su categoría.
     * @return array Lista de productos.
     */
    public function getProductos(): array
    {
        try {
            // Une producto con categoría para mostrar el nombre de la categoría.
            $sql = "SELECT 
                        p.idproducto, 
                        p.nombre_producto, 
                        p.unidad_medida,
                        c.nombre_categoria
                   FROM producto p
                   LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                   WHERE p.estatus = 'activo' 
                   ORDER BY p.nombre_producto";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ProduccionModel::getProductos - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Determina la unidad de medida para un registro basado en el proceso y/o producto.
     * Prioriza la unidad definida en el proceso si existe.
     * @param int $idproceso ID del proceso.
     * @param int $idproducto ID del producto.
     * @return array Resultado (status, data['unidad_medida'] o status, message).
     */
    public function getUnidadMedida(int $idproceso, int $idproducto): array
    {
        try {
            // Buscar unidad en la tabla de procesos primero
            $sql = "SELECT unidad_medida FROM procesos WHERE idproceso = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproceso]);
            $proceso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($proceso && !empty($proceso['unidad_medida'])) {
                return ['status' => true, 'data' => ['unidad_medida' => $proceso['unidad_medida']]];
            }

            // Si no hay en el proceso, buscar en la tabla de productos
            $sql = "SELECT unidad_medida FROM producto WHERE idproducto = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($producto && !empty($producto['unidad_medida'])) {
                return ['status' => true, 'data' => ['unidad_medida' => $producto['unidad_medida']]];
            }

            // Si no se encuentra en ninguno de los dos
            return ['status' => false, 'message' => 'No se pudo determinar la unidad de medida.'];

        } catch (Exception $e) {
            error_log("ProduccionModel::getUnidadMedida - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener la unidad de medida.'];
        }
    }

    // ===== REPORTES =====

    /**
     * Genera datos para el reporte de producción por empleado, filtrando por rango de fechas.
     * @param string|null $fechaInicio Fecha de inicio del reporte.
     * @param string|null $fechaFin Fecha de fin del reporte.
     * @return array Datos del reporte.
     */
    public function getReporteEmpleado(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            // Establece fechas por defecto si no se proporcionan
            $fechaInicio = $fechaInicio ?? date('Y-m-01'); // Primer día del mes actual
            $fechaFin = $fechaFin ?? date('Y-m-d');      // Día actual

            // Consulta para agrupar producción por empleado y proceso
            $sql = "SELECT 
                        CONCAT(e.nombre, ' ', e.apellido) as empleado,
                        e.identificacion,
                        pr.nombre_proceso,
                        COUNT(dp.iddetalle_produccion) as total_tareas,
                        SUM(dp.cantidad_asignada) as total_asignado,
                        SUM(dp.cantidad_producida) as total_producido,
                        CASE
                            WHEN SUM(dp.cantidad_asignada) > 0 THEN 
                                COALESCE(SUM(dp.cantidad_producida) / SUM(dp.cantidad_asignada) * 100, 0)
                            ELSE 0
                        END as porcentaje_rendimiento_asignado,
                        CASE
                            WHEN SUM(dp.cantidad_producida) > 0 THEN 
                                COALESCE(SUM(dp.cantidad_producida) / (SELECT SUM(cantidad_producida) FROM detalle_produccion WHERE idproceso = dp.idproceso AND DATE(fecha_inicio_proceso) BETWEEN ? AND ?), 0) * 100
                            ELSE 0
                        END as porcentaje_sobre_total_proceso
                    FROM detalle_produccion dp
                    JOIN empleado e ON dp.idempleado = e.idempleado
                    JOIN procesos pr ON dp.idproceso = pr.idproceso
                    WHERE DATE(dp.fecha_inicio_proceso) BETWEEN ? AND ?
                    GROUP BY dp.idempleado, dp.idproceso, e.identificacion, pr.nombre_proceso
                    ORDER BY empleado, pr.nombre_proceso";

            $stmt = $this->db->prepare($sql);
            // Los parámetros deben coincidir con los used in the query (incluyendo los subqueries si los hubiera)
            $stmt->execute([$fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("ProduccionModel::getReporteEmpleado - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera datos para el reporte de lotes de producción en un rango de fechas.
     * @param string|null $fechaInicio Fecha de inicio.
     * @param string|null $fechaFin Fecha de fin.
     * @return array Datos del reporte de lotes.
     */
    public function getReporteLote(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            $fechaInicio = $fechaInicio ?? date('Y-m-01');
            $fechaFin = $fechaFin ?? date('Y-m-d');

            // Consulta a la vista completa de producción para obtener los datos de los lotes.
            $sql = "SELECT * 
                    FROM vista_produccion_completa 
                    WHERE estatus = 'ACTIVO' -- Considerar solo lotes activos
                    AND fecha_inicio BETWEEN ? AND ?
                    ORDER BY fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("ProduccionModel::getReporteLote - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera datos para el reporte de consumo de materiales, agrupados por producto.
     * @param string|null $fechaInicio Fecha de inicio.
     * @param string|null $fechaFin Fecha de fin.
     * @return array Datos del reporte de materiales.
     */
    public function getReporteMaterial(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            $fechaInicio = $fechaInicio ?? date('Y-m-01');
            $fechaFin = $fechaFin ?? date('Y-m-d');

            // Agrupa el consumo de materiales por producto.
            $sql = "SELECT 
                        p.idproducto,
                        p.nombre_producto,
                        c.nombre_categoria,
                        SUM(dp.cantidad_asignada) as total_asignado,
                        SUM(dp.cantidad_producida) as total_producido,
                        dp.unidad_medida,
                        COUNT(DISTINCT dp.idempleado) as empleados_involucrados
                    FROM detalle_produccion dp
                    JOIN producto p ON dp.idproducto = p.idproducto
                    LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                    WHERE DATE(dp.fecha_inicio_proceso) BETWEEN ? AND ?
                    GROUP BY dp.idproducto, p.nombre_producto, c.nombre_categoria, dp.unidad_medida
                    ORDER BY total_producido DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("ProduccionModel::getReporteMaterial - Error: " . $e->getMessage());
            return [];
        }
    }
}
?>