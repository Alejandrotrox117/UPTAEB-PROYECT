<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class productosModel extends Mysql
{
    private $conexionObjeto;
    private $db;

    private $idproducto;
    private $nombre;
    private $descripcion;
    private $unidad_medida;
    private $precio;
    private $existencia;
    private $idcategoria;
    private $moneda;
    private $estatus;

    public function __construct()
    {
        $this->conexionObjeto = new Conexion();
        $this->db = $this->conexionObjeto->connect();
    }

    public function __destruct()
    {
        if ($this->conexionObjeto) {
            $this->conexionObjeto->disconnect();
        }
    }


    // Métodos Getters y Setters
    public function getIdproducto() {
        return $this->idproducto;
    }

    public function setIdproducto($idproducto) {
        $this->idproducto = $idproducto;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
    }

    public function getUnidadMedida() {
        return $this->unidad_medida;
    }

    public function setUnidadMedida($unidad_medida) {
        $this->unidad_medida = $unidad_medida;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function setPrecio($precio) {
        $this->precio = $precio;
    }

    public function getExistencia() {
        return $this->existencia;
    }

    public function setExistencia($existencia) {
        $this->existencia = $existencia;
    }

    public function getIdcategoria() {
        return $this->idcategoria;
    }

    public function setIdcategoria($idcategoria) {
        $this->idcategoria = $idcategoria;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }

    public function getMoneda() {
        return $this->moneda;
    }

    public function setMoneda($moneda) {
        $this->moneda = $moneda;
    }
    
public function SelectAllProductos() {
    $sql = "SELECT 
                    p.idproducto, 
                    p.nombre, 
                    p.descripcion, 
                    p.unidad_medida, 
                    p.precio, 
                    p.existencia, 
                    c.nombre AS nombre_categoria,
                    p.idcategoria,
                    p.moneda,                
                    p.estatus, 
                    p.fecha_creacion, 
                    p.ultima_modificacion
                FROM 
                    producto p 
                LEFT JOIN 
                    categoria c ON p.idcategoria = c.idcategoria 
                WHERE 
                    p.estatus = 'activo'";
    try {
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("ProductosModel: Error al seleccionar todos los productos con categoría - " . $e->getMessage());
        return [];
    }
}



    public function insertProducto($data)
    {
        $sql = "INSERT INTO producto (
                    nombre, descripcion, unidad_medida, precio, existencia, idcategoria, moneda, estatus
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['descripcion'],
            $data['unidad_medida'],
            $data['precio'],
            $data['existencia'],
            $data['idcategoria'],
            $data['moneda'],
            $data['estatus']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para eliminar lógicamente un producto
    public function deleteProducto($idproducto) {
        $sql = "UPDATE producto SET estatus = 'INACTIVO' WHERE idproducto = ?";
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$idproducto]); 
    }

    // Método para actualizar un producto
    public function updateProducto($data)
    {
        $sql = "UPDATE producto SET 
                    nombre = ?, 
                    descripcion = ?, 
                    unidad_medida = ?, 
                    precio = ?, 
                    existencia = ?, 
                    idcategoria = ?,
                    moneda = ?,
                    estatus = ? 
                WHERE idproducto = ?";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['descripcion'],
            $data['unidad_medida'],
            $data['precio'],
            $data['existencia'],
            $data['idcategoria'],
            $data['moneda'],
            $data['estatus'],
            $data['idproducto']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para obtener un producto por ID
    public function getProductoById($idproducto) {
        $sql = "SELECT * FROM producto WHERE idproducto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproducto]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Asignar los valores a las propiedades del objeto
            $this->setIdproducto($data['idproducto']);
            $this->setNombre($data['nombre']);
            $this->setDescripcion($data['descripcion']);
            $this->setUnidadMedida($data['unidad_medida']);
            $this->setPrecio($data['precio']);
            $this->setExistencia($data['existencia']);
            $this->setIdcategoria($data['idcategoria']);
            $this->setMoneda($data['moneda']);
            $this->setEstatus($data['estatus']);
        }

        return $data; 
    }
    public function SelectAllCategorias()
    {
        $sql = "SELECT * FROM categoria WHERE estatus = 'activo'";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CategoriasModel: Error al seleccionar todos las categorias- " . $e->getMessage());
            return [];
        }
    }
}