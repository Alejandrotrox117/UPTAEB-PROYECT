<?php
namespace App\Helpers\Auth;

/**
 * Helper para integración con Google reCAPTCHA v2
 * 
 * @package App\Helpers\Auth
 */
class RecaptchaHelper
{
    /**
     * Obtiene la clave del sitio (site key) desde el .env
     * 
     * @return string La site key de reCAPTCHA
     */
    public static function getSiteKey(): string
    {
        return env('RECAPTCHA_SITE_KEY', '');
    }

    /**
     * Obtiene la clave secreta (secret key) desde el .env
     * 
     * @return string La secret key de reCAPTCHA
     */
    public static function getSecretKey(): string
    {
        return env('RECAPTCHA_SECRET_KEY', '');
    }

    /**
     * Verifica un token de reCAPTCHA con la API de Google
     * 
     * @param string $recaptchaResponse El token de respuesta del recaptcha
     * @return bool True si es válido, false si no
     */
    public static function verify(string $recaptchaResponse): bool
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $secretKey = self::getSecretKey();
        
        if (empty($secretKey)) {
            error_log("RECAPTCHA_SECRET_KEY no está configurado en .env");
            return false;
        }

        $verifyUrl = "https://www.google.com/recaptcha/api/siteverify";
        
        $data = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($verifyUrl, false, $context);
        
        if ($result === false) {
            error_log("Error al verificar reCAPTCHA: No se pudo conectar con Google");
            return false;
        }

        $resultJson = json_decode($result);

        return isset($resultJson->success) && $resultJson->success === true;
    }

    /**
     * Genera el HTML del widget de reCAPTCHA
     * 
     * @param string $theme Tema del widget (light o dark)
     * @param string $size Tamaño del widget (normal o compact)
     * @return string HTML del widget
     */
    public static function renderWidget(string $theme = 'light', string $size = 'normal'): string
    {
        $siteKey = self::getSiteKey();
        
        if (empty($siteKey)) {
            return '<!-- reCAPTCHA no configurado -->';
        }

        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8') . '" data-theme="' . $theme . '" data-size="' . $size . '"></div>';
    }

    /**
     * Genera el script necesario para cargar reCAPTCHA
     * 
     * @param string|null $onloadCallback Función callback al cargar (opcional)
     * @return string HTML del script
     */
    public static function renderScript(?string $onloadCallback = null): string
    {
        $url = "https://www.google.com/recaptcha/api.js";
        
        if ($onloadCallback) {
            $url .= "?onload={$onloadCallback}&render=explicit";
        }

        return '<script src="' . $url . '" async defer></script>';
    }

    /**
     * Verifica si reCAPTCHA está correctamente configurado
     * 
     * @return bool True si está configurado, false si no
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getSiteKey()) && !empty(self::getSecretKey());
    }
}
