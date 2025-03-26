<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio</title>
</head>
<body>
<?php
include './public/header.php';
// Instancia del controlador y llama al método
$controller = new Home();
$controller->home('Parámetros de prueba');

include './public/footer.php'; 
?>
</body>
</html>
