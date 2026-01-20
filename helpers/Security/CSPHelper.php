<?php
namespace App\Helpers\Security;

/**
 * Helper para manejo de Content Security Policy (CSP)
 * 
 * @package App\Helpers\Security
 */
class CSPHelper
{
    /**
     * Genera un nonce para CSP y lo almacena en sesión
     * 
     * @return string El nonce generado
     */
    public static function generateNonce(): string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
        }
        
        return $_SESSION['csp_nonce'];
    }

    /**
     * Configura los headers de Content Security Policy
     * 
     * @param array|null $customPolicies Políticas personalizadas (opcional)
     */
    public static function setHeaders(?array $customPolicies = null): void
    {
        $nonce = self::generateNonce();
        
        // Política CSP por defecto
        $defaultPolicies = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-src 'self' https://www.google.com",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        $policies = $customPolicies ?? $defaultPolicies;
        $cspString = implode('; ', $policies);
        
        // Establecer headers de seguridad
        header("Content-Security-Policy: " . $cspString);
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }

    /**
     * Renderiza datos JavaScript de manera segura con CSP
     * 
     * @param string $varName Nombre de la variable JavaScript
     * @param mixed $data Datos a renderizar
     * @return string HTML del script con nonce
     */
    public static function renderJavaScriptData(string $varName, $data): string
    {
        $nonce = self::generateNonce();
        $jsonData = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        return "<script nonce=\"{$nonce}\">window.{$varName} = {$jsonData};</script>";
    }

    /**
     * Obtiene el atributo nonce para usar en tags script/style
     * 
     * @return string HTML del atributo nonce
     */
    public static function getNonceAttribute(): string
    {
        $nonce = self::generateNonce();
        return "nonce=\"{$nonce}\"";
    }
}
