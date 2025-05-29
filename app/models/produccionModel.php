<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class ProduccionModel extends Mysql
{
    private $db;
    private $conexion;

    // Atributos encapsulados
    private $idproduccion;
    private $idempleado;
    private $idproducto;
    private $cantidad_a_realizar;
    private $fecha_inicio;
    private $fecha_fin;
    private $estado;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
        // Obtener el ID del usuario desde la sesión
        $idUsuario = $this->obtenerIdUsuarioSesion();

        if ($idUsuario) {
            // Establecer la variable de sesión SQL
            $this->setUsuarioActual($idUsuario);
        }
    }
    private function obtenerIdUsuarioSesion(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    // Método para establecer @usuario_actual en MySQL
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }

    // === Getters y Setters ===

    public function getIdProduccion()
    {
        return $this->idproduccion;
    }
    public function setIdProduccion($id)
    {
        $this->idproduccion = $id;
    }

    public function getIdEmpleado()
    {
        return $this->idempleado;
    }
    public function setIdEmpleado($id)
    {
        $this->idempleado = $id;
    }

    public function getIdProducto()
    {
        return $this->idproducto;
    }
    public function setIdProducto($id)
    {
        $this->idproducto = $id;
    }

    public function getCantidadARealizar()
    {
        return $this->cantidad_a_realizar;
    }
    public function setCantidadARealizar($cant)
    {
        $this->cantidad_a_realizar = $cant;
    }

    public function getFechaInicio()
    {
        return $this->fecha_inicio;
    }
    public function setFechaInicio($fecha)
    {
        $this->fecha_inicio = $fecha;
    }

    public function getFechaFin()
    {
        return $this->fecha_fin;
    }
    public function setFechaFin($fecha)
    {
        $this->fecha_fin = $fecha;
    }

    public function getEstado()
    {
        return $this->estado;
    }
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }
    public function setFechaCreacion($fecha)
    {
        $this->fecha_creacion = $fecha;
    }

    public function getFechaModificacion()
    {
        return $this->fecha_modificacion;
    }
    public function setFechaModificacion($fecha)
    {
        $this->fecha_modificacion = $fecha;
    }

    // === Operaciones CRUD ===

    /**
     * Registra una nueva producción y sus insumos
     * @param array $data Datos de la producción
     * @return int|false ID de producción o false en caso de error
     */
    public function insertProduccion(array $data)
    {
        try {
            // Insertar producción principal
            $sql = "INSERT INTO produccion (
                        idempleado,
                        idproducto,
                        cantidad_a_realizar,
                        fecha_inicio,
                        fecha_fin,
                        estado,
                        fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['idempleado'],
                $data['idproducto'],
                $data['cantidad_a_realizar'],
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                $data['estado'],
                date("Y-m-d H:i:s") // Fecha creación
            ]);

            $idproduccion = $this->db->lastInsertId();

            // Insertar detalles (insumos)
            if (!empty($data['insumos'])) {
                foreach ($data['insumos'] as $insumo) {
                    $this->insertDetalleProduccion($idproduccion, $insumo);
                }
            }

            return $idproduccion;
        } catch (Exception $e) {
            error_log("ProduccionModel::insertProduccion - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta un detalle de producción (insumo usado)
     * @param int $idproduccion ID de producción
     * @param array $insumo Datos del insumo
     * @return bool
     */
    private function insertDetalleProduccion(int $idproduccion, array $insumo): bool
    {
        try {
            $sql = "INSERT INTO detalle_produccion (
                        idproduccion,
                        idproducto,
                        cantidad,
                        cantidad_consumida,
                        observaciones,
                        fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $idproduccion,
                $insumo['idproducto'],
                $insumo['cantidad'],
                $insumo['cantidad_utilizada'] ?? 0,
                $insumo['observaciones'] ?? '',
                date("Y-m-d H:i:s")
            ]);
        } catch (Exception $e) {
            error_log("ProduccionModel::insertDetalleProduccion - Error: " . $e->getMessage());
            return false;
        }
    }


    public function updateProduccion(array $data): bool
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
                $data['fecha_fin'] ?? null,
                $data['estado'],
                $data['fecha_modificacion'],
                $data['idproduccion']
            ]);

            // Eliminar y re-insertar los insumos si se proporcionan
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
            error_log("ProduccionModel::updateProduccion - Error: " . $e->getMessage());
            return false;
        }
    }

    
    public function deleteDetalleProduccion(int $idproduccion): bool
    {
        try {
            $sql = "DELETE FROM detalle_produccion WHERE idproduccion = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idproduccion]);
        } catch (Exception $e) {
            error_log("ProduccionModel::deleteDetalleProduccion - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las producciones activas
     * @return array
     */
    public function SelectAllProducciones(): array
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

    /**
     * Obtiene una producción por su ID
     * @param int $idproduccion
     * @return array|false
     */
    public function getProduccionById(int $idproduccion): mixed
    {
        $sql = "SELECT 
                p.*, 
                prod.nombre AS nombre_producto,
                e.nombre AS nombre_empleado 
            FROM 
                produccion p
            JOIN 
                producto prod ON p.idproducto = prod.idproducto
            JOIN
                empleado e ON p.idempleado = e.idempleado
            WHERE 
                p.idproduccion = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los insumos usados en una producción
     * @param int $idproduccion
     * @return array
     */
    public function SelectDetalleProduccion(int $idproduccion): array
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

    /**
     * Elimina lógicamente una producción
     * @param int $idproduccion
     * @return bool
     */
    public function deleteProduccion(int $idproduccion): bool
    {
        $sql = "UPDATE produccion SET estado = 'inactivo' WHERE idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idproduccion]);
    }

    /**
     * Obtiene todos los empleados activos
     * @return array
     */
    public function SelectAllEmpleado(): array
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

    /**
     * Obtiene todos los productos activos
     * @return array
     */
    public function SelectAllProducto(): array
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

    /**
     * Ajusta inventario tras finalizar producción
     * @param int $idproduccion
     * @return bool
     */
    public function ajustarInventario(int $idproduccion): bool
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
    public function getTotalProducciones()
    {
        $sql = "SELECT COUNT(*) AS total FROM produccion WHERE estado != ''";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    public function getProduccionesEnClasificacion()
    {
        $sql = "SELECT COUNT(*) AS total FROM produccion WHERE estado = 'en_clasificacion'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    public function getProduccionesFinalizadas()
    {
        $sql = "SELECT COUNT(*) AS total FROM produccion WHERE estado = 'realizado'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }
}
