<?php

require_once __DIR__ . '/../app/core/conexion.php';

try {
    $db = new datos();
    $conexion = $db->conecta();
    echo "Conexión exitosa a la base de datos.";
} catch (PDOException $e) {
    echo "Error en la conexión: " . $e->getMessage();
}