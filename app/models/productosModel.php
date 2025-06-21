<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";
require_once "app/models/bitacoraModel.php";
require_once "app/models/notificacionesModel.php";

class ProductosModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $productoId;
    private $message;
    private $status;
    private $notificacionesModel;
 
    public function __construct()
    {
        $this->notificacionesModel = new NotificacionesModel();
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

    public function getProductoId(){
        return $this->productoId;
    }

    public function setProductoId(?int $productoId){
        $this->productoId = $productoId;
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

    private function ejecutarVerificacionProducto(string $nombre, int $idProductoExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM producto WHERE nombre = ?");
            $this->setArray([$nombre]);
            if ($idProductoExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idproducto != ?");
                $array = $this->getArray();
                $array[] = $idProductoExcluir;
                $this->setArray($array);
            }
            $stmt = $db->prepare($this->getQuery());

            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar producto existente: " . $e->getMessage());
            $exists = true;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    private function ejecutarInsercionProducto(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "INSERT INTO producto (
                    nombre, descripcion, unidad_medida, precio, 
                    idcategoria, moneda, estatus, existencia,
                    fecha_creacion, ultima_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            
            $this->setArray([
                $data['nombre'],
                $data['descripcion'],
                $data['unidad_medida'],
                $data['precio'],
                $data['idcategoria'],
                $data['moneda'],
                'ACTIVO',
                0 
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setProductoId($db->lastInsertId());
            
            if ($this->getProductoId()) {
                $this->setStatus(true);
                $this->setMessage('Producto registrado exitosamente.');
                
                // Crear notificación de nuevo producto
                $this->crearNotificacionNuevoProducto($data['nombre'], $this->getProductoId());
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de producto tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'producto_id' => $this->getProductoId()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al insertar producto: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al registrar producto: ' . $e->getMessage(),
                'producto_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarActualizacionProducto(int $idproducto, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "UPDATE producto SET 
                    nombre = ?, descripcion = ?, unidad_medida = ?, 
                    precio = ?, idcategoria = ?, moneda = ?, 
                    ultima_modificacion = NOW() 
                WHERE idproducto = ?"
            );
            
            $this->setArray([
                $data['nombre'],
                $data['descripcion'],
                $data['unidad_medida'],
                $data['precio'],
                $data['idcategoria'],
                $data['moneda'],
                $idproducto
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $this->setStatus(true);
                $this->setMessage('Producto actualizado exitosamente.');
                
                // Crear notificación de producto actualizado
                $this->crearNotificacionProductoActualizado($data['nombre'], $idproducto);
            } else {
                $this->setStatus(false);
                $this->setMessage('No se pudo actualizar el producto o no se realizaron cambios.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al actualizar producto: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error de base de datos al actualizar producto: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaProductoPorId(int $idproducto){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idproducto, p.nombre, p.descripcion, p.unidad_medida,
                    p.precio, p.existencia, p.idcategoria, p.moneda, p.estatus,
                    p.fecha_creacion, p.ultima_modificacion,
                    c.nombre as categoria_nombre,
                    DATE_FORMAT(p.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(p.ultima_modificacion, ?) as fecha_modificacion_formato
                FROM producto p 
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.idproducto = ?"
            );
            
            $this->setArray(['%d/%m/%Y %H:%i', '%d/%m/%Y %H:%i', $idproducto]);
        
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("ProductosModel::ejecutarBusquedaProductoPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminacionProducto(int $idproducto){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener nombre del producto antes de desactivarlo
            $this->setQuery("SELECT nombre FROM producto WHERE idproducto = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idproducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->setQuery("UPDATE producto SET estatus = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $this->setArray(['INACTIVO', $idproducto]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
            if ($resultado && $producto) {
                // Crear notificación de producto desactivado
                $this->crearNotificacionProductoDesactivado($producto['nombre'], $idproducto);
            }
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarEliminacionProducto -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaTodosProductos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idproducto, p.nombre, p.descripcion, p.unidad_medida,
                    p.precio, p.existencia, p.idcategoria, p.moneda, p.estatus,
                    p.fecha_creacion, p.ultima_modificacion,
                    c.nombre as categoria_nombre,
                    DATE_FORMAT(p.fecha_creacion, ?) as fecha_creacion_formato,
                    DATE_FORMAT(p.ultima_modificacion, ?) as fecha_modificacion_formato
                FROM producto p 
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                ORDER BY p.nombre ASC"
            );
            
            $this->setArray(['%d/%m/%Y', '%d/%m/%Y']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Productos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarBusquedaTodosProductos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener productos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaProductosActivos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idproducto, p.nombre, p.descripcion, p.unidad_medida,
                    p.precio, p.existencia, p.moneda,
                    c.nombre as categoria_nombre
                FROM producto p 
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.estatus = ?
                ORDER BY p.nombre ASC"
            );
            
            $this->setArray(['ACTIVO']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Productos activos obtenidos.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarBusquedaProductosActivos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener productos activos: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaCategoriasActivas(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    idcategoria, nombre, descripcion
                FROM categoria 
                WHERE estatus = ?
                ORDER BY nombre ASC"
            );
            
            $this->setArray(['ACTIVO']);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Categorías activas obtenidas.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarBusquedaCategoriasActivas - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener categorías: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarActivacionProducto(int $idproducto){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Obtener nombre del producto antes de activarlo
            $this->setQuery("SELECT nombre FROM producto WHERE idproducto = ?");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([$idproducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->setQuery("UPDATE producto SET estatus = ?, ultima_modificacion = NOW() WHERE idproducto = ?");
            $this->setArray(['ACTIVO', $idproducto]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
            if ($resultado && $producto) {
                // Crear notificación de producto activado
                $this->crearNotificacionProductoActivado($producto['nombre'], $idproducto);
            }
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarActivacionProducto -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaProductos(string $termino){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT 
                    p.idproducto, p.nombre, p.descripcion, p.unidad_medida,
                    p.precio, p.existencia, p.moneda, p.estatus,
                    c.nombre as categoria_nombre
                FROM producto p 
                LEFT JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.nombre LIKE ? OR p.descripcion LIKE ? OR c.nombre LIKE ?
                ORDER BY p.nombre ASC"
            );
            
            $terminoBusqueda = '%' . $termino . '%';
            $this->setArray([$terminoBusqueda, $terminoBusqueda, $terminoBusqueda]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                "status" => true,
                "message" => "Búsqueda completada.",
                "data" => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("ProductosModel::ejecutarBusquedaProductos - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error en la búsqueda: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos para crear notificaciones
    private function crearNotificacionNuevoProducto(string $nombreProducto, int $productoId){
        try {
            $dataNotificacion = [
                'tipo' => 'PRODUCTO_NUEVO',
                'titulo' => 'Nuevo Producto Registrado',
                'mensaje' => "Se ha registrado un nuevo producto: '{$nombreProducto}'",
                'modulo' => 'productos',
                'referencia_id' => $productoId,
                'rol_destinatario' => $this->obtenerRolProductos(),
                'prioridad' => 'MEDIA'
            ];
            
            $this->notificacionesModel->crearNotificacion($dataNotificacion);
        } catch (Exception $e) {
            error_log("Error al crear notificación de nuevo producto: " . $e->getMessage());
        }
    }

    private function crearNotificacionProductoActualizado(string $nombreProducto, int $productoId){
        try {
            $dataNotificacion = [
                'tipo' => 'PRODUCTO_ACTUALIZADO',
                'titulo' => 'Producto Actualizado',
                'mensaje' => "El producto '{$nombreProducto}' ha sido actualizado",
                'modulo' => 'productos',
                'referencia_id' => $productoId,
                'rol_destinatario' => $this->obtenerRolProductos(),
                'prioridad' => 'BAJA'
            ];
            
            $this->notificacionesModel->crearNotificacion($dataNotificacion);
        } catch (Exception $e) {
            error_log("Error al crear notificación de producto actualizado: " . $e->getMessage());
        }
    }

    private function crearNotificacionProductoDesactivado(string $nombreProducto, int $productoId){
        try {
            $dataNotificacion = [
                'tipo' => 'PRODUCTO_DESACTIVADO',
                'titulo' => 'Producto Desactivado',
                'mensaje' => "El producto '{$nombreProducto}' ha sido desactivado",
                'modulo' => 'productos',
                'referencia_id' => $productoId,
                'rol_destinatario' => $this->obtenerRolProductos(),
                'prioridad' => 'MEDIA'
            ];
            
            $this->notificacionesModel->crearNotificacion($dataNotificacion);
        } catch (Exception $e) {
            error_log("Error al crear notificación de producto desactivado: " . $e->getMessage());
        }
    }

    private function crearNotificacionProductoActivado(string $nombreProducto, int $productoId){
        try {
            $dataNotificacion = [
                'tipo' => 'PRODUCTO_ACTIVADO',
                'titulo' => 'Producto Activado',
                'mensaje' => "El producto '{$nombreProducto}' ha sido activado",
                'modulo' => 'productos',
                'referencia_id' => $productoId,
                'rol_destinatario' => $this->obtenerRolProductos(),
                'prioridad' => 'BAJA'
            ];
            
            $this->notificacionesModel->crearNotificacion($dataNotificacion);
        } catch (Exception $e) {
            error_log("Error al crear notificación de producto activado: " . $e->getMessage());
        }
    }

    private function obtenerRolProductos(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT r.idrol FROM rol r 
                JOIN roles_permisos rp ON r.idrol = rp.rol_id 
                JOIN permisos p ON rp.permiso_id = p.idpermiso 
                WHERE p.modulo = 'productos' AND p.accion = 'ver' AND r.estatus = 'ACTIVO'
                LIMIT 1"
            );
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['idrol'] : null;
            
        } catch (Exception $e) {
            error_log("Error al obtener rol de productos: " . $e->getMessage());
            return null;
        } finally {
            $conexion->disconnect();
        }
    }

    // Métodos públicos que usan las funciones privadas
    public function insertProducto(array $data){
        $this->setData($data);
        $nombre = $this->getData()['nombre'];

        if ($this->ejecutarVerificacionProducto($nombre)) {
            return [
                'status' => false,
                'message' => 'Ya existe un producto con ese nombre.',
                'producto_id' => null
            ];
        }

        return $this->ejecutarInsercionProducto($this->getData());
    }

    public function updateProducto(int $idproducto, array $data){
        $this->setData($data);
        $this->setProductoId($idproducto);
        $nombre = $this->getData()['nombre'];

        if ($this->ejecutarVerificacionProducto($nombre, $this->getProductoId())) {
            return [
                'status' => false,
                'message' => 'Ya existe otro producto con ese nombre.'
            ];
        }

        return $this->ejecutarActualizacionProducto($this->getProductoId(), $this->getData());
    }

    public function selectProductoById(int $idproducto){
        $this->setProductoId($idproducto);
        return $this->ejecutarBusquedaProductoPorId($this->getProductoId());
    }

    public function deleteProductoById(int $idproducto){
        $this->setProductoId($idproducto);
        return $this->ejecutarEliminacionProducto($this->getProductoId());
    }

    public function selectAllProductos(){
        return $this->ejecutarBusquedaTodosProductos();
    }

    public function selectProductosActivos(){
        return $this->ejecutarBusquedaProductosActivos();
    }

    public function selectCategoriasActivas(){
        return $this->ejecutarBusquedaCategoriasActivas();
    }

    public function activarProductoById(int $idproducto){
        $this->setProductoId($idproducto);
        return $this->ejecutarActivacionProducto($this->getProductoId());
    }

    public function buscarProductos(string $termino){
        return $this->ejecutarBusquedaProductos($termino);
    }
}
?>