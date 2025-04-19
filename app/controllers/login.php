<?php

require_once "app/core/Controllers.php";

class Login extends Controllers {
    public function __construct() {
        parent::__construct();
        session_start();
    }

    // Método para mostrar el formulario de login
    public function index() {
        if (isset($_SESSION['usuario_id'])) {
            // Si el usuario ya está logueado, redirigir al módulo principal
            header("Location: " . BASE_URL . "/home");
            exit;
        }

        // Cargar la vista del formulario de login
        $data['page_title'] = "Iniciar Sesión";
        $this->views->getView($this, "login", $data);
    }

    // Método para procesar el inicio de sesión
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = $_POST['usuario'];
            $clave = $_POST['clave'];

            // Validar que los campos no estén vacíos
            if (empty($usuario) || empty($clave)) {
                echo "Por favor, completa todos los campos.";
                return;
            }

            // Consultar el usuario en la base de datos
            $user = $this->model->getUserByUsername($usuario);

            // Verificar si el usuario existe
            if (empty($user)) {
                echo "Usuario no encontrado.";
                return;
            }

            // Comparar la contraseña ingresada con la almacenada
            if (trim($user['clave']) === trim($clave)) {
                // Iniciar sesión
                session_start();
                $_SESSION['usuario_id'] = $user['idusuario'];
                $_SESSION['usuario_rol'] = $user['idrol'];
                $_SESSION['usuario'] = $user['usuario'];
               

                // Redirigir al módulo principal
                header("Location: " . BASE_URL . "/home");
                exit;
            } else {
                echo "Contraseña incorrecta.";
            }
        }
    }

    // Método para cerrar sesión
    public function logout() {
        session_start();
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit;
    }
}