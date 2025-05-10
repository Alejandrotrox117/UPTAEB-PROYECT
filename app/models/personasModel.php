<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class PersonasModel extends Mysql
{
    private $db;
    private $conexion;

 
    private $idpersona;
    private $nombre;
    private $apellido;
    private $cedula;
    private $rif;
    private $tipo;
    private $genero;
    private $fecha_nacimiento;
    private $telefono_principal;
    private $correo_electronico;
    private $direccion;
    private $ciudad;
    private $estado;
    private $pais;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->db = (new Conexion())->connect();
        
    }

    // Métodos Getters y Setters
    public function getIdpersona() {
        return $this->idpersona;
    }

    public function setIdpersona($idpersona) {
        $this->idpersona = $idpersona;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getApellido() {
        return $this->apellido;
    }

    public function setApellido($apellido) {
        $this->apellido = $apellido;
    }

    public function getCedula() {
        return $this->cedula;
    }

    public function setCedula($cedula) {
        $this->cedula = $cedula;
    }

    public function getRif() {
        return $this->rif;
    }

    public function setRif($rif) {
        $this->rif = $rif;
    }

    public function getTipo() {
        return $this->tipo;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function getGenero() {
        return $this->genero;
    }

    public function setGenero($genero) {
        $this->genero = $genero;
    }

    public function getFechaNacimiento() {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento($fecha_nacimiento) {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function getTelefonoPrincipal() {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal($telefono_principal) {
        $this->telefono_principal = $telefono_principal;
    }

    public function getCorreoElectronico() {
        return $this->correo_electronico;
    }

    public function setCorreoElectronico($correo_electronico) {
        $this->correo_electronico = $correo_electronico;
    }

    public function getDireccion() {
        return $this->direccion;
    }

    public function setDireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getCiudad() {
        return $this->ciudad;
    }

    public function setCiudad($ciudad) {
        $this->ciudad = $ciudad;
    }

    public function getEstado() {
        return $this->estado;
    }

    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function getPais() {
        return $this->pais;
    }

    public function setPais($pais) {
        $this->pais = $pais;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }

    // Método para seleccionar todas las personas activas
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
                FROM personas 
                WHERE estatus = 'ACTIVO'";
        return $this->searchAll($sql);
    }

    // Método para insertar una nueva persona
    public function insertPersona($data)
{
    $sql = "INSERT INTO personas (
                nombre, apellido, cedula, rif, tipo, genero, fecha_nacimiento, 
                telefono_principal, correo_electronico, direccion, ciudad, estado, pais, estatus
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);
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

    return $stmt->execute($arrValues);
}

    // Método para eliminar lógicamente una persona
    public function deletePersona($idpersona) {
        $sql = "UPDATE personas SET estatus = 'INACTIVO' WHERE idpersona = ?";
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$idpersona]); 
    }

    // Método para actualizar una persona
    public function updatePersona() {
        $sql = "UPDATE personas SET 
                    nombre = ?, 
                    apellido = ?, 
                    cedula = ?, 
                    rif = ?, 
                    tipo = ?, 
                    genero = ?, 
                    fecha_nacimiento = ?, 
                    telefono_principal = ?, 
                    correo_electronico = ?, 
                    direccion = ?, 
                    ciudad = ?, 
                    estado = ?, 
                    pais = ?, 
                    estatus = ? 
                WHERE idpersona = ?";
        
        $stmt = $this->db->prepare($sql); 
        $arrValues = [
            $this->nombre, 
            $this->apellido, 
            $this->cedula, 
            $this->rif, 
            $this->tipo, 
            $this->genero, 
            $this->fecha_nacimiento, 
            $this->telefono_principal, 
            $this->correo_electronico, 
            $this->direccion, 
            $this->ciudad, 
            $this->estado, 
            $this->pais, 
            $this->estatus,
            $this->idpersona 
        ];
    
        return $stmt->execute($arrValues); 
    }

    // Método para obtener una persona por ID
   
    
}