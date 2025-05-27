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
        //Buscar el rol del usuario
        $sql = "SELECT u.idusuario,u.usuario,u.clave,u.estatus,u.correo, r.idrol,r.nombre
        FROM usuario u INNER JOIN roles r ON u.idrol = r.idrol WHERE u.idusuario  = $this->idUser";
        $request = $this->searchSeguridad($sql);
        $_SESSION['userData'] = $request;
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
        $sql = "SELECT idusuario, usuario, correo FROM usuario WHERE correo = ? AND estado = 1";
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
        $this->setToken($token);
        $this->setEmailUser($email);
        $sql = "SELECT * FROM usuario WHERE correo = '{$this->getEmailUser()}' AND token = '{$this->getToken()}' 
        AND estado = 1";
        $request = $this->searchSeguridad($sql);
        return $request;
    }

    public function insertPassword(int $idUsuario, string $pass)
    {

        $this->setPass($pass);
        $this->setIdUser($idUsuario);

        $sql = "UPDATE usuario SET clave = '{$this->getPass()}', token = '' WHERE idusuario = '{$this->getIdUser()}'";
        //$arrData = $this->getPass(), "");
        $request = $this->updateOneSeguridad($sql);
        return $request;
    }
}
