<?php
require_once "app/core/Conexion.php"; // Asegúrate que esta ruta sea correcta

class ComprasModel
{
    private $db; // Este será el objeto PDO
    private $conexionObjeto;

    public function __construct()
    {
        $this->conexionObjeto = new Conexion();
        $this->db = $this->conexionObjeto->connect();
        if (!$this->db) {
            error_log("ComprasModel: Fallo al conectar a la base de datos.");
        }
    }

    public function __destruct()
    {
        if ($this->conexionObjeto) {
            $this->conexionObjeto->disconnect();
        }
    }

    //GET COMPRAS DATATABLE
    public function getComprasServerSide($start, $length, $searchValue, $orderColumn, $orderDir)
{
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    $allowedOrderColumns = [
        'c.nro_compra',
        'c.fecha',
        'p.nombre',    
        'c.total_general',
        'c.fecha_creacion'    
    ];
    if (!in_array($orderColumn, $allowedOrderColumns)) {
        $orderColumn = 'c.fecha_creacion';
    }

    $sqlBase = "FROM compra c 
                JOIN proveedor p ON c.idproveedor = p.idproveedor
                JOIN monedas m ON c.idmoneda_general = m.idmoneda";
    $bindings = []; 
    $sqlWhere = "";
    $conditions = []; 
    if (!empty($searchValue)) {
        $searchPattern = '%' . $searchValue . '%';

        $conditions[] = "c.nro_compra LIKE :search_nro_compra";
        $bindings[':search_nro_compra'] = $searchPattern;

        $conditions[] = "p.nombre LIKE :search_proveedor_nombre";
        $bindings[':search_proveedor_nombre'] = $searchPattern;

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $searchValue)) {
            $conditions[] = "c.fecha = :search_fecha_exacta";
            $bindings[':search_fecha_exacta'] = $searchValue;
        }

        if (is_numeric($searchValue)) {
            $conditions[] = "c.total_general = :search_total_exacto";
            $bindings[':search_total_exacto'] = floatval($searchValue);
        }

        if (!empty($conditions)) {
            $sqlWhere = "WHERE (" . implode(" OR ", $conditions) . ")";
        }
    }

    $sql = "SELECT c.idcompra, c.nro_compra, c.fecha, p.nombre as proveedor_nombre,
                   c.total_general, m.codigo_moneda as moneda_simbolo 
            $sqlBase
            $sqlWhere
            ORDER BY $orderColumn $orderDir";

    if ($length != -1) { 
        $sql .= " LIMIT :start, :length";
    }

    try {
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $placeholder => $value) {
            if ($placeholder === ':search_total_exacto') {
                $stmt->bindValue($placeholder, $value);
            } else {
                $stmt->bindValue($placeholder, $value, PDO::PARAM_STR);
            }
        }
        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultData;

    } catch (PDOException $e) {
        error_log("ComprasModel::getComprasServerSide - Error de BD: " . $e->getMessage() . " SQL: " . $sql);
        return [];
    }
}

    public function countAllCompras()
    {
        $sql = "SELECT COUNT(c.idcompra) as total 
                FROM compra c"; 
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? intval($result['total']) : 0;
        } catch (PDOException $e) {
            error_log("ComprasModel::countAllCompras - Error de BD: " . $e->getMessage());
            return 0;
        }
    }

    public function countFilteredCompras($searchValue)
    {
        $sqlBase = "FROM compra c
                    JOIN proveedor p ON c.idproveedor = p.idproveedor
                    JOIN monedas m ON c.idmoneda_general = m.idmoneda";
        
        $bindings = [];
        $sqlWhere = "";

        if (!empty($searchValue)) {
            $sqlWhere = "WHERE (c.nro_compra LIKE :search_val 
                               OR c.fecha LIKE :search_val 
                               OR p.nombre LIKE :search_val 
                               OR c.total_general LIKE :search_val)";
            $bindings[':search_val'] = '%' . $searchValue . '%';
        }

        $sql = "SELECT COUNT(c.idcompra) as total
                $sqlBase
                $sqlWhere";
        try {
            $stmt = $this->db->prepare($sql);
            if (isset($bindings[':search_val'])) {
                $stmt->bindParam(':search_val', $bindings[':search_val'], PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? intval($result['total']) : 0;
        } catch (PDOException $e) {
            error_log("ComprasModel::countFilteredCompras - Error de BD: " . $e->getMessage());
            return 0;
        }
    }


    public function generarNumeroCompra()
    {
        $year = date("Y");
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(nro_compra, '-', -1) AS UNSIGNED)) as max_num
                FROM compra WHERE nro_compra LIKE :patron_nro";
        try {
            $stmt = $this->db->prepare($sql);
            $patron = "C-{$year}-%";
            $stmt->bindParam(':patron_nro', $patron, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result && isset($result['max_num'])) ? intval($result['max_num']) + 1 : 1;
            return "C-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);
        } catch (PDOException $e) {
            error_log("ComprasModel::generarNumeroCompra - Error de BD: " . $e->getMessage());
            return "C-" . $year . "-ERROR";
        }
    }

    public function buscarProveedor($termino)
    {
        $sql = "SELECT idproveedor, nombre, apellido, identificacion
                FROM proveedor
                WHERE (nombre LIKE :termino1 OR apellido LIKE :termino2 OR identificacion LIKE :termino3)
                AND estatus = 'activo' LIMIT 10";
        try {
            $stmt = $this->db->prepare($sql);
            $param = "%{$termino}%";
            $stmt->bindParam(':termino1', $param, PDO::PARAM_STR);
            $stmt->bindParam(':termino2', $param, PDO::PARAM_STR);
            $stmt->bindParam(':termino3', $param, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::buscarProveedor - Error de BD: " . $e->getMessage());
            return [];
        }
    }

    public function registrarProveedor(array $data)
    {
        $sql = "INSERT INTO proveedor (nombre, apellido, identificacion, direccion, correo_electronico, telefono_principal, genero, estatus)
                VALUES (:nombre, :apellido, :identificacion, :direccion, :correo, :telefono, :genero, 1)";
        try {
            $stmt = $this->db->prepare($sql);
            // Asegúrate de que las claves coincidan con las que envías desde el controlador/formulario
            $stmt->bindParam(':nombre', $data['nombre_proveedor_nuevo']);
            $apellido = $data['apellido_proveedor_nuevo'] ?? null; // Manejar si es opcional
            $stmt->bindParam(':apellido', $apellido);
            $stmt->bindParam(':identificacion', $data['identificacion_proveedor_nuevo']);
            $direccion = $data['direccion_proveedor_nuevo'] ?? null;
            $stmt->bindParam(':direccion', $direccion);
            $correo = $data['correo_proveedor_nuevo'] ?? null;
            $stmt->bindParam(':correo', $correo);
            $telefono = $data['telefono_proveedor_nuevo'] ?? null;
            $stmt->bindParam(':telefono', $telefono);
            $genero = $data['genero_proveedor_nuevo'] ?? null; // Asumiendo que 'genero' puede venir del form
            $stmt->bindParam(':genero', $genero);

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("ComprasModel::registrarProveedor - Error de BD: " . $e->getMessage());
            return false;
        }
    }

    public function getProveedorByIdentificacion(string $identificacion)
    {
        $sql = "SELECT idproveedor, nombre, apellido FROM proveedor WHERE identificacion = :identificacion AND estatus = 'activo'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':identificacion', $identificacion, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::getProveedorByIdentificacion - Error de BD: " . $e->getMessage());
            return false;
        }
    }

    public function getProductosConCategoria()
    {
        $sql = "SELECT
                    p.idproducto,
                    p.nombre AS nombre_producto,
                    p.idcategoria,
                    cp.nombre AS nombre_categoria,
                    p.precio AS precio_referencia_compra,
                    p.moneda AS idmoneda_producto 
                FROM
                    producto p
                JOIN
                    categoria cp ON p.idcategoria = cp.idcategoria
                LEFT JOIN
                    monedas m ON p.moneda = m.idmoneda 
                WHERE
                    p.estatus = 'activo'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::getProductosConCategoria - Error de BD: " . $e->getMessage());
            return [];
        }
    }

    public function getProductoById(int $idproducto)
    {
        $sql = "SELECT p.idproducto, p.nombre_producto, p.idcategoria,
                       p.precio_referencia_compra, p.idmoneda_referencia,
                       m.simbolo as moneda_simbolo, m.codigo_iso as moneda_codigo,
                       cp.nombre_categoria
                FROM productos p
                JOIN categorias_producto cp ON p.idcategoria = cp.idcategoria
                LEFT JOIN monedas m ON p.idmoneda_referencia = m.idmoneda
                WHERE p.idproducto = :idproducto AND p.estatus = 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idproducto', $idproducto, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::getProductoById - Error de BD: " . $e->getMessage());
            return false;
        }
    }

    public function getMonedasActivas()
    {
        // Asumiendo que tu tabla monedas tiene un campo 'estatus'
        $sql = "SELECT idmoneda, codigo_moneda, valor FROM monedas WHERE estado = 'activo'";
        // Si no tiene estatus, simplemente:
        // $sql = "SELECT idmoneda, nombre_moneda, simbolo, codigo_iso FROM monedas";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::getMonedasActivas - Error de BD: " . $e->getMessage());
            return [];
        }
    }

    public function insertarCompra(array $datosCompra, array $detallesCompra)
    {
        try {
            $this->db->beginTransaction();

            $sqlCompra = "INSERT INTO compra (nro_compra, fecha, idproveedor, idmoneda_general, subtotal_general, descuento_porcentaje_general, monto_descuento_general, total_general, observaciones_compra, estatus_compra)
                          VALUES (:nro_compra, :fecha, :idproveedor, :idmoneda_general, :subtotal_general, :descuento_porcentaje, :monto_descuento, :total_general, :observaciones, 'Pendiente')";
            
            $stmtCompra = $this->db->prepare($sqlCompra);
            $stmtCompra->bindParam(':nro_compra', $datosCompra['nro_compra']);
            $stmtCompra->bindParam(':fecha', $datosCompra['fecha_compra']);
            $stmtCompra->bindParam(':idproveedor', $datosCompra['idproveedor'], PDO::PARAM_INT);
            $stmtCompra->bindParam(':idmoneda_general', $datosCompra['idmoneda_general'], PDO::PARAM_INT);
            $stmtCompra->bindParam(':subtotal_general', $datosCompra['subtotal_general_compra']);
            $stmtCompra->bindParam(':descuento_porcentaje', $datosCompra['descuento_porcentaje_compra']);
            $stmtCompra->bindParam(':monto_descuento', $datosCompra['monto_descuento_compra']);
            $stmtCompra->bindParam(':total_general', $datosCompra['total_general_compra']);
            $stmtCompra->bindParam(':observaciones', $datosCompra['observaciones_compra']);
            
            if (!$stmtCompra->execute()) {
                $this->db->rollBack();
                error_log("ComprasModel::insertarCompra - Error al insertar cabecera: " . implode(", ", $stmtCompra->errorInfo()));
                return false;
            }
            $idCompra = $this->db->lastInsertId();

            if (!$idCompra) {
                $this->db->rollBack();
                error_log("ComprasModel::insertarCompra - No se pudo obtener lastInsertId para la compra.");
                return false;
            }

            $sqlDetalle = "INSERT INTO detalle_compras (idcompra, idproducto, descripcion_temporal_producto, cantidad, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                           VALUES (:idcompra, :idproducto, :descripcion, :cantidad, :precio_unitario, :idmoneda_detalle, :subtotal_linea, :peso_vehiculo, :peso_bruto, :peso_neto)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($detallesCompra as $detalle) {
                $stmtDetalle->bindParam(':idcompra', $idCompra, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idproducto', $detalle['idproducto'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':descripcion', $detalle['descripcion_temporal_producto']);
                $stmtDetalle->bindParam(':cantidad', $detalle['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $detalle['precio_unitario_compra']);
                $stmtDetalle->bindParam(':idmoneda_detalle', $detalle['idmoneda_detalle'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':subtotal_linea', $detalle['subtotal_linea']);
                $stmtDetalle->bindParam(':peso_vehiculo', $detalle['peso_vehiculo']);
                $stmtDetalle->bindParam(':peso_bruto', $detalle['peso_bruto']);
                $stmtDetalle->bindParam(':peso_neto', $detalle['peso_neto']);

                if (!$stmtDetalle->execute()) {
                    $this->db->rollBack();
                    error_log("ComprasModel::insertarCompra - Error al insertar detalle: " . implode(", ", $stmtDetalle->errorInfo()) . " para producto ID: " . $detalle['idproducto']);
                    return false;
                }
            }

            $this->db->commit();
            return $idCompra;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("ComprasModel::insertarCompra - Error de BD general: " . $e->getMessage());
            return false;
        }
    }
}
