<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class LoginModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $emailUser;
    private $pass;
    private $idUser;
    private $token;
    private $personaId;
    private $message;
    private $status;

    public function __construct()
    {
        parent::__construct();
    }

    // Getters y Setters principales
    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getArray()
    {
        return $this->array ?? [];
    }

    public function setArray(array $array)
    {
        $this->array = $array;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getEmailUser()
    {
        return $this->emailUser;
    }

    public function setEmailUser($emailUser)
    {
        $this->emailUser = $emailUser;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    public function getIdUser()
    {
        return $this->idUser;
    }

    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getIdPersona()
    {
        return $this->personaId;
    }

    public function setIdPersona($personaId)
    {
        $this->personaId = $personaId;
    }

    public function getMessage()
    {
        return $this->message ?? '';
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status ?? false;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

    // Métodos privados encapsulados
    private function ejecutarAutenticacion(string $email, string $password)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT idusuario, estatus, correo FROM usuario WHERE correo = ? AND clave = ? AND estatus = 'activo'");
            $this->setArray([$email, $password]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarAutenticacion - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarBusquedaSesion(int $id)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery(
                "SELECT u.idusuario, u.usuario, u.estatus, u.correo, u.idrol,
                        r.nombre as rol_nombre, r.descripcion as rol_descripcion
                 FROM usuario u 
                 INNER JOIN roles r ON u.idrol = r.idrol 
                 WHERE u.idusuario = ? AND u.estatus = 'activo'"
            );
            $this->setArray([$id]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarBusquedaSesion - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

       private function ejecutarBusquedaPorEmail(string $email)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT idusuario, usuario, correo, estatus FROM usuario WHERE correo = ?");
            $this->setArray([$email]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarBusquedaPorEmail - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarActualizacionToken(int $idUsuario, string $token)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Actualizar token con fecha de expiración (1 hora desde ahora)
            $this->setQuery("UPDATE usuario SET token = ?, token_exp = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE idusuario = ?");
            $this->setArray([$token, $idUsuario]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarActualizacionToken - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarValidacionToken(string $email, string $token)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Verificar token válido y no expirado
            $this->setQuery("SELECT * FROM usuario WHERE correo = ? AND token = ? AND token_exp > NOW() AND estatus = 'activo'");
            $this->setArray([$email, $token]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $resultado = $this->getResult();
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarValidacionToken - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarActualizacionPassword(int $idUsuario, string $pass)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("UPDATE usuario SET clave = ?, token = '' WHERE idusuario = ?");
            $this->setArray([$pass, $idUsuario]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarActualizacionPassword - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos que usan las funciones privadas
    public function login(string $email, string $password)
    {
        $this->setEmailUser($email);
        $this->setPass($password);
        return $this->ejecutarAutenticacion($this->getEmailUser(), $this->getPass());
    }

    public function sessionLogin(int $id)
    {
        $this->setIdUser($id);
        return $this->ejecutarBusquedaSesion($this->getIdUser());
    }

  

    public function getUsuarioEmail(string $email)
    {
        $this->setEmailUser($email);
        return $this->ejecutarBusquedaPorEmail($this->getEmailUser());
    }

    public function setTokenUser(int $idUsuario, string $token)
    {
        $this->setIdUser($idUsuario);
        $this->setToken($token);
        return $this->ejecutarActualizacionToken($this->getIdUser(), $this->getToken());
    }

    public function getTokenUser(string $email, string $token)
    {
        $this->setEmailUser($email);
        $this->setToken($token);
        return $this->ejecutarValidacionToken($this->getEmailUser(), $this->getToken());
    }

    public function insertPassword(int $idUsuario, string $pass)
    {
        $this->setIdUser($idUsuario);
        $this->setPass($pass);
        return $this->ejecutarActualizacionPassword($this->getIdUser(), $this->getPass());
    }

    public function updatePassword($userId, $passwordHash)
    {
        $sql = "UPDATE usuarios SET password = ? WHERE idusuario = ?";
        $arrData = array($passwordHash, $userId);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function deleteToken($token)
    {
        $sql = "UPDATE usuarios SET token = NULL, token_exp = NULL WHERE token = ?";
        $arrData = array($token);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    
}
?>