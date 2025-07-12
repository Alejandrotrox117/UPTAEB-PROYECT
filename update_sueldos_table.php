<?php
// Script temporal para actualizar la tabla sueldos
$conexion = new mysqli('localhost', 'root', '', 'bd_pda');

if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}

// Verificar si la columna estatus ya existe
$result = $conexion->query("SHOW COLUMNS FROM sueldos LIKE 'estatus'");
if ($result->num_rows == 0) {
    // Agregar la columna estatus si no existe
    $sql = "ALTER TABLE sueldos ADD COLUMN estatus ENUM('POR_PAGAR', 'INACTIVO', 'PAGO_FRACCIONADO', 'PAGADO') NOT NULL DEFAULT 'POR_PAGAR' AFTER observacion";
    if ($conexion->query($sql)) {
        echo "Columna estatus agregada exitosamente." . PHP_EOL;
    } else {
        echo "Error al agregar columna estatus: " . $conexion->error . PHP_EOL;
    }
} else {
    echo "La columna estatus ya existe." . PHP_EOL;
}

$conexion->close();
echo "Script completado." . PHP_EOL;
?>
