<?php
namespace App\Helpers\Security;

/**
 * Helper para manejo de tokens CSRF (Cross-Site Request Forgery)
 * 
 * @package App\Helpers\Security
 */
class CSRFHelper
{
    /**
     * Genera un nuevo token CSRF y lo almacena en la sesión
     * 
     * @return string El token generado
     */
    public static function generateToken(): string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }

    /**
     * Valida un token CSRF contra el almacenado en sesión
     * 
     * @param string $token Token a validar
     * @return bool True si es válido, false si no
     */
    public static function validateToken(string $token): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        $isValid = hash_equals($_SESSION['csrf_token'], $token);
        
        // Regenerar el token después de validar (one-time use)
        if ($isValid) {
            unset($_SESSION['csrf_token']);
        }
        
        return $isValid;
    }

    /**
     * Obtiene el token CSRF actual de la sesión
     * Si no existe, genera uno nuevo
     * 
     * @return string El token CSRF
     */
    public static function getToken(): string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['csrf_token'] ?? self::generateToken();
    }

    /**
     * Genera un campo hidden HTML con el token CSRF
     * 
     * @return string HTML del campo hidden
     */
    public static function getTokenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Regenera el token CSRF (útil después de login)
     */
    public static function regenerateToken(): string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['csrf_token']);
        return self::generateToken();
    }
}
