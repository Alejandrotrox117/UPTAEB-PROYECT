<?php

// Cargar el loader de variables de entorno
require_once __DIR__ . '/../app/core/EnvLoader.php';

// Configuración de Base de Datos
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_NAME_GENERAL', env('DB_NAME_GENERAL', 'bd_pda'));
define('DB_NAME_SEGURIDAD', env('DB_NAME_SEGURIDAD', 'bd_pda_seguridad'));

// Configuración de Email SMTP
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls'));
define('FROM_EMAIL', env('FROM_EMAIL', ''));
define('FROM_NAME', env('FROM_NAME', 'Sistema Recuperadora'));

// Configuración de Seguridad
define('JWT_SECRET', env('JWT_SECRET', 'default-jwt-secret-change-this'));
define('SESSION_SECRET', env('SESSION_SECRET', 'default-session-secret'));
define('ENCRYPTION_KEY', env('ENCRYPTION_KEY', '32-character-encryption-key-here'));

// Configuración de la Aplicación
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));
define('APP_URL', env('APP_URL', 'http://localhost/project'));
define('APP_NAME', env('APP_NAME', 'Sistema Recuperadora'));

// Configuración de Archivos
define('UPLOAD_MAX_SIZE', env('UPLOAD_MAX_SIZE', 10485760)); // 10MB por defecto
define('ALLOWED_EXTENSIONS', env('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx'));

// APIs Externas
define('BCV_API_URL', env('BCV_API_URL', 'https://api.bcv.org.ve'));
define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', ''));

// Configuración de Cache
define('CACHE_DRIVER', env('CACHE_DRIVER', 'file'));
define('CACHE_LIFETIME', env('CACHE_LIFETIME', 3600));

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Configuración de errores según el entorno
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

?>