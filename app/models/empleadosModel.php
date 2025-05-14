<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class empleadosModel extends Mysql
{
    private $db;
    private $conexion;

    // Atributos de la tabla `empleado`
    private $idempleado;
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
    private $fecha_creacion;
    private $fecha_modificacion;
    private $fecha_inicio;
    private $fecha_fin;
    private $puesto;
    private $salario;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdEmpleado()
    {
        return $this->idempleado;
    }

    public function setIdEmpleado($idempleado)
    {
        $this->idempleado = $idempleado;
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

    public function getIdentificacion()
    {
        return $this->identificacion;
    }

    public function setIdentificacion($identificacion)
    {
        $this->identificacion = $identificacion;
    }

    public function getFechaNacimiento()
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento($fecha_nacimiento)
    {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function getDireccion()
    {
        return $this->direccion;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function getCorreoElectronico()
    {
        return $this->correo_electronico;
    }

    public function setCorreoElectronico($correo_electronico)
    {
        $this->correo_electronico = $correo_electronico;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    public function getTelefonoPrincipal()
    {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal($telefono_principal)
    {
        $this->telefono_principal = $telefono_principal;
    }

    public function getObservaciones()
    {
        return $this->observaciones;
    }

    public function setObservaciones($observaciones)
    {
        $this->observaciones = $observaciones;
    }

    public function getGenero()
    {
        return $this->genero;
    }

    public function setGenero($genero)
    {
        $this->genero = $genero;
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

    public function getFechaInicio()
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio($fecha_inicio)
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getFechaFin()
    {
        return $this->fecha_fin;
    }

    public function setFechaFin($fecha_fin)
    {
        $this->fecha_fin = $fecha_fin;
    }

    public function getPuesto()
    {
        return $this->puesto;
    }

    public function setPuesto($puesto)
    {
        $this->puesto = $puesto;
    }

    public function getSalario()
    {
        return $this->salario;
    }

    public function setSalario($salario)
    {
        $this->salario = $salario;
    }

    // Método para seleccionar todos los empleados activos
    public function SelectAllEmpleados()
    {
        $sql = "SELECT 
            idempleado, 
            nombre, 
            apellido, 
            identificacion, 
            fecha_nacimiento, 
            direccion, 
            correo_electronico, 
            estatus, 
            telefono_principal, 
            observaciones, 
            genero, 
            fecha_inicio, 
            fecha_fin, 
            puesto, 
            salario 
        FROM empleado 
        WHERE estatus = 'ACTIVO'";
        return $this->searchAll($sql);
    }

    // Método para insertar un nuevo empleado
    public function insertEmpleado($data)
    {
        $sql = "INSERT INTO empleado (
                    nombre, 
                    apellido, 
                    identificacion, 
                    fecha_nacimiento, 
                    direccion, 
                    correo_electronico, 
                    estatus, 
                    telefono_principal, 
                    observaciones, 
                    genero, 
                    fecha_inicio, 
                    fecha_fin, 
                    puesto, 
                    salario
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
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
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['puesto'],
            $data['salario']
        ];

        return $stmt->execute($arrValues);
    }

    // Método para eliminar lógicamente un empleado
    public function deleteEmpleado($idempleado)
    {
        $sql = "UPDATE empleado SET estatus = 'INACTIVO' WHERE idempleado = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idempleado]);
    }

    // Método para actualizar un empleado
    public function updateEmpleado($data)
    {
        $sql = "UPDATE empleado SET 
                    nombre = ?, 
                    apellido = ?, 
                    identificacion = ?, 
                    fecha_nacimiento = ?, 
                    direccion = ?, 
                    correo_electronico = ?, 
                    estatus = ?, 
                    telefono_principal = ?, 
                    observaciones = ?, 
                    genero = ?, 
                    fecha_modificacion = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?, 
                    puesto = ?, 
                    salario = ? 
                WHERE idempleado = ?";

        $stmt = $this->db->prepare($sql);
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
            $data['fecha_modificacion'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['puesto'],
            $data['salario'],
            $data['idempleado']
        ];

        return $stmt->execute($arrValues);
    }

    // Método para obtener un empleado por ID
    public function getEmpleadoById($idempleado)
    {
        $sql = "SELECT 
                    idempleado, 
                    nombre, 
                    apellido, 
                    identificacion, 
                    fecha_nacimiento, 
                    direccion, 
                    correo_electronico, 
                    estatus, 
                    telefono_principal, 
                    observaciones, 
                    genero,  
                    fecha_modificacion, 
                    fecha_inicio, 
                    fecha_fin, 
                    puesto, 
                    salario 
                FROM empleado 
                WHERE idempleado = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idempleado]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
