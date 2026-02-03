<?php


// =============================================================================
// AUTOLOAD DE HELPERS ORGANIZADOS
// =============================================================================

// Security Helpers
require_once __DIR__ . '/Security/CSRFHelper.php';
require_once __DIR__ . '/Security/CSPHelper.php';

// Auth Helpers  
require_once __DIR__ . '/Auth/RecaptchaHelper.php';

// Permission Helpers
require_once __DIR__ . '/Permissions/PermisosHelper.php';

// Validation Helpers
if (file_exists(__DIR__ . '/Validation/ExpresionesRegulares.php')) {
    require_once __DIR__ . '/Validation/ExpresionesRegulares.php';
}

// Bitacora Helper 
if (file_exists(__DIR__ . '/BitacoraHelper.php')) {
    require_once __DIR__ . '/BitacoraHelper.php';
}

// Controller Helpers (incluye funciones de validación)
if (file_exists(__DIR__ . '/controller_helpers.php')) {
    require_once __DIR__ . '/controller_helpers.php';
}


// FUNCIONES DE COMPATIBILIDAD (WRAPPERS)

use App\Helpers\Security\CSRFHelper;
use App\Helpers\Security\CSPHelper;
use App\Helpers\Auth\RecaptchaHelper;
use App\Helpers\Permissions\PermisosHelper;

// --- CSRF Functions ---

function generateCSRFToken(): string {
    return CSRFHelper::generateToken();
}

function validateCSRFToken(string $token): bool {
    return CSRFHelper::validateToken($token);
}

function getCSRFToken(): string {
    return CSRFHelper::getToken();
}

// --- CSP Functions ---

function generateCSPNonce(): string {
    return CSPHelper::generateNonce();
}

function setCSPHeaders(): void {
    CSPHelper::setHeaders();
}

function renderJavaScriptData(string $varName, $data): string {
    return CSPHelper::renderJavaScriptData($varName, $data);
}

// --- Recaptcha Functions ---

function getRecaptchaSiteKey(): string {
    return RecaptchaHelper::getSiteKey();
}

function getRecaptchaSecretKey(): string {
    return RecaptchaHelper::getSecretKey();
}

// =============================================================================
// UTILIDADES GENERALES
// =============================================================================

/**
 * Genera URL base de la aplicación
 * 
 * @param string $path Ruta relativa (con o sin / inicial)
 * @return string URL completa
 */
function base_url(string $path = ''): string
{
    // Cargar configuración si no está disponible
    if (!defined('APP_URL')) {
        require_once __DIR__ . '/../config/config.php';
    }
    
    $base_url = defined('APP_URL') ? APP_URL : "http://localhost/project";
    
    // Normalizar base URL (quitar / final)
    $base_url = rtrim($base_url, '/');
    
    // Si no hay path, retornar base URL con /
    if (empty($path)) {
        return $base_url . '/';
    }
    
    // Normalizar path (quitar / inicial y final)
    $path = trim($path, '/');
    
    // Retornar URL completa
    return $base_url . '/' . $path;
}

/**
 * Genera URL de assets
 */
function assets_url(string $path = ''): string
{
    return base_url('public/assets/' . ltrim($path, '/'));
}

/**
 * Limpia string para prevenir inyecciones
 * 
 * @deprecated Considera usar ExpresionesRegulares::limpiar() en su lugar
 */


// TEMPLATES Y VISTAS

/**
 * Incluye el header de la aplicación
 */
function headerAdmin($data = ""): void
{
    $view_header = "public/header.php";
    if (file_exists($view_header)) {
        require_once($view_header);
    }
}

/**
 * Incluye el footer de la aplicación
 */
function footerAdmin($data = ""): void
{
    $view_footer = "public/footer.php";
    if (file_exists($view_footer)) {
        require_once($view_footer);
    }
}

// =============================================================================
// EMAIL FUNCIONES (PHPMailer)
// =============================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Cargar PHPMailer
if (file_exists('app/libs/phpmailer/src/Exception.php')) {
    require_once 'app/libs/phpmailer/src/Exception.php';
    require_once 'app/libs/phpmailer/src/PHPMailer.php';
    require_once 'app/libs/phpmailer/src/SMTP.php';
}

/**
 * Envía email usando configuración nativa de PHP
 * 
 * @deprecated Considera usar EmailHelper::send() en su lugar
 */
function sendEmail(array $data, string $template): bool
{
    $asunto = "Reinicio de contraseña";
    $emailDestino = $data['correo'];
    $empresa = env('APP_NAME', 'Sistema');
    $remitente = env('FROM_EMAIL', '');
    $emailCopia = !empty($data['copia']) ? $data['copia'] : $remitente;
    
    $de = "MIME-Version: 1.0\r\n";
    $de .= "Content-type: text/html; charset=UTF-8\r\n";
    $de .= "From: {$empresa} <{$remitente}>\r\n";
    $de .= "Bcc: {$remitente}\r\n";
    
    ob_start();
    $templatePath = "app/views/templates/email/" . $template . ".php";
    if (file_exists($templatePath)) {
        require_once($templatePath);
    }
    $mensaje = ob_get_clean();
    
    return mail($emailDestino, $asunto, $mensaje, $de);
}

/**
 * Envía email usando PHPMailer (SMTP)
 * 
 * @deprecated Considera usar EmailHelper::sendSMTP() en su lugar
 */
function sendEmailLocal(array $data, string $template): string
{
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return 'PHPMailer no está disponible';
    }

    $mail = new PHPMailer(true);
    
    ob_start();
    $templatePath = "app/views/templates/email/" . $template . ".php";
    if (file_exists($templatePath)) {
        require_once($templatePath);
    }
    $mensaje = ob_get_clean();

    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USER', '');
        $mail->Password = env('SMTP_PASS', '');
        $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
        $mail->Port = env('SMTP_PORT', 587);

        // Recipients
        $mail->setFrom(env('FROM_EMAIL', ''), env('FROM_NAME', 'Sistema'));
        $mail->addAddress($data['correo']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Recuperar acceso a la cuenta";
        $mail->Body = $mensaje;
      
        $mail->send();
        return 'Mensaje Enviado';
    } catch (PHPMailerException $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return "El mensaje no pudo ser enviado. Error: {$mail->ErrorInfo}";
    }
}

// =============================================================================
// SESIONES (LEGACY - Mantener por compatibilidad)
// =============================================================================

/**
 * Obtiene información del usuario en sesión
 * 
 * @deprecated Usa métodos del modelo directamente
 */
function sessionUser(int $usuarioId): array
{
    // Esta función requiere el modelo de login
    // Se mantiene por compatibilidad pero no se recomienda su uso
    return [];
}

// =============================================================================
// CONFIGURACIÓN GLOBAL
// =============================================================================

// Establecer zona horaria
date_default_timezone_set('America/Caracas');
