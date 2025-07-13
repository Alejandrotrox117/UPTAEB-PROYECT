<?php
require_once 'app/core/conexion.php';

try {
    $conexion = new Conexion();
    $conexion->connect();
    $pdo = $conexion->get_conectSeguridad();
    
    $sql = file_get_contents('config/backups/init_backups_module.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $pdo->exec($statement);
        }
    }
    
    echo "Módulo de backups inicializado correctamente en la base de datos\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
