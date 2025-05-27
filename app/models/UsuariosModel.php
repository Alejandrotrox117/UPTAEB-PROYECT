<?php
require_once "app/core/conexion.php"; // Asegúrate que la ruta sea correcta
require_once "app/core/mysql.php"; // Asegúrate que la ruta sea correcta

class UsuariosModel extends Mysql
{
    private $idusuario;
    private $strNombreUsuario;
    private $strApellido;
    private $connect;

    private $strEmail;
    private $strToken;
    private $strPassword;
    private $intRol;
    private $intStatus;

    private $personaId;
    private $nombre;
    private $telefono;
    private $direccion;

    public function setTelefono(int $telefono) {
        $this->telefono = $telefono;
    }


    // Getters
    public function getIdUsuario(): int
    {
        return isset($this->idusuario) ? intval($this->idusuario) : 0;
    }

    public function get_connect()
    {
        return $this->connect;
    }
    public function getNombreUsuario(): string
    {
        return $this->strNombreUsuario;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->strApellido;
    }

    public function getEmail(): string
    {
        return $this->strEmail;
    }

    public function getToken(): string
    {
        return $this->strToken;
    }

    public function getPassword(): string
    {
        return $this->strPassword;
    }

    public function getRol(): int
    {
        return $this->intRol;
    }

    public function getStatus(): int
    {
        return $this->intStatus;
    }

    public function getIdPersona(): string
    {
        return $this->personaId;
    }

    public function getDireccion(): string
    {
        return $this->direccion;
    }

    public function getTelefono() {
        return $this->telefono;
    }

    

    // Setters
    public function setIdUsuario(int $idusuario): void
    {
        $this->idusuario = $idusuario;
    }

    public function setNombreUsuario(string $nombreUsuario): void
    {
        $this->strNombreUsuario = $nombreUsuario;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setApellido(string $apellido): void
    {
        $this->strApellido = $apellido;
    }

    public function setEmail(string $email): void
    {
        $this->strEmail = $email;
    }

    public function setToken(string $token): void
    {
        $this->strToken = $token;
    }

    public function setPassword($password)
    {
        $this->strPassword = $password;
    }

    public function setRol(int $rol)
    {
        $this->intRol = $rol;
    }

    public function setStatus(int $estado): void
    {
        $this->intStatus = $estado;
    }
    public function set_connect($connect)
    {
        $this->connect = $connect;
    }
    public function setIdPersona($personaId)
    {
        $this->personaId = $personaId;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function __construct()
    {
        parent::__construct();
        $this->set_connect((new Conexion())->get_conectSeguridad());

    }

    public function insertUsuario(
        int $rolId,
        string $usuario,
        string $password,
        int $estado,
        string $email,
        string $idPersona
    ) {
        $this->setNombreUsuario($usuario);
        $this->setEmail($email);
        $this->setPassword($password);
        $this->setStatus($estado);
        $this->setRol($rolId);
        $this->setIdPersona($idPersona);
    
        // Verificar si el email o nombre de usuario ya existen
        $sql = "SELECT * FROM usuario WHERE correo = '{$this->getEmail()}' OR usuario = '{$this->getNombreUsuario()}'";
        $request = $this->searchAllSeguridad($sql);
    
        if (empty($request)) {
            $query_insert = "INSERT INTO usuario (rolId, usuario, clave, estado, correo, personaId) VALUES (?, ?, ?, ?, ?, ?)";
            $arrData = array(
                $this->getRol(),
                $this->getNombreUsuario(),
                $this->getPassword(),
                $this->getStatus(),
                $this->getEmail(),
                $this->getIdPersona()

            );
            $request_insert = $this->insertSeguridad($query_insert, $arrData);
            $response = $request_insert;
        } 
        else {
            $response = false;
        }
    
        return $response;
    }

    public function selectPersonaByCedula($cedula) {
        $this->setIdPersona($cedula);
        $sql = "SELECT * FROM persona WHERE personaId = '{$this->getIdPersona()}'";
        $request = $this->search($sql);
        return array($request);
    }

    public function insertPersona($cedula, $nombres, $apellidos, $telefono, $email, $direccion) {
        $this->setIdPersona($cedula);
        $this->setNombre($nombres);
        $this->setApellido($apellidos);
        $this->setTelefono($telefono);
        $this->setEmail($email);
        $this->setDireccion($direccion);
        
        $sql = "INSERT INTO persona (personaId, nombres, apellidos, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)";
        $arrData = array( $this->getIdPersona(), $this->getNombre(), $this->getApellido(), $this->getTelefono(), $this->getEmail(), $this->getDireccion());
        return $this->insertPerson($sql, $arrData);
    }

    public function PutInfoPerson($personaId, 
                                    $txtnombres, 
                                    $txtnombrespersonas, 
                                    $txtapellidospersonas,
                                    $txtdireccionpersonas,
                                    int $txttelefonopersonas,
                                    $email,
                                    $txtpassword) {
                    // Asignar valores a las propiedades de la clase
                    $this->setIdPersona($personaId);
                    $this->setNombreUsuario($txtnombres);
                    $this->setNombre($txtnombrespersonas);
                    $this->setApellido($txtapellidospersonas);
                    $this->setDireccion($txtdireccionpersonas);
                    $this->setTelefono($txttelefonopersonas); 
                    $this->setEmail($email); 
                    $this->setPassword($txtpassword);

                    // Construir la consulta SQL
                    $sql = "UPDATE db_celtech_seguridad.usuario u
                            JOIN db_celtech.persona p ON u.personaId = p.personaId
                            SET u.usuario = ?,
                            p.nombres = ?,
                            p.apellidos = ?, 
                            p.direccion = ?,
                            p.telefono = ?,
                            p.email = ?,
                            u.correo = ?";

                    // Crear el array de parámetros
                    $arr = [
                    $this->getNombreUsuario(), 
                    $this->getNombre(), 
                    $this->getApellido(), 
                    $this->getDireccion(),
                    $this->getTelefono(),
                    $this->getEmail(),
                    $this->getEmail()
                    ];

                    // Agregar la contraseña si no está vacía
                    if (!empty($txtpassword)) {
                    $sql .= ", u.clave = ?";
                    $arr[] = $this->getPassword();
                    }

                    // Agregar la condición WHERE
                    $sql .= " WHERE u.personaId = ?";
                    $arr[] = $this->getIdPersona();

                    // Ejecutar la consulta
                    $request = $this->updateSeguridad($sql, $arr);
                    return $request;
}

    public function getIdUser($idusuario)
    {
        $this->setIdUsuario($idusuario);

        $sql = "SELECT * FROM usuario WHERE idusuario = '{$this->getIdUsuario()}'";
        $request = $this->searchAllSeguridad($sql);
        return $request;
    }
    public function selectUsuario(int $idusuario)
    {
        $this->setIdUsuario($idusuario);
        $sql = "SELECT u.idusuario, u.rolId, u.usuario, u.clave,u.token, u.correo, u.estado FROM usuario u WHERE u.idusuario = {$this->getIdUsuario()}";
        $request = $this->searchSeguridad($sql);
        return $request;
    }

    public function selectUsuarioPersona(int $idusuario)
    {
        $this->setIdUsuario($idusuario);
                $sql = "SELECT u.idusuario, u.rolId, u.usuario, u.clave, u.token, u.correo, u.estado, r.rolId, r.nombre, p.personaId, p.nombres, p.apellidos, p.telefono, p.direccion 
                FROM db_celtech_seguridad.usuario u
                JOIN db_celtech_seguridad.rol r ON u.rolId = r.rolId
                JOIN db_celtech.persona p ON u.personaId = p.personaId 
                WHERE u.idusuario = {$this->getIdUsuario()}";
        $request = $this->searchSeguridad($sql);
        return $request;
    }

    public function selectUsuarios()
    {
        $whereAdmin = "";
        if($_SESSION['idusuario'] != 1){
            $whereAdmin = " WHERE idusuario != 1";
        }
        $sql = "SELECT * FROM usuario" . $whereAdmin;
        $request = $this->searchAllSeguridad($sql);
        return $request;
    }

    public function actualizarUsuario(
        int $idusuario,
        int $rolId,
        string $usuario,
        string $password,
        int $estado,
        string $email,
        string $idPersona
    ) {
        $this->setIdUsuario($idusuario);
        $this->setEmail($email);
        $this->setPassword($password);
        $this->setRol($rolId);
        $this->setStatus($estado);
        $this->setNombreUsuario($usuario);
        $this->setIdPersona($idPersona);
        $sql = "SELECT * FROM usuario WHERE (correo = '{$this->getEmail()}' AND idusuario != {$this->getIdUsuario()})";
        $request = $this->searchAllSeguridad($sql);
        if (!empty($request)) {
            return "exist";
        }
        try {
            if (empty($request)) {
                if ($this->getPassword() != "") {
                    $sql = "UPDATE usuario SET idusuario=?,rolId=?,usuario=?, clave=?, estado=?,correo=?,personaId=? WHERE idusuario = {$this->getIdUsuario()}";
                    $arrData = array(
                        $this->getIdUsuario(),
                        $this->getRol(),
                        $this->getNombreUsuario(),
                        $this->getPassword(),
                        $this->getStatus(),
                        $this->getEmail(),
                        $this->getIdPersona()
                    );
                } else {
                    $sql = "UPDATE usuario SET idusuario=?,rolId=?,usuario=?,estado=?,correo=?,personaId=? WHERE idusuario = {$this->getIdUsuario()}";
                    $arrData = array(
                        $this->getIdUsuario(),
                        $this->getRol(),
                        $this->getNombreUsuario(),
                        $this->getStatus(),
                        $this->getEmail(),
                        $this->getIdPersona()  
                    );
                }
                $request = $this->updateSeguridad($sql, $arrData);
            }
        } catch (Exception $e) {
            // Manejo de errores
            $request = "error";
        }
        return $request ?: false;

    }

    public function deleteUsuario(int $idusuario)
    {
        $this->setIdUsuario($idusuario);
        $query = "SELECT * FROM usuario WHERE idusuario = {$this->getIdUsuario()}";
        $request = $this->searchAllSeguridad($query);
    
        if (!empty($request)) {
            $sql = "UPDATE usuario SET estado = 0 WHERE idusuario = {$this->getIdUsuario()}";
            $request = $this->deleteSeguridad($sql);
    
            if ($request) {
                $arrResponse = array("status" => true, "msg" => "Usuario desactivado correctamente.");
            } else {
                $arrResponse = array("status" => false, "msg" => "No se pudo eliminar el usuario.");
            }
        } else {
            $arrResponse = array("status" => false, "msg" => "Usuario no encontrado.");
        }
    
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        exit();
    }
}