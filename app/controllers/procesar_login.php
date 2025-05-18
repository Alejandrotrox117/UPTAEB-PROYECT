<?php
require "../models/Usuario.php";
require "../models/bitacoraModel.php";
require "../core/conexion.php";
?>

<?php
if (isset($_POST['ingresar'])) {
    $username = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "<script>alert('Usuario o contraseña vacío');</script>";
        exit;
    }

    // Validamos que sea un correo válido y que el password tenga letras, números y símbolos
    if (
        filter_var($username, FILTER_VALIDATE_EMAIL) &&
        preg_match('/[a-zA-Z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[^a-zA-Z0-9]/', $password)
    ) {

        // Usamos la nueva clase con getters y setters
        $Model = new Usuario(); // Asegúrate que el nombre coincide con la clase (con mayúscula)
        $Model->setUsername($username);
        $Model->setPassword($password);
        $user = $Model->PostUser();

        // Verificamos el tipo de error devuelto
        if ($user === 'usuario_no_existe') {
            echo "<script>alert('El usuario no existe'); window.location = '../../index.php';</script>";
            exit;
        }

        if ($user === 'contraseña_incorrecta') {
            echo "<script>alert('Contraseña incorrecta'); window.location = '../../index.php';</script>";
            exit;
        }

        if ($user === 'error_en_la_consulta') {
            echo "<script>alert('Hubo un error en la consulta'); window.location = '../../index.php';</script>";
            exit;
        }

        session_start();
        $_SESSION['user'] = $user;

        if (isset($_SESSION['user'])) {
            // Obtenemos el ID del usuario logueado
            $idUsuario = $_SESSION['user']['idusuario']; // Ajusta según la estructura real

            // Registrar en bitácora
            require_once '../models/bitacoraModel.php';
            $bitacora = new bitacoraModel();
            $bitacora->setTabla("usuario");
            $bitacora->setAccion("iniciar sesión");
            $bitacora->setIdusuario($idUsuario);
            $bitacora->insertar(); // Guardamos en la base de datos

            // Redirigir al home
            header('Location: ../views/home/home.php');
            exit;
        }

    } else {
        echo "<script>alert('Lo siento, ha colocado un dato inválido');</script>";
        echo "<script>window.location = '../../index.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Ups, ha ocurrido un Error');</script>";
    echo "<script>window.location = '../../index.php';</script>";
    exit;
}
?>