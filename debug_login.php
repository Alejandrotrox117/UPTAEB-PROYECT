<?php
// Debug del problema del login
echo "<h1>Debug del Sistema</h1>";

echo "<h2>1. Variables de entorno:</h2>";
if (file_exists('.env')) {
    echo "✅ Archivo .env existe<br>";
} else {
    echo "❌ Archivo .env NO existe<br>";
}

echo "<h2>2. Cargando configuración:</h2>";
try {
    require_once "config/config.php";
    echo "✅ config.php cargado exitosamente<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO') . "<br>";
    echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'NO DEFINIDO') . "<br>";
} catch (Exception $e) {
    echo "❌ Error cargando config.php: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Cargando helpers:</h2>";
try {
    require_once "helpers/helpers.php";
    echo "✅ helpers.php cargado exitosamente<br>";
    echo "base_url(): " . base_url() . "<br>";
} catch (Exception $e) {
    echo "❌ Error cargando helpers.php: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Cargando core:</h2>";
try {
    require_once "app/core/Controllers.php";
    echo "✅ Controllers.php cargado exitosamente<br>";
} catch (Exception $e) {
    echo "❌ Error cargando Controllers.php: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Verificando controlador de login:</h2>";
$loginFile = "app/controllers/Login.php";
if (file_exists($loginFile)) {
    echo "✅ Login.php existe<br>";
    try {
        require_once $loginFile;
        echo "✅ Login.php cargado exitosamente<br>";
        $login = new Login();
        echo "✅ Clase Login instanciada exitosamente<br>";
    } catch (Exception $e) {
        echo "❌ Error con Login.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Login.php NO existe<br>";
}

echo "<h2>6. Variables GET:</h2>";
var_dump($_GET);

echo "<h2>7. Variables de sesión:</h2>";
session_start();
var_dump($_SESSION);
?>