<?php
require_once __DIR__ . '/../core/conexion.php';

class TestController
{
    public function sayHello()
    {
        echo "¡Hola desde el TestController! Composer está funcionando correctamente.<br>";

        $db = new \datos();
        $conexion = $db->conecta();
        if ($conexion) {
            echo "Conexión exitosa a la base de datos.";
        } else {
            echo "Error en la conexión a la base de datos.";
        }
    }
}