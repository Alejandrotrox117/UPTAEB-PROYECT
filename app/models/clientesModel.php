<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class ClientesModel extends Mysql
{
    private $db;
    private $conexion;


    private $idcliente;
    private $nombre;
    private $apellido;
    private $cedula;
    private $telefono_principal;
    private $correo_electronico;
    private $direccion;
    private $estatus;
    private $observaciones;
    private $fecha_creacion;
    private $fecha_modificacion;
    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdcliente()
    {
        return $this->idcliente;
    }

    public function setIdcliente($idpersona)
    {
        $this->idcliente = $idpersona;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function getApellido()
    {
        return $this->apellido;
    }

    public function setApellido($apellido)
    {
        $this->apellido = $apellido;
    }

    public function getcedula()
    {
        return $this->cedula;
    }

    public function setCedula($cedula)
    {
        $this->cedula = $cedula;
    }





    public function getTelefonoPrincipal()
    {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal($telefono_principal)
    {
        $this->telefono_principal = $telefono_principal;
    }

    public function getCorreoElectronico()
    {
        return $this->correo_electronico;
    }

    public function setCorreoElectronico($correo_electronico)
    {
        $this->correo_electronico = $correo_electronico;
    }

    public function getDireccion()
    {
        return $this->direccion;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function getObservaciones()
    {
        return $this->observaciones;
    }
    public function setObservaciones($observaciones)
    {
        $this->observaciones = $observaciones;
    }
    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }
    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }
    public function getFechaModificacion()
    {
        return $this->fecha_modificacion;
    }
    public function setFechaModificacion($fecha_modificacion)
    {
        $this->fecha_modificacion = $fecha_modificacion;
    }


    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    // Método para seleccionar todas las personas activas
    public function SelectAllclientes()
    {
        $sql = "SELECT 
                    idcliente, 
                    nombre, 
                    apellido, 
                    cedula,
                    direccion, 
                    correo_electronico,
                    estatus,
                    telefono_principal,
                    observaciones,
                    fecha_creacion,
                    fecha_modifcacion
                FROM cliente 
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
    public function deletePersona($idpersona)
    {
        $sql = "UPDATE personas SET estatus = 'INACTIVO' WHERE idpersona = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idpersona]);
    }

    // Método para actualizar una persona
    public function updatePersona()
    {
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
           
            $this->telefono_principal,
            $this->correo_electronico,
            $this->direccion,
           
            $this->estatus,
            $this->idcliente
        ];

        return $stmt->execute($arrValues);
    }

    // Método para obtener una persona por ID


}
