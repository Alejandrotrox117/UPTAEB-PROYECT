<?php
// Script de depuración temporal
session_start();

echo "<h2>Diagnóstico de configuración</h2>";

// Cargar configuración
require_once "config/config.php";
require_once "helpers/helpers.php";

echo "<h3>Variables de entorno:</h3>";
echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'NO DEFINIDA') . "<br>";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'NO DEFINIDA') . "<br>";

echo "<h3>Función base_url():</h3>";
echo "base_url(): " . base_url() . "<br>";
echo "base_url('login'): " . base_url('login') . "<br>";

echo "<h3>Variables de sesión:</h3>";
echo "SESSION login: " . (isset($_SESSION['login']) ? ($_SESSION['login'] ? 'true' : 'false') : 'NO EXISTE') . "<br>";
echo "SESSION usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NO EXISTE') . "<br>";

echo "<h3>Variables del servidor:</h3>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "<br>";

echo "<h3>Archivo .env existe:</h3>";
echo file_exists('.env') ? "SÍ" : "NO";
?>
