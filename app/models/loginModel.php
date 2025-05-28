<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class LoginModel extends mysql
{
    private $emailUser;
    private $pass;
    private $idUser;
    private $token;
    private $personaId;

    public function setEmailUser($emailUser)
    {
        $this->emailUser = $emailUser;
    }

    public function getEmailUser()
    {
        return $this->emailUser;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setIdPersona($personaId)
    {
        $this->personaId = $personaId;
    }

    public function getIdPersona(): string
    {
        return $this->personaId;
    }

    public function getIdUser()
    {
        return $this->idUser;
    }

    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function login(string $email, string $password)
    {
        $sql = "SELECT idusuario, estatus, correo FROM usuario
                WHERE correo = ? AND clave = ? AND estatus = 'activo'";
        $arrData = array($email, $password);
        return $this->searchSeguridadParams($sql, $arrData);
    }

    public function sessionLogin(int $id)
    {
        $this->idUser = $id;
        // Buscar información completa del usuario
        $sql = "SELECT u.idusuario, u.usuario, u.estatus, u.correo, u.idrol,
                       r.nombre as rol_nombre, r.descripcion as rol_descripcion
                FROM usuario u 
                INNER JOIN roles r ON u.idrol = r.idrol 
                WHERE u.idusuario = ? AND u.estatus = 'activo'";
        
        $request = $this->searchSeguridadParams($sql, [$this->idUser]);
        return $request;
    }

    public function getInfoPerson($personaId)
    {
        $this->setIdPersona($personaId);
        $sql = "SELECT u.*, p.nombres, p.apellidos, p.telefono, p.email, p.direccion, p.estado 
                FROM db_celtech_seguridad.usuario u 
                JOIN db_celtech.persona p ON u.personaId = p.personaId 
                WHERE u.personaId = '{$this->getIdPersona()}'";
        $request = $this->searchAllSeguridad($sql);
        $_SESSION['personaData'] = $request;
        return $request;
    }

    public function getUsuarioEmail(string $email)
    {
        $sql = "SELECT idusuario, usuario, correo FROM usuario WHERE correo = ? AND estatus = 'activo'";
        $arrData = array($email);
        return $this->searchSeguridadParams($sql, $arrData);
    }

    public function setTokenUser(int $idUsuario, string $token)
    {
        $sql = "UPDATE usuario SET token = ? WHERE idusuario = ?";
        $arrData = array($token, $idUsuario);
        return $this->updateSeguridad($sql, $arrData);
    }

    public function getTokenUser(string $email, string $token)
    {
        $sql = "SELECT * FROM usuario WHERE correo = ? AND token = ? AND estatus = 'activo'";
        $arrData = array($email, $token);
        return $this->searchSeguridadParams($sql, $arrData);
    }

    public function insertPassword(int $idUsuario, string $pass)
    {
        $sql = "UPDATE usuario SET clave = ?, token = '' WHERE idusuario = ?";
        $arrData = array($pass, $idUsuario);
        return $this->updateSeguridad($sql, $arrData);
    }
}
?>
