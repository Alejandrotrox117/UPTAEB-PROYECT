<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class MovimientosModel extends Mysql
{
    private $db;
    private $conexion;
    private $fecha;
    private $id;
    private $inicial;
    private $final;
    private $material_compras;
    private $ajuste;
    private $descuento;

    // Getters y Setters
    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }
    public function getFecha()
    {
        return $this->fecha;
    }
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    public function getInicial()
    {
        return $this->inicial;
    }
    public function setInicial($inicial)
    {
        $this->inicial = $inicial;
    }
    public function getFinal()
    {
        return $this->final;
    }
    public function setFinal($final)
    {
        $this->final = $final;
    }
    public function getMaterial_compras()
    {
        return $this->material_compras;
    }
    public function setMaterial_compras($material_compras)
    {
        $this->material_compras = $material_compras;
    }
    public function getAjuste()
    {
        return $this->ajuste;
    }
    public function setAjuste($ajuste)
    {
        $this->ajuste = $ajuste;
    }
    public function getDescuento()
    {
        return $this->descuento;
    }
    public function setDescuento($descuento)
    {
        $this->descuento = $descuento;
    }

    // Constructor
    public function __construct()
    {

        parent::__construct();
    }

    // Obtener todos los movimientos
    public function selectAllMovimientos()
    {
        $sql = "SELECT 
                m.*, 
                t.nombre AS tipo_movimiento, 
                p.nombre AS nombre_producto
            FROM movimientos_existencia m
            INNER JOIN tipo_movimiento t ON m.idtipomovimiento = t.idtipomovimiento
            INNER JOIN producto p ON m.idproducto = p.idproducto
            ORDER BY m.idmovimiento DESC";
        return $this->searchAll($sql);
    }
   

    public function obtenerMovimientoPorId($idmovimiento)
    {
        $sql = "SELECT 
                m.*, 
                t.nombre AS tipo_movimiento, 
                p.nombre AS nombre_producto
            FROM movimientos_existencia m
            INNER JOIN tipo_movimiento t ON m.idtipomovimiento = t.idtipomovimiento
            INNER JOIN producto p ON m.idproducto = p.idproducto
            WHERE m.idmovimiento = ?";
        return $this->search($sql, [$idmovimiento]);
    }
    // Crear un nuevo movimiento
    public function crearMovimiento($data)
    {
        return $this->executeTransaction(function ($mysql) use ($data) {
            // Validar campos obligatorios
            $camposObligatorios = [
                'inicial',
                'ajuste',
                'material_compras',
                'despacho',
                'descuento',
                'final',
                'fecha'
            ];
            foreach ($camposObligatorios as $campo) {
                if (!isset($data[$campo])) {
                    throw new Exception("Falta el campo obligatorio: $campo");
                }
            }

            $sql = "INSERT INTO movimiento_existencia 
                (inicial, ajuste, material_compras, despacho, descuento, final, fecha)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $data['inicial'],
                $data['ajuste'],
                $data['material_compras'],
                $data['despacho'],
                $data['descuento'],
                $data['final'],
                $data['fecha']
            ];
            $id = $this->insert($sql, $params);
            if (!$id) throw new Exception("No se pudo crear el movimiento.");
            return ['success' => true, 'message' => 'Movimiento creado exitosamente', 'id_movimiento' => $id];
        });
    }

    // Actualizar un movimiento
    public function actualizarMovimiento($id_movimiento, $data)
    {
        return $this->executeTransaction(function ($mysql) use ($id_movimiento, $data) {
            $sql = "UPDATE movimiento_existencia SET 
                        inicial = ?, 
                        ajuste = ?, 
                        material_compras = ?, 
                        despacho = ?, 
                        descuento = ?, 
                        final = ?, 
                        fecha = ?
                    WHERE id_movimiento = ?";
            $params = [
                $data['inicial'],
                $data['ajuste'],
                $data['material_compras'],
                $data['despacho'],
                $data['descuento'],
                $data['final'],
                $data['fecha'],
                $id_movimiento
            ];
            $result = $this->update($sql, $params);
            if ($result > 0) {
                return ['success' => true, 'message' => 'Movimiento actualizado exitosamente'];
            } else {
                throw new Exception("No se pudo actualizar el movimiento.");
            }
        });
    }

    // Eliminar (desactivar) un movimiento
    public function eliminarMovimiento($id_movimiento)
    {
        return $this->executeTransaction(function ($mysql) use ($id_movimiento) {
            // Verificar que el movimiento existe
            $mov = $this->search("SELECT COUNT(*) as count FROM movimiento_existencia WHERE id_movimiento = ?", [$id_movimiento]);
            if ($mov['count'] == 0) {
                throw new Exception("El movimiento especificado no existe.");
            }
            // Cambiar estatus a 'Inactivo' (si tienes un campo estatus)
            $sqlUpdate = "UPDATE movimiento_existencia SET estatus = 'Inactivo' WHERE id_movimiento = ?";
            $result = $this->update($sqlUpdate, [$id_movimiento]);
            if ($result > 0) {
                return ['success' => true, 'message' => 'Movimiento desactivado exitosamente'];
            } else {
                throw new Exception("No se pudo desactivar el movimiento.");
            }
        });
    }

    // Buscar movimientos por fecha o criterio (ejemplo)
    public function buscarMovimientos($criterio)
    {
        try {
            $sql = "SELECT * FROM movimiento_existencia
                    WHERE inicial LIKE ? OR material_compras LIKE ? OR fecha LIKE ?
                    ORDER BY fecha DESC
                    LIMIT 20";
            $parametroBusqueda = '%' . $criterio . '%';
            return $this->searchAll($sql, [$parametroBusqueda, $parametroBusqueda, $parametroBusqueda]);
        } catch (Exception $e) {
            error_log("Error al buscar movimientos: " . $e->getMessage());
            throw new Exception("Error al buscar movimientos: " . $e->getMessage());
        }
    }
}
