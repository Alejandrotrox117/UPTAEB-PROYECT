<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\controllers\TestController;

// Instancia del controlador y llama al método
$controller = new TestController();
$controller->sayHello();