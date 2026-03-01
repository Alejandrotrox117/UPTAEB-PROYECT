<?php
/**
 * Bootstrap de PHPUnit
 * Carga el autoloader de Composer y la configuración de la aplicación.
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración de la aplicación (define DB_HOST, DB_USERNAME, etc.)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}
