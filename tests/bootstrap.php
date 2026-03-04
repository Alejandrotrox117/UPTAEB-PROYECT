<?php
/**
 * Bootstrap de PHPUnit
 * Carga el autoloader de Composer y la configuración de la aplicación.
 *
 * IMPORTANTE: Las variables de entorno de prueba deben establecerse ANTES de
 * cargar vendor/autoload.php, porque EnvLoader.php está registrado en la
 * sección "files" de composer.json y se carga automáticamente junto con el
 * autoloader. Si el .env de producción se carga primero, los define() de
 * config.php quedan fijados con los valores de producción y no pueden cambiarse.
 */

// 1. Fijar env vars de prueba ANTES de que el autoloader cargue EnvLoader.php
$_ENV['APP_ENV']           = 'testing';
$_ENV['DB_HOST']           = '127.0.0.1';
$_ENV['DB_USERNAME']       = 'root';
$_ENV['DB_PASSWORD']       = '';
$_ENV['DB_NAME_GENERAL']   = 'bd_pda_test';
$_ENV['DB_NAME_SEGURIDAD'] = 'bd_pda_seguridad_test';

putenv('APP_ENV=testing');
putenv('DB_HOST=127.0.0.1');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');
putenv('DB_NAME_GENERAL=bd_pda_test');
putenv('DB_NAME_SEGURIDAD=bd_pda_seguridad_test');

// 2. Cargar autoloader (EnvLoader.php se carga aquí, pero ahora ve los valores
//    de $_ENV anteriores y NO sobrescribe con el .env de producción)
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Cargar configuración (define DB_HOST, DB_USERNAME, etc. con valores de prueba)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}
