<?php
/**
 * Script para crear las bases de datos de prueba
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Configuración de conexión (usuario root por defecto en XAMPP)
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    // Conectar al servidor MySQL sin seleccionar base de datos
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado a MySQL.\n";

    // Nombres de las bases de datos de prueba
    $dbGeneralTest = 'bd_pda_test';
    $dbSeguridadTest = 'bd_pda_seguridad_test';

    // Crear bases de datos si no existen
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbGeneralTest` CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "Base de datos '$dbGeneralTest' creada o verificada.\n";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbSeguridadTest` CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "Base de datos '$dbSeguridadTest' creada o verificada.\n";

    // Opcional: Crear tablas aqui o usando migraciones si existen.
    // Por ahora solo creamos las bases de datos vacías.

} catch (PDOException $e) {
    echo "Error al conectar o crear bases de datos: " . $e->getMessage() . "\n";
    echo "Asegurate de que MySQL esté corriendo y las credenciales sean correctas.\n";
    exit(1);
}
