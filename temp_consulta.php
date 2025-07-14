<?php
require_once 'app/core/conexion.php';

try {
    $conn = new Conexion();
    $conn->connect();
    $db = $conn->get_conectGeneral();
    
    $stmt = $db->query('SELECT * FROM categoria WHERE estatus = "ACTIVO"');
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Categorías disponibles:\n";
    foreach($categorias as $cat) {
        echo "- ID: " . $cat['idcategoria'] . " | Nombre: " . $cat['nombre'] . "\n";
    }
    
    $conn->disconnect();
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
