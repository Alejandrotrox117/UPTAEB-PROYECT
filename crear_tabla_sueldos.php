<?php
// Script temporal para crear la tabla de sueldos

try {
    // Conectar directamente con MySQLi usando valores por defecto
    $host = 'localhost';
    $dbname = 'bd_pda';
    $username = 'root';
    $password = '';

    $db = new mysqli($host, $username, $password, $dbname);
    
    if ($db->connect_error) {
        throw new Exception("Error de conexión: " . $db->connect_error);
    }

    // Establecer charset
    $db->set_charset("utf8mb4");

    echo "Conexión establecida exitosamente.\n";

    // SQL para crear la tabla sueldos
    $sql = "
    CREATE TABLE IF NOT EXISTS `sueldos` (
      `idsueldo` int(11) NOT NULL AUTO_INCREMENT,
      `idpersona` int(11) DEFAULT NULL,
      `idempleado` int(11) DEFAULT NULL,
      `monto` decimal(15,2) NOT NULL,
      `balance` decimal(15,2) NOT NULL,
      `observacion` text NOT NULL,
      `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
      `fecha_modificacion` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
      PRIMARY KEY (`idsueldo`),
      KEY `idpersona` (`idpersona`),
      KEY `idempleado` (`idempleado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    if ($db->query($sql)) {
        echo "Tabla 'sueldos' creada exitosamente.\n";
    } else {
        echo "Error creando tabla: " . $db->error . "\n";
    }

    // Verificar si las tablas de referencias existen antes de añadir FK
    $checkEmpleado = $db->query("SHOW TABLES LIKE 'empleado'");
    $checkPersonas = $db->query("SHOW TABLES LIKE 'personas'");

    // Añadir las restricciones de llaves foráneas si las tablas existen
    if ($checkEmpleado && $checkEmpleado->num_rows > 0) {
        $sqlFK1 = "ALTER TABLE `sueldos` ADD CONSTRAINT `sueldos_ibfk_1` FOREIGN KEY (`idempleado`) REFERENCES `empleado` (`idempleado`)";
        if ($db->query($sqlFK1)) {
            echo "Restricción de llave foránea para empleado añadida.\n";
        } else {
            echo "Error al añadir FK empleado (puede que ya exista): " . $db->error . "\n";
        }
    } else {
        echo "Tabla 'empleado' no existe, FK no añadida.\n";
    }

    if ($checkPersonas && $checkPersonas->num_rows > 0) {
        $sqlFK2 = "ALTER TABLE `sueldos` ADD CONSTRAINT `sueldos_ibfk_2` FOREIGN KEY (`idpersona`) REFERENCES `personas` (`idpersona`)";
        if ($db->query($sqlFK2)) {
            echo "Restricción de llave foránea para persona añadida.\n";
        } else {
            echo "Error al añadir FK persona (puede que ya exista): " . $db->error . "\n";
        }
    } else {
        echo "Tabla 'personas' no existe, FK no añadida.\n";
    }

    $db->close();
    echo "Proceso completado.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
