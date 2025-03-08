<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\controllers\TestController;

include 'header.php';

// Instancia del controlador y llama al método
$controller = new TestController();
$controller->sayHello();

include 'footer.php';