<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;

class LoginModel
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
    private $objLoginModel;

    public function __construct()
    {
        // Constructor vacío
    }

    private function getInstanciaModel()
    {
        if ($this->objLoginModel == null) {
            $this->objLoginModel = new LoginModel();
        }
        return $this->objLoginModel;
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

    private function ejecutarValidacionTokenOnly(string $token)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            // Verificar token válido y no expirado (solo por token)
            $this->setQuery("SELECT * FROM usuario WHERE token = ? AND token_exp > NOW() AND estatus = 'activo'");
            $this->setArray([$token]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $resultado = $this->getResult();
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarValidacionTokenOnly - Error: " . $e->getMessage());
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

    private function ejecutarActualizacionPasswordRecuperacion(int $idUsuario, string $pass)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("UPDATE usuario SET clave = ?, token = NULL, token_exp = NULL WHERE idusuario = ?");
            $this->setArray([$pass, $idUsuario]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarActualizacionPasswordRecuperacion - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    private function ejecutarEliminacionToken(string $token)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("UPDATE usuario SET token = NULL, token_exp = NULL WHERE token = ?");
            $this->setArray([$token]);

            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $resultado = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("LoginModel::ejecutarEliminacionToken - Error: " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // Métodos públicos que usan las funciones privadas
    public function login(string $email, string $password)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setEmailUser($email);
        $objLoginModel->setPass($password);
        return $objLoginModel->ejecutarAutenticacion($objLoginModel->getEmailUser(), $objLoginModel->getPass());
    }

    public function sessionLogin(int $id)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setIdUser($id);
        return $objLoginModel->ejecutarBusquedaSesion($objLoginModel->getIdUser());
    }



    public function getUsuarioEmail(string $email)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setEmailUser($email);
        return $objLoginModel->ejecutarBusquedaPorEmail($objLoginModel->getEmailUser());
    }

    public function setTokenUser(int $idUsuario, string $token)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setIdUser($idUsuario);
        $objLoginModel->setToken($token);
        return $objLoginModel->ejecutarActualizacionToken($objLoginModel->getIdUser(), $objLoginModel->getToken());
    }

    public function getTokenUser(string $email, string $token)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setEmailUser($email);
        $objLoginModel->setToken($token);
        return $objLoginModel->ejecutarValidacionToken($objLoginModel->getEmailUser(), $objLoginModel->getToken());
    }

    public function getTokenUserByToken(string $token)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setToken($token);
        return $objLoginModel->ejecutarValidacionTokenOnly($objLoginModel->getToken());
    }

    public function insertPassword(int $idUsuario, string $pass)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setIdUser($idUsuario);
        $objLoginModel->setPass($pass);
        return $objLoginModel->ejecutarActualizacionPassword($objLoginModel->getIdUser(), $objLoginModel->getPass());
    }

    public function updatePassword($userId, $passwordHash)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setIdUser($userId);
        $objLoginModel->setPass($passwordHash);
        return $objLoginModel->ejecutarActualizacionPasswordRecuperacion($objLoginModel->getIdUser(), $objLoginModel->getPass());
    }

    public function deleteToken($token)
    {
        $objLoginModel = $this->getInstanciaModel();
        $objLoginModel->setToken($token);
        return $objLoginModel->ejecutarEliminacionToken($objLoginModel->getToken());
    }
}
