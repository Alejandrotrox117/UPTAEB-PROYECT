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


    // Método para eliminar lógicamente un cliente
    public function deleteCliente($clienteId)
    {
        $sql = "UPDATE cliente SET estatus = 'INACTIVO' WHERE idcliente = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$clienteId]);
    
    
        $sql = "UPDATE cliente SET estatus = 'INACTIVO' WHERE idcliente = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$this->getIdcliente()]);
    }

   public function updateCliente()
    {
        $sql = "UPDATE cliente SET 
                    cedula = ?, 
                    nombre = ?, 
                    apellido = ?, 
                    direccion = ?, 
                    telefono_principal = ?, 
                    estatus = ?, 
                    observaciones = ? 
                WHERE idcliente = ?";

        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $this->getCedula(),
            $this->getNombre(),
            $this->getApellido(),
            $this->getDireccion(),
            $this->getTelefonoPrincipal(),
            $this->getEstatus(),
            $this->getObservaciones(),
            $this->getIdcliente() // Usar el ID constante
        ];

        return $stmt->execute($arrValues);
    }

    public function getClienteById($idcliente)
    {
        $sql = "SELECT 
                    idcliente, 
                    cedula, 
                    nombre, 
                    apellido, 
                    direccion, 
                    telefono_principal, 
                    estatus, 
                    observaciones 
                FROM cliente 
                WHERE idcliente = ?";
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idcliente]);
    
        // Obtener el resultado
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            // Mapear los datos a las propiedades de la clase
            $this->setIdcliente($result['idcliente']);
            $this->setCedula($result['cedula']);
            $this->setNombre($result['nombre']);
            $this->setApellido($result['apellido']);
            $this->setDireccion($result['direccion']);
            $this->setTelefonoPrincipal($result['telefono_principal']);
           
            $this->setEstatus($result['estatus']);
            $this->setObservaciones($result['observaciones']);
    
            return $result; // Retornar los datos como un array asociativo
        }
    
        return null; // Retornar null si no se encuentra el cliente
    }

}
