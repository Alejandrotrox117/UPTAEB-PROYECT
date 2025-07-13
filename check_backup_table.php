<?php
require_once 'app/core/conexion.php';

try {
    $conn = new Conexion();
    $conn->connect();
    $pdo = $conn->get_conectSeguridad();
    
    // Verificar si la tabla existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'historial_backups'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "La tabla historial_backups ya existe.\n";
    } else {
        echo "Creando tabla historial_backups...\n";
        
        $sql = "
        CREATE TABLE historial_backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_archivo VARCHAR(255) NOT NULL,
            tipo_backup ENUM('COMPLETO', 'TABLA') NOT NULL,
            tamaño_archivo BIGINT NOT NULL,
            fecha_creacion DATETIME NOT NULL,
            estatus ENUM('activo', 'eliminado') NOT NULL DEFAULT 'activo',
            INDEX idx_fecha_creacion (fecha_creacion),
            INDEX idx_estatus (estatus)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "Tabla historial_backups creada exitosamente.\n";
    }
    
    // Verificar estructura actual
    echo "\nEstructura de la tabla:\n";
    $result = $pdo->query("DESCRIBE historial_backups");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
