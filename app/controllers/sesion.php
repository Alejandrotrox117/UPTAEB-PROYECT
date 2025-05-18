<?php
session_start();

// Solo permitir acceso si hay sesión activa
if (!isset($_SESSION['user'])) {
    // Redirige a la raíz de tu proyecto
    header("Location: /project/"); // Ajusta esta ruta si tu carpeta de proyecto tiene un nombre diferente
    exit();
}
?>
