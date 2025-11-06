<?php

/**
 * Clase centralizada para manejar toda la configuración de la aplicación
 * Proporciona acceso seguro y organizado a todas las configuraciones
 */
class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Inicializa la configuración
     */
    private static function init()
    {
        if (self::$loaded) {
            return;
        }

        // Cargar configuración desde constants definidas en config.php
        self::$config = [
            'database' => [
                'host' => DB_HOST,
                'username' => DB_USERNAME,
                'password' => DB_PASSWORD,
                'database_general' => DB_NAME_GENERAL,
                'database_seguridad' => DB_NAME_SEGURIDAD,
            ],
            'email' => [
                'smtp_host' => SMTP_HOST,
                'smtp_port' => SMTP_PORT,
                'smtp_user' => SMTP_USER,
                'smtp_pass' => SMTP_PASS,
                'smtp_secure' => SMTP_SECURE,
                'from_email' => FROM_EMAIL,
                'from_name' => FROM_NAME,
            ],
            'security' => [
                'jwt_secret' => JWT_SECRET,
                'session_secret' => SESSION_SECRET,
                'encryption_key' => ENCRYPTION_KEY,
            ],
            'app' => [
                'env' => APP_ENV,
                'debug' => APP_DEBUG,
                'url' => APP_URL,
                'name' => APP_NAME,
            ],
            'files' => [
                'upload_max_size' => UPLOAD_MAX_SIZE,
                'allowed_extensions' => explode(',', ALLOWED_EXTENSIONS),
            ],
            'api' => [
                'bcv_url' => BCV_API_URL,
                'google_maps_key' => GOOGLE_MAPS_API_KEY,
            ],
            'cache' => [
                'driver' => CACHE_DRIVER,
                'lifetime' => CACHE_LIFETIME,
            ],
        ];

        self::$loaded = true;
    }

    /**
     * Obtiene un valor de configuración usando notación de punto
     * 
     * @param string $key Clave en notación de punto (ej: 'database.host')
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::init();

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Establece un valor de configuración
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::init();

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!is_array($config)) {
                $config = [];
            }
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Verifica si existe una configuración
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }

    /**
     * Obtiene toda la configuración de una sección
     * 
     * @param string $section
     * @return array
     */
    public static function getSection($section)
    {
        return self::get($section, []);
    }

    /**
     * Métodos de conveniencia para configuraciones comunes
     */
    
    public static function getDatabaseConfig()
    {
        return self::getSection('database');
    }

    public static function getEmailConfig()
    {
        return self::getSection('email');
    }

    public static function getSecurityConfig()
    {
        return self::getSection('security');
    }

    public static function getAppConfig()
    {
        return self::getSection('app');
    }

    public static function isProduction()
    {
        return self::get('app.env') === 'production';
    }

    public static function isDevelopment()
    {
        return self::get('app.env') === 'development';
    }

    public static function isDebugEnabled()
    {
        return self::get('app.debug', false);
    }

    /**
     * Obtiene la URL completa de la aplicación
     * 
     * @param string $path
     * @return string
     */
    public static function url($path = '')
    {
        $baseUrl = rtrim(self::get('app.url'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Valida que todas las configuraciones críticas estén presentes
     * 
     * @return array Lista de configuraciones faltantes
     */
    public static function validate()
    {
        $required = [
            'database.host',
            'database.username',
            'database.database_general',
            'database.database_seguridad',
            'security.jwt_secret',
            'security.session_secret',
            'app.name',
            'app.url',
        ];

        $missing = [];
        
        foreach ($required as $key) {
            $value = self::get($key);
            if (empty($value)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Obtiene toda la configuración (para debugging)
     * 
     * @param bool $hideSensitive Ocultar datos sensibles
     * @return array
     */
    public static function all($hideSensitive = true)
    {
        self::init();
        
        if (!$hideSensitive) {
            return self::$config;
        }

        $config = self::$config;
        
        // Ocultar datos sensibles
        $sensitiveKeys = [
            'database.password',
            'email.smtp_pass',
            'security.jwt_secret',
            'security.session_secret',
            'security.encryption_key',
            'api.google_maps_key',
        ];

        foreach ($sensitiveKeys as $key) {
            $keys = explode('.', $key);
            $current = &$config;
            
            foreach ($keys as $k) {
                if (isset($current[$k])) {
                    if ($k === end($keys)) {
                        $current[$k] = '***HIDDEN***';
                    } else {
                        $current = &$current[$k];
                    }
                }
            }
        }

        return $config;
    }
}

?>
