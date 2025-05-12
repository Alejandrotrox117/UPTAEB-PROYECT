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


// Método para seleccionar todos los clientes activos
public function SelectAllclientes()
{
    $sql = "SELECT 
                idcliente, 
                cedula,
                nombre, 
                apellido, 
                direccion, 
                estatus,
                telefono_principal,
                observaciones
            FROM cliente 
            WHERE estatus = 'ACTIVO'";

    $result = $this->searchAll($sql);

    // Mapear los resultados a las propiedades de la clase
    $clientes = [];
    foreach ($result as $row) {
        $cliente = new self();
        $cliente->setIdcliente($row['idcliente']);
        $cliente->setCedula($row['cedula']);
        $cliente->setNombre($row['nombre']);
        $cliente->setApellido($row['apellido']);
        $cliente->setDireccion($row['direccion']);
        $cliente->setEstatus($row['estatus']);
        $cliente->setTelefonoPrincipal($row['telefono_principal']);
        $cliente->setObservaciones($row['observaciones']);
        $clientes[] = $cliente;
    }

    return $clientes;
}
   


// Método para insertar un nuevo cliente
public function insertCliente()
{
    $sql = "INSERT INTO cliente (
                cedula,
                nombre, 
                apellido, 
                 direccion, 
                telefono_principal, 
               estatus, 
              observaciones
               
               
                
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);
    $arrValues = [
        $this->getCedula(),
        $this->getNombre(),
        $this->getApellido(),
        $this->getTelefonoPrincipal(),
    
        $this->getDireccion(),
        $this->getEstatus(),
        $this->getObservaciones()
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
