<?php
require_once "app/core/Conexion.php";
require_once "app/core/Mysql.php";

class ComprasModel extends Mysql
{
    private $db; 
    private $conexionObjeto;

    // Propiedades de compra
    private $idcompra;
    private $nro_compra;
    private $fecha;
    private $idproveedor;
    private $idmoneda_general;
    private $subtotal_general;
    private $descuento_porcentaje_general;
    private $monto_descuento_general;
    private $total_general;
    private $estatus_compra;
    private $observaciones_compra;
    private $termino;
    private $identificacion;

    // Propiedades de detalle de compra
    private $iddetalle_compra;
    private $idproducto;
    private $descripcion_temporal_producto;
    private $cantidad;
    private $precio_unitario_compra;
    private $idmoneda_detalle;
    private $subtotal_linea;
    private $peso_vehiculo;
    private $peso_bruto;
    private $peso_neto;

    public function __construct()
    {
        parent::__construct();
        $this->conexionObjeto = new Conexion();
        $this->conexionObjeto->connect();
        $this->db = $this->conexionObjeto->get_conectGeneral();
    }

    // SETTERS Y GETTERS (mantener los existentes)
    public function setIdCompra($idcompra) { $this->idcompra = $idcompra; }
    public function getIdCompra() { return $this->idcompra; }
    public function setNroCompra($nro_compra) { $this->nro_compra = $nro_compra; }
    public function getNroCompra() { return $this->nro_compra; }
    public function setFecha($fecha) { $this->fecha = $fecha; }
    public function getFecha() { return $this->fecha; }
    public function setIdProveedor($idproveedor) { $this->idproveedor = $idproveedor; }
    public function getIdProveedor() { return $this->idproveedor; }
    public function setIdMonedaGeneral($idmoneda_general) { $this->idmoneda_general = $idmoneda_general; }
    public function getIdMonedaGeneral() { return $this->idmoneda_general; }
    public function setSubtotalGeneral($subtotal_general) { $this->subtotal_general = $subtotal_general; }
    public function getSubtotalGeneral() { return $this->subtotal_general; }
    public function setDescuentoPorcentajeGeneral($descuento_porcentaje_general) { $this->descuento_porcentaje_general = $descuento_porcentaje_general; }
    public function getDescuentoPorcentajeGeneral() { return $this->descuento_porcentaje_general; }
    public function setMontoDescuentoGeneral($monto_descuento_general) { $this->monto_descuento_general = $monto_descuento_general; }
    public function getMontoDescuentoGeneral() { return $this->monto_descuento_general; }
    public function setTotalGeneral($total_general) { $this->total_general = $total_general; }
    public function getTotalGeneral() { return $this->total_general; }
    public function setEstatusCompra($estatus_compra) { $this->estatus_compra = $estatus_compra; }
    public function getEstatusCompra() { return $this->estatus_compra; }
    public function setObservacionesCompra($observaciones_compra) { $this->observaciones_compra = $observaciones_compra; }
    public function getObservacionesCompra() { return $this->observaciones_compra; }
    public function setTermino($termino) { $this->termino = $termino; }
    public function getTermino() { return $this->termino; }
    public function setIdentificacion($identificacion) { $this->identificacion = $identificacion; }
    public function getIdentificacion() { return $this->identificacion; }
    public function setIdProducto($idproducto) { $this->idproducto = $idproducto; }
    public function getIdProducto() { return $this->idproducto; }

    //GET COMPRAS DATATABLE
    public function selectAllCompras(){
        $sql = "SELECT 
                    c.idcompra, 
                    c.nro_compra, 
                    c.fecha, 
                    CONCAT(p.nombre, ' ', COALESCE(p.apellido, '')) as proveedor,
                    c.total_general, 
                    c.estatus_compra,
                    c.observaciones_compra,
                    c.fecha_creacion, 
                    c.fecha_modificacion
                FROM compra c
                LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor
                ORDER BY c.fecha_creacion DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel: Error al seleccionar todas las compras - " . $e->getMessage());
            return [];
        }
    }

    //GENERAR NUMERO DE COMPRA (MANTENER COMO ESTÁ)
    public function generarNumeroCompra(){
        $year = date("Y");
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(nro_compra, '-', -1) AS UNSIGNED)) as max_num
                FROM compra WHERE nro_compra LIKE ?";
        $arrData = ["C-" . $year . "-%"];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result && isset($result['max_num'])) ? intval($result['max_num']) + 1 : 1;
            return "C-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);
        } catch (PDOException $e) {
            error_log("ComprasModel::generarNumeroCompra - Error de BD: " . $e->getMessage());
            return "C-" . $year . "-ERROR";
        }
    }

    //BUSCAR PROVEEDORES (MANTENER COMO ESTÁ)
    public function buscarProveedor($termino){
        $this->setTermino($termino);
        $sql = "SELECT idproveedor, nombre, apellido, identificacion
                FROM proveedor
                WHERE (nombre LIKE ? OR apellido LIKE ? OR identificacion LIKE ?)
                AND estatus = 'ACTIVO'
                LIMIT 10";
        $param = "%{$this->getTermino()}%";
        $arrData = [$param, $param, $param];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar Proveedores: " . $e->getMessage());
            return [];
        }
    }

    //BUSCAR PRODUCTOS (MANTENER COMO ESTÁ)
    public function getProductosConCategoria(){
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
            error_log("Error al obtener los productos" . $e->getMessage());
            return [];
        }
    }

    //BUSCAR PRODUCTO POR ID (MANTENER COMO ESTÁ)
    public function getProductoById(int $idproducto){
        $this->setIdProducto($idproducto);
        $sql = "SELECT p.idproducto, p.nombre, p.idcategoria,
                       p.precio, p.moneda,
                       m.codigo_moneda as codigo_moneda,
                       cp.nombre as nombre_categoria
                FROM producto p
                JOIN categoria cp ON p.idcategoria = cp.idcategoria
                LEFT JOIN monedas m ON p.moneda = m.idmoneda
                WHERE p.idproducto = ? AND p.estatus = 'activo'";
        $arrData = [$this->getIdProducto()];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al Obtener Producto: " . $e->getMessage());
            return false;
        }
    }

    //BUSCAR MONEDAS (MANTENER COMO ESTÁ)
    public function getMonedasActivas(){
        $sql = "SELECT idmoneda, codigo_moneda, valor FROM monedas WHERE estado = 'activo'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al Obtener las monedas" . $e->getMessage());
            return [];
        }
    }

    //BUSCAR TASAS BCV POR FECHA (MANTENER COMO ESTÁ)
    public function getTasasPorFecha($fecha){
        $this->setFecha($fecha);
        $sql = "SELECT codigo_moneda, tasa_a_bs 
            FROM historial_tasas_bcv 
            WHERE fecha_publicacion_bcv = ?";
        $arrData = [$this->getFecha()];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $tasas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tasas[$row['codigo_moneda']] = floatval($row['tasa_a_bs']);
            }
            return $tasas;
        } catch (PDOException $e) {
            error_log("Error al Consultar las Tasas: " . $e->getMessage());
            return [];
        }
    }

    //BUSCAR ULTIMO PESO REGISTRADO EN HISTORICO DE ROMANA (MANTENER COMO ESTÁ)
    public function getUltimoPesoRomana(){
        $sql = "SELECT peso FROM historial_romana ORDER BY idromana DESC LIMIT 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['peso'])) {
                return floatval($row['peso']);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            error_log("Ultimo Peso de la Romana - Error de BD: " . $e->getMessage());
            return null;
        }
    }

    //INSERTAR COMPRA (MANTENER COMO ESTÁ - FUNCIONAL)
    public function insertarCompra(array $datosCompra, array $detallesCompra){
        // ... Mantener toda la lógica existente de insertar compra que ya funciona
        $this->setNroCompra($datosCompra['nro_compra']);
        $this->setFecha($datosCompra['fecha_compra']);
        $this->setIdProveedor($datosCompra['idproveedor']);
        $this->setIdMonedaGeneral($datosCompra['idmoneda_general']);
        $this->setSubtotalGeneral($datosCompra['subtotal_general_compra']);
        $this->setDescuentoPorcentajeGeneral($datosCompra['descuento_porcentaje_compra']);
        $this->setMontoDescuentoGeneral($datosCompra['monto_descuento_compra']);
        $this->setTotalGeneral($datosCompra['total_general_compra']);
        $this->setObservacionesCompra($datosCompra['observaciones_compra']);

        try {
            $this->db->beginTransaction();

            $sqlCompra = "INSERT INTO compra (nro_compra, fecha, idproveedor, idmoneda_general, subtotal_general, descuento_porcentaje_general, monto_descuento_general, total_general, observaciones_compra, estatus_compra)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'BORRADOR')";
            $arrDataCompra = [
                $this->getNroCompra(),
                $this->getFecha(),
                $this->getIdProveedor(),
                $this->getIdMonedaGeneral(),
                $this->getSubtotalGeneral(),
                $this->getDescuentoPorcentajeGeneral(),
                $this->getMontoDescuentoGeneral(),
                $this->getTotalGeneral(),
                $this->getObservacionesCompra()
            ];

            $stmtCompra = $this->db->prepare($sqlCompra);
            if (!$stmtCompra->execute($arrDataCompra)) {
                $this->db->rollBack();
                throw new Exception("Error al insertar cabecera de compra");
            }
            $idCompra = $this->db->lastInsertId();

            // Insertar detalles
            $sqlDetalle = "INSERT INTO detalle_compra (idcompra, idproducto, descripcion_temporal_producto, cantidad, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($detallesCompra as $detalle) {
                $arrDataDetalle = [
                    $idCompra,
                    $detalle['idproducto'],
                    $detalle['descripcion_temporal_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario_compra'],
                    $detalle['idmoneda_detalle'],
                    $detalle['subtotal_linea'],
                    $detalle['peso_vehiculo'],
                    $detalle['peso_bruto'],
                    $detalle['peso_neto']
                ];

                if (!$stmtDetalle->execute($arrDataDetalle)) {
                    $this->db->rollBack();
                    throw new Exception("Error al insertar detalle de compra");
                }
            }

            $this->db->commit();
            return $idCompra;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al insertar compra: " . $e->getMessage());
            return false;
        }
    }

    //BUSCAR COMPRA POR ID
    public function getCompraById($idcompra){
        $sql = "SELECT 
                    c.*,
                    CONCAT(p.nombre, ' ', COALESCE(p.apellido, '')) as proveedor_nombre,
                    m.codigo_moneda
                FROM compra c 
                LEFT JOIN proveedor p ON c.idproveedor = p.idproveedor
                LEFT JOIN monedas m ON c.idmoneda_general = m.idmoneda
                WHERE c.idcompra = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idcompra]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener compra por ID: " . $e->getMessage());
            return false;
        }
    }

    //BUSCAR DETALLE DE COMPRA POR ID
    public function getDetalleCompraById($idcompra){
        $sql = "SELECT 
                    dc.*,
                    p.nombre as producto_nombre,
                    m.codigo_moneda
                FROM detalle_compra dc
                LEFT JOIN producto p ON dc.idproducto = p.idproducto
                LEFT JOIN monedas m ON dc.idmoneda_detalle = m.idmoneda
                WHERE dc.idcompra = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idcompra]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener detalle de compra: " . $e->getMessage());
            return [];
        }
    }

    //ACTUALIZAR COMPRA
    public function updateCompra(array $datosCompra, array $detallesCompra): array
    {
        try {
            $this->db->beginTransaction();

            // Actualizar cabecera de compra
            $sql = "UPDATE compra SET 
                    fecha = ?, 
                    idproveedor = ?, 
                    idmoneda_general = ?, 
                    subtotal_general = ?, 
                    descuento_porcentaje_general = ?, 
                    monto_descuento_general = ?, 
                    total_general = ?, 
                    observaciones_compra = ?,
                    fecha_modificacion = NOW() 
                    WHERE idcompra = ?";
            
            $valores = [
                $datosCompra['fecha_compra'],
                $datosCompra['idproveedor'],
                $datosCompra['idmoneda_general'],
                $datosCompra['subtotal_general_compra'],
                $datosCompra['descuento_porcentaje_compra'],
                $datosCompra['monto_descuento_compra'],
                $datosCompra['total_general_compra'],
                $datosCompra['observaciones_compra'],
                $datosCompra['idcompra']
            ];
            
            $stmt = $this->db->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso) {
                $this->db->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar la compra.'
                ];
            }

            // Si hay detalles, actualizar también
            if (!empty($detallesCompra)) {
                // Eliminar detalles existentes
                $sqlDeleteDetalles = "DELETE FROM detalle_compra WHERE idcompra = ?";
                $stmtDelete = $this->db->prepare($sqlDeleteDetalles);
                $stmtDelete->execute([$datosCompra['idcompra']]);

                // Insertar nuevos detalles
                $sqlDetalle = "INSERT INTO detalle_compra (idcompra, idproducto, descripcion_temporal_producto, cantidad, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtDetalle = $this->db->prepare($sqlDetalle);

                foreach ($detallesCompra as $detalle) {
                    $arrDataDetalle = [
                        $datosCompra['idcompra'],
                        $detalle['idproducto'],
                        $detalle['descripcion_temporal_producto'],
                        $detalle['cantidad'],
                        $detalle['precio_unitario_compra'],
                        $detalle['idmoneda_detalle'],
                        $detalle['subtotal_linea'],
                        $detalle['peso_vehiculo'] ?? null,
                        $detalle['peso_bruto'] ?? null,
                        $detalle['peso_neto'] ?? null
                    ];

                    if (!$stmtDetalle->execute($arrDataDetalle)) {
                        $this->db->rollBack();
                        return [
                            'status' => false, 
                            'message' => 'Error al actualizar detalles de compra.'
                        ];
                    }
                }
            }

            $this->db->commit();
            return [
                'status' => true, 
                'message' => 'Compra actualizada exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al actualizar compra: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar compra: ' . $e->getMessage()
            ];
        }
    }

    //Eliminacion Logica
    public function deleteCompraById(int $idcompra)
    {
        try {
            $this->db->beginTransaction();
            $sqlCompra = "
                UPDATE compra
                SET estatus_compra = 'inactivo'
                WHERE idcompra = ?
            ";
            $stmtCompra = $this->db->prepare($sqlCompra);
            $stmtCompra->execute([$idcompra]);

            $this->db->commit();
            return $stmtCompra->rowCount() > 0;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al marcar eliminar logicamente la compra " . $e->getMessage());
            return false;
        }
    }


    //CAMBIAR ESTADO DE COMPRA
    public function cambiarEstadoCompra(int $idcompra, string $nuevoEstado)
    {
        $estadosValidos = ['BORRADOR', 'POR_AUTORIZAR', 'AUTORIZADA', 'POR_PAGAR', 'PAGADA'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return [
                'status' => false,
                'message' => 'Estado no válido.'
            ];
        }

        try {
            // Obtener estado actual
            $sqlGetEstado = "SELECT estatus_compra FROM compra WHERE idcompra = ?";
            $stmtGet = $this->db->prepare($sqlGetEstado);
            $stmtGet->execute([$idcompra]);
            $compra = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                return [
                    'status' => false,
                    'message' => 'Compra no encontrada.'
                ];
            }

            $estadoActual = $compra['estatus_compra'];

            // Validar transición de estado
            if (!$this->validarTransicionEstado($estadoActual, $nuevoEstado)) {
                return [
                    'status' => false,
                    'message' => 'Transición de estado no válida.'
                ];
            }

            // Actualizar estado
            $sql = "UPDATE compra SET estatus_compra = ?, fecha_modificacion = NOW() WHERE idcompra = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nuevoEstado, $idcompra]);

            // Si el estado es PAGADA, generar nota de entrega
            if ($nuevoEstado === 'PAGADA') {
                $this->generarNotaEntrega($idcompra);
            }

            return [
                'status' => true,
                'message' => 'Estado de compra actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            error_log("Error al cambiar estado de compra: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al cambiar estado.'
            ];
        }
    }

    //VALIDAR TRANSICIÓN DE ESTADO
    private function validarTransicionEstado($estadoActual, $nuevoEstado): bool
    {
        $transicionesValidas = [
            'BORRADOR' => ['POR_AUTORIZAR'],
            'POR_AUTORIZAR' => ['AUTORIZADA', 'BORRADOR'],
            'AUTORIZADA' => ['POR_PAGAR'],
            'POR_PAGAR' => ['PAGADA', 'AUTORIZADA'],
            'PAGADA' => [] // Estado final
        ];

        return isset($transicionesValidas[$estadoActual]) && 
               in_array($nuevoEstado, $transicionesValidas[$estadoActual]);
    }

    //GENERAR NOTA DE ENTREGA
    private function generarNotaEntrega($idcompra)
    {
        try {
            // Generar número de nota de entrega
            $year = date("Y");
            $sqlNum = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_nota, '-', -1) AS UNSIGNED)) as max_num
                       FROM notas_entrega WHERE numero_nota LIKE ?";
            $stmtNum = $this->db->prepare($sqlNum);
            $stmtNum->execute(["NE-" . $year . "-%"]);
            $result = $stmtNum->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result && isset($result['max_num'])) ? intval($result['max_num']) + 1 : 1;
            $numeroNota = "NE-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);

            // Insertar nota de entrega
            $sqlNota = "INSERT INTO notas_entrega (numero_nota, idcompra, fecha_creacion, estado) 
                        VALUES (?, ?, NOW(), 'PENDIENTE')";
            $stmtNota = $this->db->prepare($sqlNota);
            $stmtNota->execute([$numeroNota, $idcompra]);

            return $numeroNota;
        } catch (Exception $e) {
            error_log("Error al generar nota de entrega: " . $e->getMessage());
            return false;
        }
    }

    //INSERTAR PROVEEDOR
    public function insertProveedor(array $data): array
    {
        try {
            $sql = "INSERT INTO proveedor (nombre, apellido, identificacion, telefono_principal, correo_electronico, direccion, fecha_nacimiento, genero, observaciones, estatus, fecha_creacion, fecha_modificacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', NOW(), NOW())";
            
            $valores = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['telefono_principal'],
                $data['correo_electronico'],
                $data['direccion'],
                $data['fecha_nacimiento'],
                $data['genero'],
                $data['observaciones']
            ];
            
            $stmt = $this->db->prepare($sql);
            $insertExitoso = $stmt->execute($valores);
            
            if ($insertExitoso) {
                $idProveedor = $this->db->lastInsertId();
                return [
                    'status' => true,
                    'message' => 'Proveedor registrado exitosamente.',
                    'idproveedor' => $idProveedor
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Error al registrar proveedor.'
                ];
            }

        } catch (PDOException $e) {
            error_log("Error al insertar proveedor: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error de base de datos al registrar proveedor: ' . $e->getMessage()
            ];
        }
    }

    //OBTENER PROVEEDOR POR ID
    public function getProveedorById($idproveedor)
    {
        $sql = "SELECT * FROM proveedor WHERE idproveedor = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idproveedor]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener proveedor por ID: " . $e->getMessage());
            return false;
        }
    }
}
?>
