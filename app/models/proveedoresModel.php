<?php
require_once("app/core/conexion.php"); 

class ProveedoresModel
{
    private $conexionObjeto;
    private $db;


    private $idproveedor; 
    private $nombre;
    private $apellido;
    private $identificacion;
    private $fecha_nacimiento;
    private $direccion;
    private $correo_electronico;
    private $estatus;
    private $telefono_principal;
    private $observaciones;
    private $genero;
    private $fecha_cracion; 
    private $fecha_modificacion;


    public function getIdproveedor() { return $this->idproveedor; } 
    public function setIdproveedor($idproveedor) { $this->idproveedor = $idproveedor; }

    public function getNombre() { return $this->nombre; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function getApellido() { return $this->apellido; }
    public function setApellido($apellido) { $this->apellido = $apellido; }
    
    public function getIdentificacion() { return $this->identificacion; }
    public function setIdentificacion($identificacion) { $this->identificacion = $identificacion; }
    public function getFechaNacimiento() { return $this->fecha_nacimiento; }
    public function setFechaNacimiento($fecha_nacimiento) { $this->fecha_nacimiento = $fecha_nacimiento; }
    public function getDireccion() { return $this->direccion; }
    public function setDireccion($direccion) { $this->direccion = $direccion; }
    public function getCorreoElectronico() { return $this->correo_electronico; }
    public function setCorreoElectronico($correo_electronico) { $this->correo_electronico = $correo_electronico; }
    public function getEstatus() { return $this->estatus; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }
    public function getTelefonoPrincipal() { return $this->telefono_principal; }
    public function setTelefonoPrincipal($telefono_principal) { $this->telefono_principal = $telefono_principal; }
    public function getObservaciones() { return $this->observaciones; }
    public function setObservaciones($observaciones) { $this->observaciones = $observaciones; }
    public function getGenero() { return $this->genero; }
    public function setGenero($genero) { $this->genero = $genero; }
    public function getFechaCracion() { return $this->fecha_cracion; }
    public function setFechaCracion($fecha_cracion) { $this->fecha_cracion = $fecha_cracion; }
    public function getFechaModificacion() { return $this->fecha_modificacion; }
    public function setFechaModificacion($fecha_modificacion) { $this->fecha_modificacion = $fecha_modificacion; }


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


    public function insertProveedor($data)
    {
        if (!$this->db) {
            error_log("ProveedoresModel: No hay conexión a la base de datos en insertProveedor.");
            return false;
        }

        $sql = "INSERT INTO proveedor (
                    nombre, apellido, identificacion, fecha_nacimiento, direccion, 
                    correo_electronico, estatus, telefono_principal, observaciones, genero, 
                    fecha_cracion, fecha_modificacion 
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $fechaActual = date('Y-m-d H:i:s');

        $arrValues = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['fecha_nacimiento'],
                $data['direccion'],
                $data['correo_electronico'],
                $data['estatus'],
                $data['telefono_principal'],
                $data['observaciones'],
                $data['genero'],
                $fechaActual,
                $fechaActual 
        ];
        $consulta = $stmt->execute($arrValues);
        return $consulta;
    }

    public function insertProveedorbackid($data)
    {
        if (!$this->db) {
            error_log("ProveedoresModel: No hay conexión a la base de datos en insertProveedor.");
            return false;
        }

        $sql = "INSERT INTO proveedor (
                    nombre, apellido, identificacion, fecha_nacimiento, direccion,
                    correo_electronico, estatus, telefono_principal, observaciones, genero,
                    fecha_cracion, fecha_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $return = false;

        try {
            $stmt = $this->db->prepare($sql);
            $fechaActual = date('Y-m-d H:i:s');

            $arrValues = [
                $data['nombre'] ?? null,
                $data['apellido'] ?? null,
                $data['identificacion'] ?? null,
                $data['fecha_nacimiento'] ?? null,
                $data['direccion'] ?? null,
                $data['correo_electronico'] ?? null,
                $data['estatus'] ?? 'ACTIVO',
                $data['telefono_principal'] ?? null,
                $data['observaciones'] ?? null,
                $data['genero'] ?? null,
                $fechaActual,
                $fechaActual
            ];
            
            $consulta = $stmt->execute($arrValues);
            
            if ($consulta) {
                $lastId = $this->db->lastInsertId();
                error_log("Último ID insertado: " . $lastId);
                return [
                    "status" => true,
                    "message" => "Proveedor registrado con éxito.",
                    "idproveedor" => $lastId
                ];
            } else {
                error_log("ProveedoresModel: Error al ejecutar la inserción: " . implode(", ", $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            error_log("ProveedoresModel: PDOException en insertProveedorbackid: " . $e->getMessage());
            return false;
        }
        
        return $return;
    }

    public function updateProveedor() {
        if (!$this->db || !$this->getIdproveedor()) { 
            error_log("ProveedoresModel: No hay conexión o ID para actualizar proveedor.");
            return false;
        }

        $sql = "UPDATE proveedor SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    identificacion = :identificacion, 
                    fecha_nacimiento = :fecha_nacimiento, 
                    direccion = :direccion, 
                    correo_electronico = :correo_electronico, 
                    estatus = :estatus, 
                    telefono_principal = :telefono_principal, 
                    observaciones = :observaciones,
                    genero = :genero,
                    fecha_modificacion = :fecha_modificacion_actual
                WHERE idproveedor = :idproveedor_actual"; 
        try {
            $stmt = $this->db->prepare($sql);
            $arrValues = [
                ':nombre' => $this->getNombre(),
                ':apellido' => $this->getApellido(),
                ':identificacion' => $this->getIdentificacion(),
                ':fecha_nacimiento' => !empty($this->getFechaNacimiento()) ? $this->getFechaNacimiento() : null,
                ':direccion' => $this->getDireccion(),
                ':correo_electronico' => $this->getCorreoElectronico(),
                ':estatus' => $this->getEstatus(),
                ':telefono_principal' => $this->getTelefonoPrincipal(),
                ':observaciones' => $this->getObservaciones(),
                ':genero' => $this->getGenero(),
                ':fecha_modificacion_actual' => date('Y-m-d H:i:s'),
                ':idproveedor_actual' => $this->getIdproveedor()
            ];
            return $stmt->execute($arrValues);
        } catch (PDOException $e) {
            error_log("ProveedoresModel: Error al actualizar proveedor - " . $e->getMessage());
            error_log("SQL Intentado (updateProveedor): " . $sql);
            error_log("Valores Intentados (updateProveedor): ");
            return false;
        }
    }

    public function selectAllProveedores() {
        
        $sql = "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento, 
                    direccion, correo_electronico, estatus, telefono_principal, 
                    observaciones, genero, fecha_cracion, fecha_modificacion
                FROM proveedor
                WHERE estatus = 'ACTIVO'";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProveedoresModel: Error al seleccionar todos los proveedores - " . $e->getMessage());
            return [];
        }
    }
    public function getProveedorById($idproveedor_param) {
        if (!$this->db) {
            error_log("ProveedoresModel: No hay conexión para getProveedorById.");
            return false;
        }
        $sql = "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento, 
                    direccion, correo_electronico, estatus, telefono_principal, 
                    observaciones, genero, fecha_cracion, fecha_modificacion
                FROM proveedor 
                WHERE idproveedor = :idproveedor_actual";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':idproveedor_actual' => $idproveedor_param]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $this->setIdproveedor($data['idproveedor']); 
                $this->setNombre($data['nombre']);
                $this->setApellido($data['apellido'] ?? '');
                $this->setIdentificacion($data['identificacion']);
                $this->setFechaNacimiento($data['fecha_nacimiento']);
                $this->setDireccion($data['direccion']);
                $this->setCorreoElectronico($data['correo_electronico']);
                $this->setEstatus($data['estatus']);
                $this->setTelefonoPrincipal($data['telefono_principal']);
                $this->setObservaciones($data['observaciones'] ?? '');
                $this->setGenero($data['genero'] ?? '');
                $this->setFechaCracion($data['fecha_cracion']);
                $this->setFechaModificacion($data['fecha_modificacion']);
            }
            return $data;
        } catch (PDOException $e) {
            error_log("ProveedoresModel: Error al obtener proveedor por ID - " . $e->getMessage());
            return false;
        }
    }

    public function deleteProveedor($idproveedor_param) { 
        if (!$this->db) {
            error_log("ProveedoresModel: No hay conexión para deleteProveedor.");
            return false;
        }
        $sql = "UPDATE proveedor SET estatus = 'INACTIVO', fecha_modificacion = :fecha_modificacion_actual WHERE idproveedor = :idproveedor_actual";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':fecha_modificacion_actual' => date('Y-m-d H:i:s'),
                ':idproveedor_actual' => $idproveedor_param
            ]);
        } catch (PDOException $e) {
            error_log("ProveedoresModel: Error al eliminar proveedor - " . $e->getMessage());
            return false;
        }
    }
}
?>
