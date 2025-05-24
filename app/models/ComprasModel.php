<?php
require_once "app/core/Conexion.php";
require_once "app/core/Mysql.php";

class ComprasModel extends Mysql
{
    private $db; 
    private $conexionObjeto;

     // Compra
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

    // Detalle de compra
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

    //SETTERS
    public function setIdCompra($idcompra) {
        $this->idcompra = $idcompra;
    }

    public function setNroCompra($nro_compra) {
        $this->nro_compra = $nro_compra;
    }

    public function setFecha($fecha) {
        $this->fecha = $fecha;
    }

    public function setIdProveedor($idproveedor) {
        $this->idproveedor = $idproveedor;
    }

    public function setIdMonedaGeneral($idmoneda_general) {
        $this->idmoneda_general = $idmoneda_general;
    }

    public function setSubtotalGeneral($subtotal_general) {
        $this->subtotal_general = $subtotal_general;
    }

    public function setDescuentoPorcentajeGeneral($descuento_porcentaje_general) {
        $this->descuento_porcentaje_general = $descuento_porcentaje_general;
    }

    public function setMontoDescuentoGeneral($monto_descuento_general) {
        $this->monto_descuento_general = $monto_descuento_general;
    }

    public function setTotalGeneral($total_general) {
        $this->total_general = $total_general;
    }

    public function setEstatusCompra($estatus_compra) {
        $this->estatus_compra = $estatus_compra;
    }

    public function setObservacionesCompra($observaciones_compra) {
        $this->observaciones_compra = $observaciones_compra;
    }

    public function setIdDetalleCompra($iddetalle_compra) {
        $this->iddetalle_compra = $iddetalle_compra;
    }

    public function setIdProducto($idproducto) {
        $this->idproducto = $idproducto;
    }

    public function setDescripcionTemporalProducto($descripcion_temporal_producto) {
        $this->descripcion_temporal_producto = $descripcion_temporal_producto;
    }

    public function setCantidad($cantidad) {
        $this->cantidad = $cantidad;
    }
    public function setPrecioUnitarioCompra($precio_unitario_compra) {
        $this->precio_unitario_compra = $precio_unitario_compra;
    }

    public function setIdMonedaDetalle($idmoneda_detalle) {
        $this->idmoneda_detalle = $idmoneda_detalle;
    }

    public function setSubtotalLinea($subtotal_linea) {
        $this->subtotal_linea = $subtotal_linea;
    }

    public function setPesoVehiculo($peso_vehiculo) {
        $this->peso_vehiculo = $peso_vehiculo;
    }

    public function setPesoBruto($peso_bruto) {
        $this->peso_bruto = $peso_bruto;
    }

    public function setPesoNeto($peso_neto) {
        $this->peso_neto = $peso_neto;
    }

    public function setTermino($termino) {
        $this->termino = $termino;
    }

    public function setIdentificacion($identificacion) {
        $this->identificacion = $identificacion;
    }

    //GETTERS

    public function getIdCompra() {
        return $this->idcompra;
    }

    public function getNroCompra() {
        return $this->nro_compra;
    }

    public function getFecha() {
        return $this->fecha;
    }

    public function getIdProveedor() {
        return $this->idproveedor;
    }

    public function getIdMonedaGeneral() {
        return $this->idmoneda_general;
    }

    public function getSubtotalGeneral() {
        return $this->subtotal_general;
    }

    public function getDescuentoPorcentajeGeneral() {
        return $this->descuento_porcentaje_general;
    }

    public function getMontoDescuentoGeneral() {
        return $this->monto_descuento_general;
    }

    public function getTotalGeneral() {
        return $this->total_general;
    }

    public function getEstatusCompra() {
        return $this->estatus_compra;
    }

    public function getObservacionesCompra() {
        return $this->observaciones_compra;
    }

    public function getIdDetalleCompra() {
        return $this->iddetalle_compra;
    }

    public function getIdProducto() {
        return $this->idproducto;
    }

    public function getDescripcionTemporalProducto() {
        return $this->descripcion_temporal_producto;
    }

    public function getCantidad() {
        return $this->cantidad;
    }

    public function getPrecioUnitarioCompra() {
        return $this->precio_unitario_compra;
    }

    public function getIdMonedaDetalle() {
        return $this->idmoneda_detalle;
    }

    public function getSubtotalLinea() {
        return $this->subtotal_linea;
    }

    public function getPesoVehiculo() {
        return $this->peso_vehiculo;
    }

    public function getPesoBruto() {
        return $this->peso_bruto;
    }

    public function getPesoNeto() {
        return $this->peso_neto;
    }

    public function getTermino() {
        return $this->termino;
    }

    public function getIdentificacion() {
        return $this->identificacion;
    }   




    // Constructor
    public function __construct()
    {
        parent::__construct();
        $this->conexionObjeto = new Conexion();
        $this->conexionObjeto->connect(); // Asegúrate de conectar antes de obtener la conexión
        $this->db = $this->conexionObjeto->get_conectGeneral();
       

    }

    //GET COMPRAS DATATABLE
    public function selectAllCompras(){
        
        $sql = "SELECT `idcompra`, `nro_compra`, `fecha`, `idproveedor`, `idmoneda_general`, `subtotal_general`, `descuento_porcentaje_general`, `monto_descuento_general`, `total_general`, `estatus_compra`, `observaciones_compra`, `fecha_creacion`, `fecha_modificacion`
                FROM compra";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel: Error al seleccionar todos los proveedores - " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }

    //GENERAR NUMERO DE COMPRA
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
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return "C-" . $year . "-" . str_pad($next_num, 5, "0", STR_PAD_LEFT);
        } catch (PDOException $e) {
            error_log("ComprasModel::generarNumeroCompra - Error de BD: " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return "C-" . $year . "-ERROR";
        }
    }

    //BUSCAR PROVEEDORES
    public function buscarProveedor($termino){
        $this->setTermino($termino);
        $sql = "SELECT idproveedor, nombre, apellido, identificacion
                FROM proveedor
                WHERE (nombre LIKE ? OR apellido LIKE ? OR identificacion LIKE ?)
                AND estatus = 'activo'
                LIMIT 10";
        $param = "%{$this->getTermino()}%";
        $arrData = [$param, $param, $param];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $result;
        } catch (PDOException $e) {
            error_log("Error al buscar Proveedores: " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }

    //BUSCAR PROVEEDOR POR ID
    public function getProveedorByIdentificacion(string $identificacion){
        $this->setIdentificacion($identificacion);
        $sql = "SELECT idproveedor, nombre, apellido 
                FROM proveedor 
                WHERE identificacion = ? AND estatus = 'activo'";
        $arrData = [$this->getIdentificacion()];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al consultar el proveedor: " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return false;
        }
    }

    //BUSCAR PRODUCTOS
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
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener los productos" . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }

    //BUSCAR PRODUCTO POR ID
    public function getProductoById(int $idproducto){
        $this->setIdProducto($idproducto);
        $sql = "SELECT p.idproducto, p.nombre, p.idcategoria,
                       p.precio, p.moneda,
                       m.codigo_moneda as codigo_moneda,
                       cp.nombre
                FROM producto p
                JOIN categoria cp ON p.idcategoria = cp.idcategoria
                LEFT JOIN monedas m ON p.moneda = m.codigo_moneda
                WHERE p.idproducto = ? AND p.estatus = 'activo'";
        $arrData = [$this->getIdProducto()];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Eror al Obtener Producto: " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return false;
        }
    }

    //BUSCAR MONEDAS
    public function getMonedasActivas(){
        $sql = "SELECT idmoneda, codigo_moneda, valor FROM monedas WHERE estado = 'activo'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al Obtener las monedas" . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }

    //BUSCAR MONEDA POR CODIGO ¨USD¨ U OTROS
    public function getIdMonedaByCodigo($codigoMoneda){
        $this->setIdMonedaDetalle($codigoMoneda);
        $sql = "SELECT idmoneda FROM monedas WHERE codigo_moneda = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getIdMonedaDetalle()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->conexionObjeto->disconnect(); // Cierra la conexión
        return $row;
    }

    //INSERTAR COMPRA
    public function insertarCompra(array $datosCompra, array $detallesCompra){
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
            $tasas = $this->getTasasMonedas();
            $this->db->beginTransaction();

            // Validar y convertir monedas en los detalles
            foreach ($detallesCompra as &$detalle) {
                if (!is_numeric($detalle['idmoneda_detalle'])) {
                    $idMoneda = $this->getIdMonedaByCodigo($detalle['idmoneda_detalle']);
                    if ($idMoneda === null) {
                        throw new Exception("Código de moneda inválido: " . $detalle['idmoneda_detalle']);
                    }
                    $detalle['idmoneda_detalle'] = $idMoneda;
                }
            }
            unset($detalle);
            $sqlCompra = "INSERT INTO compra (nro_compra, fecha, idproveedor, idmoneda_general, subtotal_general, descuento_porcentaje_general, monto_descuento_general, total_general, observaciones_compra, estatus_compra)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
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
                $errorInfo = $stmtCompra->errorInfo();
                throw new Exception("Error al insertar cabecera: " . implode(" | ", $errorInfo));
            }
            $idCompra = $this->db->lastInsertId();
            $this->setIdCompra($idCompra);

            // Insertar detalles de compra
            $sqlDetalle = "INSERT INTO detalle_compra (idcompra, idproducto, descripcion_temporal_producto, cantidad, precio_unitario_compra, idmoneda_detalle, subtotal_linea, peso_vehiculo, peso_bruto, peso_neto)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($detallesCompra as $detalle) {
                $this->setIdProducto($detalle['idproducto']);
                $this->setDescripcionTemporalProducto($detalle['descripcion_temporal_producto']);
                $this->setCantidad($detalle['cantidad']);
                $this->setPrecioUnitarioCompra($detalle['precio_unitario_compra']);
                $this->setIdMonedaDetalle($detalle['idmoneda_detalle']);
                $this->setSubtotalLinea($detalle['subtotal_linea']);
                $this->setPesoVehiculo($detalle['peso_vehiculo']);
                $this->setPesoBruto($detalle['peso_bruto']);
                $this->setPesoNeto($detalle['peso_neto']);

                $arrDataDetalle = [
                    $this->getIdCompra(),
                    $this->getIdProducto(),
                    $this->getDescripcionTemporalProducto(),
                    $this->getCantidad(),
                    $this->getPrecioUnitarioCompra(),
                    $this->getIdMonedaDetalle(),
                    $this->getSubtotalLinea(),
                    $this->getPesoVehiculo(),
                    $this->getPesoBruto(),
                    $this->getPesoNeto()
                ];

                if (!$stmtDetalle->execute($arrDataDetalle)) {
                    $this->db->rollBack();
                    $errorInfo = $stmtDetalle->errorInfo();
                    throw new Exception(
                        "Error al insertar detalle: " . implode(" | ", $errorInfo) .
                        " para producto ID: " . $detalle['idproducto']
                    );
                }
            }

            $this->db->commit();
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $idCompra;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            throw new Exception("Insertar Compras - Error: " . $e->getMessage());
        }
    }


    //BUSCAR TASAS BCV
    public function getTasasMonedas(){
        $sql = "SELECT idmoneda, valor FROM monedas WHERE estado = 'activo'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tasas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tasas[$row['idmoneda']] = floatval($row['valor']);
            }
            return $tasas;
            $this->conexionObjeto->disconnect(); // Cierra la conexión
        } catch (PDOException $e) {
            error_log("Tasas Monedas - Error de BD: " . $e->getMessage());
            return [];
            $this->conexionObjeto->disconnect(); // Cierra la conexión
        }
    }

    //BUSCAR TASAS BCV POR FECHA
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
            $this->conexionObjeto->disconnect(); // Cierra la conexión

        } catch (PDOException $e) {
        error_log("Error al Consultar las Tasas: " . $e->getMessage());
        return [];
        $this->conexionObjeto->disconnect(); // Cierra la conexión
     }
    }

    //BUSCAR ULTIMO PESO REGISTRADO EN HISTORICO DE ROMANA
    public function getUltimoPesoRomana(){
        $sql = "SELECT peso FROM historial_romana ORDER BY idromana DESC LIMIT 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['peso'])) {
                $this->conexionObjeto->disconnect(); // Cierra la conexión
                return floatval($row['peso']);
            } else {
                $this->conexionObjeto->disconnect(); // Cierra la conexión
                return null;
                
            }
        } catch (PDOException $e) {
            error_log("Ultimo Peso de la Romana - Error de BD: " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return null;
            
        }
    }

    //BUSCAR COMPRA POR ID
    public function getCompraById($idcompra){
        $sql = "SELECT c.*, p.nombre as proveedor FROM compra c JOIN proveedor p ON c.idproveedor = p.idproveedor WHERE c.idcompra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getIdCompra($idcompra)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //BUSCAR DETALLE DE COMPRA POR ID
    public function getDetalleCompraById($idcompra){
        $sql = "SELECT * FROM detalle_compra WHERE idcompra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getIdCompra($idcompra)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //EDITAR COMPRA
    public function editarCompra(array $datosCompra, array $detallesCompra){
        $this->setIdCompra($datosCompra['idcompra']);
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
            $tasas = $this->getTasasMonedas();
            $this->db->beginTransaction();

            // Validar y convertir monedas en los detalles
            foreach ($detallesCompra as &$detalle) {
                if (!is_numeric($detalle['idmoneda_detalle'])) {
                    $idMoneda = $this->getIdMonedaByCodigo($detalle['idmoneda_detalle']);
                    if ($idMoneda === null) {
                        throw new Exception("Código de moneda inválido: " . $detalle['idmoneda_detalle']);
                    }
                    $detalle['idmoneda_detalle'] = $idMoneda;
                }
            }
            unset($detalle);

            // Actualizar la compra
            $sqlCompra = "UPDATE compra SET nro_compra = ?, fecha = ?, idproveedor = ?, idmoneda_general = ?, subtotal_general = ?, descuento_porcentaje_general = ?, monto_descuento_general = ?, total_general = ?, observaciones_compra = ? WHERE idcompra = ?";
            $arrDataCompra = [
                $this->getNroCompra(),
                $this->getFecha(),
                $this->getIdProveedor(),
                $this->getIdMonedaGeneral(),
                $this->getSubtotalGeneral(),
                $this->getDescuentoPorcentajeGeneral(),
                $this->getMontoDescuentoGeneral(),
                $this->getTotalGeneral(),
                $this->getObservacionesCompra(),
                $this->getIdCompra()
            ];

            $stmtCompra = $this->db->prepare($sqlCompra);
            if (!$stmtCompra->execute($arrDataCompra)) {
                $this->db->rollBack();
                $errorInfo = $stmtCompra->errorInfo();
                throw new Exception("Error al actualizar cabecera: " . implode(" | ", $errorInfo));
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            throw new Exception("Editar Compras - Error: " . $e->getMessage());
        }
    }


}
