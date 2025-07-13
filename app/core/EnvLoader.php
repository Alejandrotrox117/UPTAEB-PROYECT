<?php

/**
 * Clase para manejar variables de entorno desde archivo .env
 * Proporciona una forma segura de gestionar configuraciones sensibles
 */
class EnvLoader
{
    private static $loaded = false;
    private static $variables = [];

    /**
     * Carga las variables de entorno desde el archivo .env
     * 
     * @param string $path Ruta al archivo .env
     * @throws Exception Si el archivo .env no existe
     */
    public static function load($path = null)
    {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }

        if (!file_exists($path)) {
            throw new Exception("Archivo .env no encontrado en: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear la línea
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remover comillas si existen
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                // Almacenar en $_ENV y en array interno
                $_ENV[$key] = $value;
                self::$variables[$key] = $value;
                
                // También establecer en putenv() para compatibilidad
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene una variable de entorno
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        // Buscar en $_ENV primero
        if (isset($_ENV[$key])) {
            return self::parseValue($_ENV[$key]);
        }

        // Buscar en array interno
        if (isset(self::$variables[$key])) {
            return self::parseValue(self::$variables[$key]);
        }

        // Buscar en getenv()
        $value = getenv($key);
        if ($value !== false) {
            return self::parseValue($value);
        }

        return $default;
    }

    /**
     * Convierte valores de string a tipos apropiados
     * 
     * @param string $value
     * @return mixed
     */
    private static function parseValue($value)
    {
        if ($value === '') {
            return '';
        }

        $lower = strtolower($value);
        
        switch ($lower) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            default:
                // Si es numérico, convertir a número
                if (is_numeric($value)) {
                    return strpos($value, '.') !== false ? (float)$value : (int)$value;
                }
                return $value;
        }
    }

    /**
     * Verifica si una variable de entorno existe
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }

    /**
     * Obtiene todas las variables cargadas
     * 
     * @return array
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$variables;
    }

    /**
     * Función helper para obtener variable de entorno
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env($key, $default = null)
    {
        return self::get($key, $default);
    }
}

/**
 * Función global helper para acceder a variables de entorno
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env($key, $default = null)
{
    return EnvLoader::env($key, $default);
}

// Cargar automáticamente las variables de entorno
try {
    EnvLoader::load();
} catch (Exception $e) {
    error_log("Error cargando variables de entorno: " . $e->getMessage());
}

?>
