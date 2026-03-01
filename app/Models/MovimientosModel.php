<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use App\Models\ProductosModel;
use PDO;
use PDOException;
use Exception;

class MovimientosModel
{
    private $objModelMovimientosModel;

    private function getInstanciaModel()
    {
        if ($this->objModelMovimientosModel == null) {
            $this->objModelMovimientosModel = new MovimientosModel();
        }
        return $this->objModelMovimientosModel;
    }

    private $query;
    private $array;
    private $data;
    private $result;
    private $movimientoId;
    private $message;
    private $status;

    private $idmovimiento;
    private $numero_movimiento;
    private $idproducto;
    private $idtipomovimiento;
    private $idcompra;
    private $idventa;
    private $idproduccion;
    private $cantidad_entrada;
    private $cantidad_salida;
    private $stock_anterior;
    private $stock_resultante;
    private $total;
    private $entrada;
    private $salida;
    private $observaciones;
    private $estatus;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {

    }


    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getArray()
    {
        return $this->array ?? [];
    }

    public function setArray(array $array)
    {
        $this->array = $array;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getMovimientoId()
    {
        return $this->movimientoId;
    }

    public function setMovimientoId(?int $movimientoId)
    {
        $this->movimientoId = $movimientoId;
    }

    public function getMessage()
    {
        return $this->message ?? '';
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status ?? false;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

    //  GETTERS Y SETTERS ESPECÍFICOS (mantener igual)
    public function getIdmovimiento()
    {
        return $this->idmovimiento;
    }
    public function setIdmovimiento($idmovimiento)
    {
        $this->idmovimiento = filter_var($idmovimiento, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getNumeroMovimiento()
    {
        return $this->numero_movimiento;
    }
    public function setNumeroMovimiento($numero_movimiento)
    {
        $this->numero_movimiento = trim($numero_movimiento);
        return $this;
    }

    public function getIdproducto()
    {
        return $this->idproducto;
    }
    public function setIdproducto($idproducto)
    {
        $this->idproducto = filter_var($idproducto, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getIdtipomovimiento()
    {
        return $this->idtipomovimiento;
    }
    public function setIdtipomovimiento($idtipomovimiento)
    {
        $this->idtipomovimiento = filter_var($idtipomovimiento, FILTER_VALIDATE_INT);
        return $this;
    }

    public function getIdcompra()
    {
        return $this->idcompra;
    }
    public function setIdcompra($idcompra)
    {
        $this->idcompra = $idcompra ? filter_var($idcompra, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getIdventa()
    {
        return $this->idventa;
    }
    public function setIdventa($idventa)
    {
        $this->idventa = $idventa ? filter_var($idventa, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getIdproduccion()
    {
        return $this->idproduccion;
    }
    public function setIdproduccion($idproduccion)
    {
        $this->idproduccion = $idproduccion ? filter_var($idproduccion, FILTER_VALIDATE_INT) : null;
        return $this;
    }

    public function getCantidadEntrada()
    {
        return $this->cantidad_entrada;
    }
    public function setCantidadEntrada($cantidad_entrada)
    {
        $this->cantidad_entrada = $cantidad_entrada ? filter_var($cantidad_entrada, FILTER_VALIDATE_FLOAT) : null;
        return $this;
    }

    public function getCantidadSalida()
    {
        return $this->cantidad_salida;
    }
    public function setCantidadSalida($cantidad_salida)
    {
        $this->cantidad_salida = $cantidad_salida ? filter_var($cantidad_salida, FILTER_VALIDATE_FLOAT) : null;
        return $this;
    }

    public function getStockAnterior()
    {
        return $this->stock_anterior;
    }
    public function setStockAnterior($stock_anterior)
    {
        $this->stock_anterior = filter_var($stock_anterior, FILTER_VALIDATE_FLOAT);
        return $this;
    }

    public function getStockResultante()
    {
        return $this->stock_resultante;
    }
    public function setStockResultante($stock_resultante)
    {
        $this->stock_resultante = filter_var($stock_resultante, FILTER_VALIDATE_FLOAT);
        return $this;
    }

    public function getObservaciones()
    {
        return $this->observaciones;
    }
    public function setObservaciones($observaciones)
    {
        $this->observaciones = trim($observaciones);
        return $this;
    }

    public function getEstatusMovimiento()
    {
        return $this->estatus;
    }
    public function setEstatusMovimiento($estatus)
    {
        $estatusValidos = ['activo', 'inactivo', 'eliminado'];
        $this->estatus = in_array($estatus, $estatusValidos) ? $estatus : 'activo';
        return $this;
    }

    //  FUNCIONES PRIVADAS CORREGIDAS

    /**
     * Función privada para obtener todos los movimientos
     */
    private function ejecutarBusquedaTodosMovimientos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    COALESCE(m.fecha_creacion, NOW()) as fecha_creacion,
                    COALESCE(m.fecha_modificacion, NOW()) as fecha_modificacion,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    COALESCE(tm.descripcion, '') AS tipo_descripcion,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato,
                    DATE_FORMAT(COALESCE(m.fecha_modificacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_modificacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.estatus = 'activo'
                ORDER BY COALESCE(m.fecha_creacion, m.idmovimiento) DESC"
            );

            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Siempre retornar status true si la consulta fue exitosa
            $resultado = [
                'status' => true,
                'message' => $movimientos ? 'Movimientos obtenidos correctamente.' : 'No hay movimientos disponibles.',
                'data' => $movimientos ? $movimientos : []
            ];

        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaTodosMovimientos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar movimiento por ID
     */
    private function ejecutarBusquedaMovimientoPorId(int $idmovimiento)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    COALESCE(m.fecha_creacion, NOW()) as fecha_creacion,
                    COALESCE(m.fecha_modificacion, NOW()) as fecha_modificacion,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    COALESCE(tm.descripcion, '') AS tipo_descripcion,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato,
                    DATE_FORMAT(COALESCE(m.fecha_modificacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_modificacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.idmovimiento = ? AND m.estatus != 'eliminado'"
            );

            $this->setArray([$idmovimiento]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

            $resultado = $movimiento ? [
                'status' => true,
                'message' => 'Movimiento obtenido correctamente.',
                'data' => $movimiento
            ] : [
                'status' => false,
                'message' => 'Movimiento no encontrado.',
                'data' => null
            ];

        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaMovimientoPorId - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para insertar movimiento
     */
    private function ejecutarInsercionMovimiento(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            //  GENERAR NÚMERO DE MOVIMIENTO
            $numeroMovimiento = $this->generarNumeroMovimiento($db);

            $this->setQuery(
                "INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, idcompra, idventa, idproduccion,
                 cantidad_entrada, cantidad_salida, stock_anterior, stock_resultante, total, observaciones, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')"
            );

            // Usar setters con validación
            $this->setIdproducto($data['idproducto']);
            $this->setIdtipomovimiento($data['idtipomovimiento']);
            $this->setIdcompra($data['idcompra'] ?? null);
            $this->setIdventa($data['idventa'] ?? null);
            $this->setIdproduccion($data['idproduccion'] ?? null);
            $this->setCantidadEntrada($data['cantidad_entrada'] ?? 0);
            $this->setCantidadSalida($data['cantidad_salida'] ?? 0);
            $this->setStockAnterior($data['stock_anterior'] ?? 0);
            $this->setStockResultante($data['stock_resultante'] ?? 0);
            $this->setObservaciones($data['observaciones'] ?? '');

            $this->setArray([
                $numeroMovimiento,
                $this->getIdproducto(),
                $this->getIdtipomovimiento(),
                $this->getIdcompra(),
                $this->getIdventa(),
                $this->getIdproduccion(),
                $this->getCantidadEntrada(),
                $this->getCantidadSalida(),
                $this->getStockAnterior(),
                $this->getStockResultante(),
                $this->getStockResultante(),
                $this->getObservaciones()
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setMovimientoId($db->lastInsertId());

            if ($this->getMovimientoId()) {
                // Actualizar la existencia del producto
                $updateProductoStmt = $db->prepare(
                    "UPDATE producto 
                    SET existencia = ?, 
                        ultima_modificacion = NOW() 
                    WHERE idproducto = ?"
                );
                $updateProductoStmt->execute([
                    $this->getStockResultante(),
                    $this->getIdproducto()
                ]);

                error_log("🔔 Movimiento registrado, verificando stock para producto ID: " . $this->getIdproducto());

                // Verificar stock y notificar si es necesario
                $productosModel = new ProductosModel();
                $productosModel->verificarStockYNotificar($this->getIdproducto());

                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Movimiento registrado correctamente.');

                $resultado = [
                    'status' => $this->getStatus(),
                    'message' => $this->getMessage(),
                    'data' => [
                        'idmovimiento' => $this->getMovimientoId(),
                        'numero_movimiento' => $numeroMovimiento
                    ]
                ];
            } else {
                $db->rollBack();
                $resultado = [
                    'status' => false,
                    'message' => 'Error al obtener ID de movimiento tras registro.',
                    'data' => null
                ];
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarInsercionMovimiento - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al registrar movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para actualizar movimiento
     */
    private function ejecutarActualizacionMovimiento(int $idmovimiento, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();


            $this->setQuery(
                "UPDATE movimientos_existencia 
                SET idproducto = ?, idtipomovimiento = ?, idcompra = ?, idventa = ?, idproduccion = ?,
                    cantidad_entrada = ?, cantidad_salida = ?, stock_anterior = ?, stock_resultante = ?, 
                    total = ?, observaciones = ?, estatus = ?, fecha_modificacion = NOW()
                WHERE idmovimiento = ?"
            );

            // Usar setters con validación
            $this->setIdproducto($data['idproducto']);
            $this->setIdtipomovimiento($data['idtipomovimiento']);
            $this->setIdcompra($data['idcompra'] ?? null);
            $this->setIdventa($data['idventa'] ?? null);
            $this->setIdproduccion($data['idproduccion'] ?? null);
            $this->setCantidadEntrada($data['cantidad_entrada'] ?? 0);
            $this->setCantidadSalida($data['cantidad_salida'] ?? 0);
            $this->setStockAnterior($data['stock_anterior'] ?? 0);
            $this->setStockResultante($data['stock_resultante'] ?? 0);
            $this->setObservaciones($data['observaciones'] ?? '');
            $this->setEstatusMovimiento($data['estatus'] ?? 'activo');

            $this->setArray([
                $this->getIdproducto(),
                $this->getIdtipomovimiento(),
                $this->getIdcompra(),
                $this->getIdventa(),
                $this->getIdproduccion(),
                $this->getCantidadEntrada(),
                $this->getCantidadSalida(),
                $this->getStockAnterior(),
                $this->getStockResultante(),
                $this->getStockResultante(), // Campo 'total' = stock_resultante
                $this->getObservaciones(),
                $this->getEstatusMovimiento(),
                $idmovimiento
            ]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();

            if ($rowCount > 0) {
                // Actualizar la existencia del producto con el nuevo stock_resultante
                $updateProductoStmt = $db->prepare(
                    "UPDATE producto 
                    SET existencia = ?, 
                        ultima_modificacion = NOW() 
                    WHERE idproducto = ?"
                );
                $updateProductoStmt->execute([
                    $this->getStockResultante(),
                    $this->getIdproducto()
                ]);

                // Verificar stock y notificar si es necesario
                $productosModel = new ProductosModel();
                $productosModel->verificarStockYNotificar($this->getIdproducto());

                $db->commit();
                $resultado = [
                    'status' => true,
                    'message' => 'Movimiento actualizado correctamente.',
                    'data' => ['idmovimiento' => $idmovimiento]
                ];
            } else {
                $db->commit();
                $resultado = [
                    'status' => true,
                    'message' => 'No se realizaron cambios en el movimiento (datos idénticos).',
                    'data' => ['idmovimiento' => $idmovimiento]
                ];
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarActualizacionMovimiento - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al actualizar movimiento: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para eliminar (desactivar) movimiento - OBSOLETO
     * Usar anularMovimiento() en su lugar
     */
    private function ejecutarEliminacionMovimiento(int $idmovimiento)
    {
        return false;
    }

    /**
     * Función privada para anular un movimiento
     */
    private function ejecutarAnulacionMovimiento(int $idmovimiento)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $movimientoOriginal = $this->selectMovimientoById($idmovimiento);
            if (!$movimientoOriginal['status']) {
                throw new Exception('Movimiento no encontrado.');
            }

            $original = $movimientoOriginal['data'];

            if ($original['idcompra'] || $original['idventa'] || $original['idproduccion']) {
                throw new Exception('No se puede anular un movimiento vinculado a compra/venta/producción.');
            }

            if ($original['estatus'] === 'inactivo') {
                throw new Exception('El movimiento ya está anulado.');
            }

            $stockActual = $this->obtenerStockActualProducto($original['idproducto']);
            if ($stockActual === false) {
                throw new Exception('No se pudo obtener el stock actual del producto.');
            }

            $numeroAnulacion = $this->generarNumeroMovimiento($db);

            $cantidadAnulacion = 0;
            $tipoAnulacion = '';

            if (floatval($original['cantidad_entrada']) > 0) {
                $cantidadAnulacion = floatval($original['cantidad_entrada']);
                $tipoAnulacion = 'salida';
            } else {
                $cantidadAnulacion = floatval($original['cantidad_salida']);
                $tipoAnulacion = 'entrada';
            }

            $stockDespuesAnulacion = $tipoAnulacion === 'entrada'
                ? $stockActual + $cantidadAnulacion
                : $stockActual - $cantidadAnulacion;

            if ($stockDespuesAnulacion < 0) {
                throw new Exception('No se puede anular: resultaría en stock negativo.');
            }

            $stmtAnulacion = $db->prepare(
                "INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, cantidad_entrada, cantidad_salida, 
                 stock_anterior, stock_resultante, total, observaciones, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')"
            );

            $stmtAnulacion->execute([
                $numeroAnulacion,
                $original['idproducto'],
                $original['idtipomovimiento'],
                $tipoAnulacion === 'entrada' ? $cantidadAnulacion : 0,
                $tipoAnulacion === 'salida' ? $cantidadAnulacion : 0,
                $stockActual,
                $stockDespuesAnulacion,
                $stockDespuesAnulacion,
                '[ANULACIÓN AUTOMÁTICA] Anulación de ' . $original['numero_movimiento'] . '. ' . ($original['observaciones'] ?? '')
            ]);

            $idAnulacion = $db->lastInsertId();

            $updateProducto = $db->prepare("UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $updateProducto->execute([$stockDespuesAnulacion, $original['idproducto']]);

            // Verificar stock y notificar si es necesario
            $productosModel = new ProductosModel();
            $productosModel->verificarStockYNotificar($original['idproducto']);

            $stmtMarcarAnulado = $db->prepare("UPDATE movimientos_existencia SET estatus = 'inactivo', fecha_modificacion = NOW() WHERE idmovimiento = ?");
            $stmtMarcarAnulado->execute([$idmovimiento]);

            $db->commit();

            return [
                'status' => true,
                'message' => 'Movimiento anulado correctamente.',
                'data' => [
                    'idmovimiento_anulado' => $idmovimiento,
                    'idmovimiento_anulacion' => $idAnulacion,
                    'numero_anulacion' => $numeroAnulacion
                ]
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::ejecutarAnulacionMovimiento - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al anular: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Función privada para obtener productos activos
     */
    private function ejecutarBusquedaProductosActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {

            $this->setQuery("SELECT idproducto, nombre, COALESCE(existencia, 0) as stock_actual FROM producto WHERE estatus = 'activo' ORDER BY nombre ASC");
            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultado = [
                'status' => true,
                'message' => 'Productos activos obtenidos.',
                'data' => $productos
            ];

        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaProductosActivos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener tipos de movimiento activos
     */
    private function ejecutarBusquedaTiposMovimientoActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT idtipomovimiento, nombre, COALESCE(descripcion, '') as descripcion FROM tipo_movimiento WHERE estatus = 'activo' ORDER BY nombre ASC");
            $this->setArray([]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultado = [
                'status' => true,
                'message' => 'Tipos de movimiento activos obtenidos.',
                'data' => $tipos
            ];

        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaTiposMovimientoActivos - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener tipos de movimiento: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar movimientos por criterio
     */
    private function ejecutarBusquedaMovimientosPorCriterio(string $criterio)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    m.idmovimiento,
                    COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) as numero_movimiento,
                    m.idproducto,
                    m.idtipomovimiento,
                    m.idcompra,
                    m.idventa,
                    m.idproduccion,
                    COALESCE(m.cantidad_entrada, 0) as cantidad_entrada,
                    COALESCE(m.cantidad_salida, 0) as cantidad_salida,
                    COALESCE(m.stock_anterior, 0) as stock_anterior,
                    COALESCE(m.stock_resultante, m.total, 0) as stock_resultante,
                    m.observaciones,
                    m.estatus,
                    p.nombre AS producto_nombre,
                    tm.nombre AS tipo_movimiento,
                    DATE_FORMAT(COALESCE(m.fecha_creacion, NOW()), '%d/%m/%Y %H:%i') AS fecha_creacion_formato
                FROM movimientos_existencia m
                INNER JOIN producto p ON m.idproducto = p.idproducto
                INNER JOIN tipo_movimiento tm ON m.idtipomovimiento = tm.idtipomovimiento
                WHERE m.estatus = 'activo' 
                AND (COALESCE(m.numero_movimiento, CONCAT('MOV-', m.idmovimiento)) LIKE ? 
                     OR p.nombre LIKE ? 
                     OR tm.nombre LIKE ? 
                     OR COALESCE(m.observaciones, '') LIKE ?)
                ORDER BY COALESCE(m.fecha_creacion, m.idmovimiento) DESC"
            );

            $criterioLike = '%' . $criterio . '%';
            $this->setArray([$criterioLike, $criterioLike, $criterioLike, $criterioLike]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($movimientos) {
                $resultado = [
                    'status' => true,
                    'message' => 'Búsqueda completada.',
                    'data' => $movimientos
                ];
            } else {
                $resultado = [
                    'status' => false,
                    'message' => 'No se encontraron movimientos con el criterio especificado.',
                    'data' => []
                ];
            }

        } catch (Exception $e) {
            error_log("MovimientosModel::ejecutarBusquedaMovimientosPorCriterio - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para validar datos de movimiento
     */
    /**
     * Validar datos del movimiento
     * @param array $data Datos del movimiento
     * @param float|null $stockEsperado Stock esperado (opcional, usado en anulación+corrección)
     */
    private function validarDatosMovimiento(array $data, $stockEsperado = null)
    {

        if (empty($data['idproducto'])) {
            return ['valido' => false, 'mensaje' => 'El producto es obligatorio.'];
        }

        if (empty($data['idtipomovimiento'])) {
            return ['valido' => false, 'mensaje' => 'El tipo de movimiento es obligatorio.'];
        }


        $cantidadEntrada = floatval($data['cantidad_entrada'] ?? 0);
        $cantidadSalida = floatval($data['cantidad_salida'] ?? 0);

        if ($cantidadEntrada <= 0 && $cantidadSalida <= 0) {
            return ['valido' => false, 'mensaje' => 'Debe especificar al menos una cantidad (entrada o salida).'];
        }


        if ($cantidadEntrada > 0 && $cantidadSalida > 0) {
            return ['valido' => false, 'mensaje' => 'No puede tener cantidad de entrada y salida al mismo tiempo.'];
        }

        // Validar que stock_anterior coincida con la existencia real del producto
        $stockAnteriorRecibido = floatval($data['stock_anterior'] ?? 0);

        // Si se proporciona un stock esperado (caso de anulación+corrección), usarlo
        // De lo contrario, consultar el stock real del producto
        $stockRealProducto = $stockEsperado !== null ? $stockEsperado : $this->obtenerStockActualProducto($data['idproducto']);

        if ($stockRealProducto === false) {
            return ['valido' => false, 'mensaje' => 'No se pudo verificar el stock del producto.'];
        }

        if (abs($stockAnteriorRecibido - $stockRealProducto) > 0.001) {
            return ['valido' => false, 'mensaje' => 'El stock anterior no coincide con la existencia actual del producto. Stock actual: ' . $stockRealProducto];
        }

        // Validar que stock_resultante sea correcto según el cálculo
        $stockResultanteRecibido = floatval($data['stock_resultante'] ?? 0);
        $stockResultanteCalculado = $stockRealProducto + $cantidadEntrada - $cantidadSalida;

        if (abs($stockResultanteRecibido - $stockResultanteCalculado) > 0.001) {
            return ['valido' => false, 'mensaje' => 'El stock resultante no es correcto. Debería ser: ' . $stockResultanteCalculado];
        }

        // Validar que no haya stock negativo
        if ($stockResultanteCalculado < 0) {
            return ['valido' => false, 'mensaje' => 'No hay suficiente stock. Stock disponible: ' . $stockRealProducto];
        }

        return ['valido' => true, 'mensaje' => 'Datos válidos.'];
    }

    /**
     * Función privada para obtener stock actual de un producto
     */
    private function obtenerStockActualProducto($idproducto)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $stmt = $db->prepare("SELECT COALESCE(existencia, 0) as stock FROM producto WHERE idproducto = ? AND estatus = 'activo'");
            $stmt->execute([$idproducto]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado ? floatval($resultado['stock']) : false;
        } catch (Exception $e) {
            error_log("MovimientosModel::obtenerStockActualProducto - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Función privada para generar número único de movimiento
     */
    private function generarNumeroMovimiento($db)
    {
        $prefijo = 'MOV-';
        $fecha = date('Ymd');

        try {
            $query = "SELECT COALESCE(numero_movimiento, CONCAT('MOV-', idmovimiento)) as numero_movimiento 
                      FROM movimientos_existencia 
                      WHERE COALESCE(numero_movimiento, CONCAT('MOV-', idmovimiento)) LIKE ? 
                      ORDER BY idmovimiento DESC LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->execute([$prefijo . $fecha . '-%']);
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ultimo) {
                $partes = explode('-', $ultimo['numero_movimiento']);
                $consecutivo = intval(end($partes)) + 1;
            } else {
                $consecutivo = 1;
            }

            return $prefijo . $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("MovimientosModel::generarCodigoMovimiento - Error: " . $e->getMessage());
            return $prefijo . $fecha . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    //  MÉTODOS PÚBLICOS (mantener igual)

    /**
     * Insertar nuevo movimiento
     */
    public function insertMovimiento(array $data)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarInsertMovimientoPublico($data);
    }

    private function ejecutarInsertMovimientoPublico(array $data)
    {
        error_log("🔵 insertMovimiento llamado con datos: " . json_encode($data));

        $this->setData($data);


        $validacion = $this->validarDatosMovimiento($this->getData());
        if (!$validacion['valido']) {
            error_log("❌ Validación falló: " . $validacion['mensaje']);
            return [
                'status' => false,
                'message' => $validacion['mensaje'],
                'data' => null
            ];
        }

        error_log("✅ Validación OK, ejecutando inserción...");

        return $this->ejecutarInsercionMovimiento($this->getData());
    }

    /**
     * Actualizar movimiento existente - ELIMINADO
     * Usar anularMovimiento() en su lugar
     */
    public function updateMovimiento(int $idmovimiento, array $data)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarUpdateMovimientoPublico($idmovimiento, $data);
    }

    private function ejecutarUpdateMovimientoPublico(int $idmovimiento, array $data)
    {
        return [
            'status' => false,
            'message' => 'La edición directa no está permitida. Use la función de anular y crear nuevo movimiento.',
            'data' => null
        ];
    }

    /**
     * Anular movimiento y crear uno correctivo
     */
    public function anularYCorregirMovimiento(int $idmovimiento, array $datosNuevoMovimiento)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarAnularYCorregirMovimiento($idmovimiento, $datosNuevoMovimiento);
    }

    private function ejecutarAnularYCorregirMovimiento(int $idmovimiento, array $datosNuevoMovimiento)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $db->beginTransaction();

            $movimientoOriginal = $this->selectMovimientoById($idmovimiento);
            if (!$movimientoOriginal['status']) {
                throw new Exception('Movimiento original no encontrado.');
            }

            $original = $movimientoOriginal['data'];

            if ($original['idcompra'] || $original['idventa'] || $original['idproduccion']) {
                throw new Exception('No se puede anular un movimiento vinculado a compra/venta/producción.');
            }

            if ($original['estatus'] === 'inactivo') {
                throw new Exception('El movimiento ya está anulado.');
            }

            $stockActual = $this->obtenerStockActualProducto($original['idproducto']);
            if ($stockActual === false) {
                throw new Exception('No se pudo obtener el stock actual del producto.');
            }

            $numeroAnulacion = $this->generarNumeroMovimiento($db);

            $cantidadAnulacion = 0;
            $tipoAnulacion = '';

            if (floatval($original['cantidad_entrada']) > 0) {
                $cantidadAnulacion = floatval($original['cantidad_entrada']);
                $tipoAnulacion = 'salida';
            } else {
                $cantidadAnulacion = floatval($original['cantidad_salida']);
                $tipoAnulacion = 'entrada';
            }

            $stockDespuesAnulacion = $tipoAnulacion === 'entrada'
                ? $stockActual + $cantidadAnulacion
                : $stockActual - $cantidadAnulacion;

            if ($stockDespuesAnulacion < 0) {
                throw new Exception('No se puede anular: resultaría en stock negativo.');
            }

            $stmtAnulacion = $db->prepare(
                "INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, cantidad_entrada, cantidad_salida, 
                 stock_anterior, stock_resultante, total, observaciones, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')"
            );

            $stmtAnulacion->execute([
                $numeroAnulacion,
                $original['idproducto'],
                $original['idtipomovimiento'],
                $tipoAnulacion === 'entrada' ? $cantidadAnulacion : 0,
                $tipoAnulacion === 'salida' ? $cantidadAnulacion : 0,
                $stockActual,
                $stockDespuesAnulacion,
                $stockDespuesAnulacion,
                '[ANULACIÓN AUTOMÁTICA] Anulación de ' . $original['numero_movimiento'] . '. ' . ($original['observaciones'] ?? '')
            ]);

            $updateProducto1 = $db->prepare("UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $updateProducto1->execute([$stockDespuesAnulacion, $original['idproducto']]);

            // Verificar stock y notificar si es necesario
            $productosModel = new ProductosModel();
            $productosModel->verificarStockYNotificar($original['idproducto']);

            $stmtMarcarAnulado = $db->prepare("UPDATE movimientos_existencia SET estatus = 'inactivo', fecha_modificacion = NOW() WHERE idmovimiento = ?");
            $stmtMarcarAnulado->execute([$idmovimiento]);

            // Ajustar los datos del nuevo movimiento con el stock correcto después de la anulación
            $datosNuevoMovimiento['stock_anterior'] = $stockDespuesAnulacion;

            // Recalcular stock resultante basado en el stock después de anulación
            $cantidadEntradaNueva = floatval($datosNuevoMovimiento['cantidad_entrada'] ?? 0);
            $cantidadSalidaNueva = floatval($datosNuevoMovimiento['cantidad_salida'] ?? 0);
            $datosNuevoMovimiento['stock_resultante'] = $stockDespuesAnulacion + $cantidadEntradaNueva - $cantidadSalidaNueva;

            // Validar datos del nuevo movimiento pasando el stock esperado después de anulación
            $validacionNuevo = $this->validarDatosMovimiento($datosNuevoMovimiento, $stockDespuesAnulacion);
            if (!$validacionNuevo['valido']) {
                throw new Exception($validacionNuevo['mensaje']);
            }

            $this->setIdproducto($datosNuevoMovimiento['idproducto']);
            $this->setIdtipomovimiento($datosNuevoMovimiento['idtipomovimiento']);
            $this->setIdcompra($datosNuevoMovimiento['idcompra'] ?? null);
            $this->setIdventa($datosNuevoMovimiento['idventa'] ?? null);
            $this->setIdproduccion($datosNuevoMovimiento['idproduccion'] ?? null);
            $this->setCantidadEntrada($datosNuevoMovimiento['cantidad_entrada'] ?? 0);
            $this->setCantidadSalida($datosNuevoMovimiento['cantidad_salida'] ?? 0);
            $this->setStockAnterior($datosNuevoMovimiento['stock_anterior'] ?? 0);
            $this->setStockResultante($datosNuevoMovimiento['stock_resultante'] ?? 0);
            $this->setObservaciones($datosNuevoMovimiento['observaciones'] ?? '');

            $numeroNuevo = $this->generarNumeroMovimiento($db);

            $stmtNuevo = $db->prepare(
                "INSERT INTO movimientos_existencia 
                (numero_movimiento, idproducto, idtipomovimiento, idcompra, idventa, idproduccion,
                 cantidad_entrada, cantidad_salida, stock_anterior, stock_resultante, total, observaciones, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')"
            );

            $stmtNuevo->execute([
                $numeroNuevo,
                $this->getIdproducto(),
                $this->getIdtipomovimiento(),
                $this->getIdcompra(),
                $this->getIdventa(),
                $this->getIdproduccion(),
                $this->getCantidadEntrada(),
                $this->getCantidadSalida(),
                $this->getStockAnterior(),
                $this->getStockResultante(),
                $this->getStockResultante(),
                $this->getObservaciones()
            ]);

            $idNuevoMovimiento = $db->lastInsertId();

            $updateProducto2 = $db->prepare("UPDATE producto SET existencia = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $updateProducto2->execute([$this->getStockResultante(), $this->getIdproducto()]);

            // Verificar stock y notificar si es necesario
            $productosModel = new ProductosModel();
            $productosModel->verificarStockYNotificar($this->getIdproducto());

            $db->commit();

            return [
                'status' => true,
                'message' => 'Movimiento anulado y corregido exitosamente.',
                'data' => [
                    'idmovimiento_anulado' => $idmovimiento,
                    'idmovimiento_anulacion' => $db->lastInsertId(),
                    'idmovimiento_nuevo' => $idNuevoMovimiento,
                    'numero_anulacion' => $numeroAnulacion,
                    'numero_nuevo' => $numeroNuevo
                ]
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("MovimientosModel::anularYCorregirMovimiento - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Obtener movimiento por ID
     */
    public function selectMovimientoById(int $idmovimiento)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarSelectMovimientoByIdPublico($idmovimiento);
    }

    private function ejecutarSelectMovimientoByIdPublico(int $idmovimiento)
    {
        $this->setMovimientoId($idmovimiento);

        if (!$this->getMovimientoId()) {
            return [
                'status' => false,
                'message' => 'ID de movimiento inválido.',
                'data' => null
            ];
        }

        return $this->ejecutarBusquedaMovimientoPorId($this->getMovimientoId());
    }

    /**
     * Anular movimiento por ID
     */
    public function anularMovimientoById(int $idmovimiento)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarAnularMovimientoByIdPublico($idmovimiento);
    }

    private function ejecutarAnularMovimientoByIdPublico(int $idmovimiento)
    {
        $this->setMovimientoId($idmovimiento);

        if (!$this->getMovimientoId()) {
            return [
                'status' => false,
                'message' => 'ID de movimiento inválido.',
                'data' => null
            ];
        }

        return $this->ejecutarAnulacionMovimiento($this->getMovimientoId());
    }

    /**
     * Eliminar movimiento por ID - OBSOLETO
     * Usar anularMovimientoById() en su lugar
     */
    public function deleteMovimientoById(int $idmovimiento)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarDeleteMovimientoByIdPublico($idmovimiento);
    }

    private function ejecutarDeleteMovimientoByIdPublico(int $idmovimiento)
    {
        return [
            'status' => false,
            'message' => 'La eliminación directa no está permitida. Use la función de anular movimiento.',
            'data' => null
        ];
    }

    /**
     * Obtener todos los movimientos
     */
    public function selectAllMovimientos()
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarSelectAllMovimientosPublico();
    }

    private function ejecutarSelectAllMovimientosPublico()
    {
        return $this->ejecutarBusquedaTodosMovimientos();
    }

    /**
     * Buscar movimientos por criterio
     */
    public function buscarMovimientos(string $criterio)
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarBuscarMovimientosPublico($criterio);
    }

    private function ejecutarBuscarMovimientosPublico(string $criterio)
    {
        if (empty(trim($criterio))) {
            return $this->selectAllMovimientos();
        }

        return $this->ejecutarBusquedaMovimientosPorCriterio($criterio);
    }

    /**
     * Obtener productos activos
     */
    public function getProductosActivos()
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarGetProductosActivosPublico();
    }

    private function ejecutarGetProductosActivosPublico()
    {
        return $this->ejecutarBusquedaProductosActivos();
    }

    /**
     * Obtener tipos de movimiento activos
     */
    public function getTiposMovimientoActivos()
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarGetTiposMovimientoActivosPublico();
    }

    private function ejecutarGetTiposMovimientoActivosPublico()
    {
        return $this->ejecutarBusquedaTiposMovimientoActivos();
    }

    /**
     * Obtener tipos de movimiento para filtros (incluyendo estadísticas)
     */
    public function getTiposMovimientoConEstadisticas()
    {
        $objModelMovimientosModel = $this->getInstanciaModel();
        return $objModelMovimientosModel->ejecutarGetTiposMovimientoConEstadisticasPublico();
    }

    private function ejecutarGetTiposMovimientoConEstadisticasPublico()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {

            $this->setQuery(
                "SELECT 
                    tm.idtipomovimiento,
                    tm.nombre,
                    COALESCE(tm.descripcion, '') as descripcion,
                    tm.estatus,
                    COUNT(m.idmovimiento) as total_movimientos,
                    SUM(CASE WHEN  m.cantidad_entrada > 0 THEN 1 ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN m.cantidad_salida > 0 THEN 1 ELSE 0 END) as total_salidas
                FROM tipo_movimiento tm
                LEFT JOIN movimientos_existencia m ON tm.idtipomovimiento = m.idtipomovimiento 
                    AND m.estatus != 'eliminado'
                WHERE tm.estatus = 'activo'
                GROUP BY tm.idtipomovimiento, tm.nombre, tm.descripcion, tm.estatus
                ORDER BY tm.nombre ASC"
            );

            $this->setArray([]);
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultado = [
                'status' => true,
                'message' => 'Tipos de movimiento con estadísticas obtenidos.',
                'data' => $tipos
            ];

        } catch (Exception $e) {
            error_log("MovimientosModel::getTiposMovimientoConEstadisticas - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener tipos de movimiento: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }
}
?>