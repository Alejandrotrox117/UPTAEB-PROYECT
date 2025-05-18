<?php

class Usuario
{
    private $usuario;
    private $username;
    private $password;

    public function __construct()
    {
        $this->usuario = new Conexion();
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
        $conn = $this->usuario->connect();
        
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



    public function usuario()
    {
        $conn = $this->usuario->connect();
    }
}
?>
