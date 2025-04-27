<?php
require_once('../core/conexion.php');

class usuario
{
    private $usuario;

    public function __construct()
    {
        $this->usuario = new Conexion();
    }

    public function Postuser($username, $password = '')
{
    $conn = $this->usuario->connect();
    
    $sql = "SELECT * FROM usuarios WHERE correo = '$username' AND status1=1";
    if ($password === '') {
        $sql = "SELECT * FROM usuarios WHERE correo = '$username' AND status1=1"; // Eliminé el OR innecesario
    }

    $results = $conn->query($sql);
    if ($results == false) {
        $conn->close();
        return 'error_en_la_consulta'; // Se puede capturar el error de la consulta
    }

    $user = $results->fetch_assoc();
    if (!$user) {
        $conn->close();
        return 'usuario_no_existe'; // El usuario no existe
    }

    // Si el password viene vacío, solo devolvemos el usuario
    if ($password === '') {
        if (isset($user['rol'])) {
            $user['rolName'] = $user['rol'];
        }
        $conn->close();
        return $user;
    }

    // Verificamos la contraseña
    if (!password_verify($password, $user['clave'])) {
        $conn->close();
        return 'contraseña_incorrecta'; // Contraseña incorrecta
    }

    // Si todo está bien, devolvemos el usuario
    if (isset($user['rol'])) {
        $user['rolName'] = $user['rol'];
    }
    $conn->close();
    return $user;
}

}
?>
