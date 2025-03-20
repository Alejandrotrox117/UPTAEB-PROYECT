<?php

include 'header.php';

// Instancia del controlador y llama al método
$controller = new Home();
$controller->home('Parámetros de prueba');

include 'footer.php';