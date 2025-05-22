<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class ProduccionModel extends Mysql
{
    // Atributos privados mapeados a la tabla
    private $idproduccion;
    private $idempleado;
    private $idproducto;
    private $cantidad_a_realizar;
    private $fecha_inicio;
    private $fecha_fin;
    private $estado;
    private $fecha_creacion;
    private $fecha_modificacion;

    // Conexión a la base de datos
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdProduccion() { return $this->idproduccion; }
    public function setIdProduccion($id) { $this->idproduccion = $id; }

    public function getIdEmpleado() { return $this->idempleado; }
    public function setIdEmpleado($id) { $this->idempleado = $id; }

    public function getIdProducto() { return $this->idproducto; }
    public function setIdProducto($id) { $this->idproducto = $id; }

    public function getCantidadARealizar() { return $this->cantidad_a_realizar; }
    public function setCantidadARealizar($cant) { $this->cantidad_a_realizar = $cant; }

    public function getFechaInicio() { return $this->fecha_inicio; }
    public function setFechaInicio($fecha) { $this->fecha_inicio = $fecha; }

    public function getFechaFin() { return $this->fecha_fin; }
    public function setFechaFin($fecha) { $this->fecha_fin = $fecha; }

    public function getEstado() { return $this->estado; }
    public function setEstado($estado) { $this->estado = $estado; }

    public function getFechaCreacion() { return $this->fecha_creacion; }
    public function setFechaCreacion($fecha) { $this->fecha_creacion = $fecha; }

    public function getFechaModificacion() { return $this->fecha_modificacion; }
    public function setFechaModificacion($fecha) { $this->fecha_modificacion = $fecha; }

    // Método para insertar una nueva producción
    public function insertProduccion(array $data)
{
    try {
        $this->db->beginTransaction();

        // Insertar producción principal
        $sql = "INSERT INTO produccion (
                    idempleado, 
                    idproducto, 
                    cantidad_a_realizar, 
                    fecha_inicio, 
                    fecha_fin, 
                    estado,
                    numero_produccion,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['idempleado'] ?? null,
            $data['idproducto'] ?? null,
            $data['cantidad_a_realizar'] ?? null,
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $data['estado'] ?? 'borrador',
            $this->generarNumeroProduccion(), // Método opcional
            date("Y-m-d H:i:s") // fecha_creacion
        ]);

        $idproduccion = $this->db->lastInsertId();

        // Si hay insumos, insertamos el detalle
        if (!empty($data['insumos'])) {
            foreach ($data['insumos'] as $insumo) {
                $this->insertDetalleProduccion($idproduccion, $insumo);
            }
        }

        $this->db->commit();
        return $idproduccion;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("ProduccionModel: Error al insertar producción - " . $e->getMessage());
        return false;
    }
}

// Método auxiliar para insertar cada fila del detalle
private function insertDetalleProduccion($idproduccion, $insumo)
{
    try {
        $sql = "INSERT INTO detalle_produccion (
                    idproduccion, 
                    idproducto, 
                    cantidad, 
                    unidad_medida, 
                    cantidad_consumida, 
                    observaciones,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $idproduccion,
            $insumo['idproducto'],
            $insumo['cantidad'],
            $insumo['unidad_medida'] ?? 'kg', // Por defecto kg
            $insumo['cantidad_utilizada'] ?? 0,
            $insumo['observaciones'] ?? '',
            date("Y-m-d H:i:s")
        ]);
        return true;

    } catch (Exception $e) {
        error_log("ProduccionModel: Error al insertar detalle - " . $e->getMessage());
        return false;
    }
}

// Generador de número de producción (opcional)
private function generarNumeroProduccion()
{
    return "PROD-" . date("YmdHis");
}

    // Método para actualizar una producción
    public function updateProduccion(array $data)
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE produccion SET
                        idempleado = ?,
                        idproducto = ?,
                        cantidad_a_realizar = ?,
                        fecha_inicio = ?,
                        fecha_fin = ?,
                        estado = ?,
                        fecha_modificacion = ?
                    WHERE idproduccion = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['idempleado'],
                $data['idproducto'],
                $data['cantidad_a_realizar'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['estado'],
                $data['fecha_modificacion'],
                $data['idproduccion']
            ]);

            // Opcional: borrar y re-insertar insumos si cambian
            if (!empty($data['insumos'])) {
                $this->deleteDetalleProduccion($data['idproduccion']);

                foreach ($data['insumos'] as $insumo) {
                    $this->insertDetalleProduccion($data['idproduccion'], $insumo);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ProduccionModel: Error al actualizar producción - " . $e->getMessage());
            return false;
        }
    }

    // Método para eliminar detalle de producción
    public function deleteDetalleProduccion($idproduccion)
    {
        try {
            $sql = "DELETE FROM detalle_produccion WHERE idproduccion = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idproduccion]);

        } catch (Exception $e) {
            error_log("ProduccionModel: Error al eliminar detalle - " . $e->getMessage());
            return false;
        }
    }

    // Método para obtener todas las producciones
    public function SelectAllProducciones()
    {
        $sql = "SELECT 
                    p.idproduccion, 
                    pr.nombre AS nombre_producto, 
                    e.nombre AS nombre_empleado, 
                    p.cantidad_a_realizar, 
                    p.fecha_inicio, 
                    p.fecha_fin, 
                    p.estado 
                FROM produccion p
                INNER JOIN producto pr ON p.idproducto = pr.idproducto
                INNER JOIN empleado e ON p.idempleado = e.idempleado
                WHERE p.estado != 'inactivo'";

        return $this->searchAll($sql);
    }

    // Método para obtener producción por ID
    public function getProduccionById($idproduccion)
    {
        $sql = "SELECT * FROM produccion WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método para obtener detalle de producción
    public function SelectDetalleProduccion($idproduccion)
    {
        $sql = "SELECT 
                    dp.iddetalle_produccion,
                    p.nombre AS nombre_producto,
                    p.unidad_medida,
                    dp.cantidad,
                    dp.cantidad_consumida,
                    dp.observaciones
                FROM detalle_produccion dp
                INNER JOIN producto p ON dp.idproducto = p.idproducto
                WHERE dp.idproduccion = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para eliminar lógicamente una producción
    public function deleteProduccion($idproduccion)
    {
        $sql = "UPDATE produccion SET estado = 'inactivo' WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idproduccion]);
    }

    // Método para obtener empleados activos
    public function SelectAllEmpleado()
    {
        $sql = "SELECT * FROM empleado WHERE estatus = 'activo'";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EmpleadoModel: Error al seleccionar empleados - " . $e->getMessage());
            return [];
        }
    }

    // Método para obtener productos activos
    public function SelectAllProducto()
    {
        $sql = "SELECT * FROM producto WHERE estatus = 'activo'";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductoModel: Error al seleccionar productos - " . $e->getMessage());
            return [];
        }
    }

    // Método para ajustar inventario cuando se finaliza producción
    public function ajustarInventario($idproduccion)
    {
        try {
            $this->db->beginTransaction();

            // Obtener producto terminado
            $sql = "SELECT idproducto, cantidad_a_realizar FROM produccion WHERE idproduccion = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproduccion]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prod) throw new Exception("Producción no encontrada.");

            // Aumentar inventario del producto terminado
            $sql = "UPDATE inventario SET cantidad = cantidad + ? WHERE idproducto = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prod['cantidad_a_realizar'], $prod['idproducto']]);

            // Obtener insumos usados
            $sql = "SELECT idproducto, cantidad_consumida FROM detalle_produccion WHERE idproduccion = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproduccion]);
            $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Restar insumos del inventario
            foreach ($insumos as $insumo) {
                $sql = "UPDATE inventario SET cantidad = cantidad - ? WHERE idproducto = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$insumo['cantidad_consumida'], $insumo['idproducto']]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ProduccionModel: Error al ajustar inventario - " . $e->getMessage());
            return false;
        }
    }
}