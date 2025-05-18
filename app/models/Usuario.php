<?php
require_once("app/core/conexion.php"); 

class Usuario
{
    private $db;
    private $conexion;

    private $username;
    private $password;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->connect();
    }

    // SETTERS
    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    // GETTERS
    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function PostUser()
    {
        $conn = $this->db;
        
        $sql = "SELECT * FROM usuarios WHERE correo = :correo AND status1 = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':correo', $this->username, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return 'usuario_no_existe';
            }

            // Si no se ha proporcionado contraseña, solo devolvemos el usuario
            if ($this->password === '' || $this->password === null) {
                if (isset($user['rol'])) {
                    $user['rolName'] = $user['rol'];
                }
                return $user;
            }

            // Verificamos la contraseña
            if (!password_verify($this->password, $user['clave'])) {
                return 'contraseña_incorrecta';
            }

            // Usuario autenticado correctamente
            if (isset($user['rol'])) {
                $user['rolName'] = $user['rol'];
            }

            return $user;

        } catch (PDOException $e) {
            return 'error_en_la_consulta';
        }
    }
}
?>
