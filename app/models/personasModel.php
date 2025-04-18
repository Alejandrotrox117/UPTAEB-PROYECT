<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");
class personasModel extends Mysql
{

    private $db;
    private $conexion;

    public function __construct(){
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connectGeneral();
        parent::__construct();
    }
    public function SelectAllPersonas() {
        $sql = "SELECT 
                    idpersona, 
                    nombre, 
                    apellido, 
                    cedula, 
                    rif, 
                    tipo, 
                    genero, 
                    fecha_nacimiento, 
                    telefono_principal, 
                    correo_electronico, 
                    direccion, 
                    ciudad, 
                    estado, 
                    pais, 
                    estatus 
                FROM personas";
        return $this->searchAll($sql);
    }

    public function insertPersona($data) {
        $sql = "INSERT INTO personas (
                    nombre, apellido, cedula, rif, tipo, genero, fecha_nacimiento, 
                    telefono_principal, correo_electronico, direccion, ciudad, estado, pais, estatus
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql); // Prepara la consulta
        $arrValues = [
            $data['nombre'], 
            $data['apellido'], 
            $data['cedula'], 
            $data['rif'], 
            $data['tipo'], 
            $data['genero'], 
            $data['fecha_nacimiento'], 
            $data['telefono_principal'], 
            $data['correo_electronico'], 
            $data['direccion'], 
            $data['ciudad'], 
            $data['estado'], 
            $data['pais'], 
            $data['estatus']
        ];
    
        return $stmt->execute($arrValues); // Ejecuta la consulta con los valores
    }
    
}
