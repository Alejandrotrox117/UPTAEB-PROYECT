<?php

require_once "src/Exception.php";
require_once "src/PHPMailer.php";
require_once "src/SMTP.php";

// Incluir configuración - Ruta corregida
require_once __DIR__ . "/../../../config/config.php";

// Incluir helpers para tener acceso a base_url()
require_once __DIR__ . "/../../../helpers/helpers.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelper 
{
    public static function enviarEmailRecuperacion($email, $token, $nombreUsuario = '') 
    {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = !empty(SMTP_USER); // Solo autenticar si hay usuario
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
         
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $nombreUsuario);
            $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Contraseña - Recuperadora';
            
            $resetUrl = base_url() . '/login/confirmarReset/' . $token;
            
            $mail->Body = self::getTemplateRecuperacion($nombreUsuario, $resetUrl, $token);
            $mail->AltBody = "Hola " . ($nombreUsuario ?: '') . ",\n\n" .
                           "Has solicitado restablecer tu contraseña.\n" .
                           "Haz clic en el siguiente enlace para continuar:\n" .
                           $resetUrl . "\n\n" .
                           "Este enlace expira en 1 hora.\n" .
                           "Si no solicitaste este cambio, puedes ignorar este mensaje.\n\n" .
                           "Saludos,\nEquipo de Recuperadora";
            
            $mail->send();
            
            // Log de éxito
            error_log("Email de recuperación enviado exitosamente a: " . $email);
            
            return ['status' => true, 'message' => 'Email enviado correctamente'];
            
        } catch (Exception $e) {
            // Log detallado del error
            $errorMessage = "Error enviando email a {$email}: " . $e->getMessage();
            error_log($errorMessage);
            
            // Mensaje de error más específico según el tipo
            $userMessage = 'Error al enviar el email';
            
            if (strpos($e->getMessage(), 'SMTP connect()') !== false) {
                $userMessage = 'No se pudo conectar al servidor de correo. Verifica la configuración SMTP.';
            } elseif (strpos($e->getMessage(), 'SMTP Error: Could not authenticate') !== false) {
                $userMessage = 'Error de autenticación SMTP. Verifica usuario y contraseña.';
            } elseif (strpos($e->getMessage(), 'Invalid address') !== false) {
                $userMessage = 'Dirección de email inválida.';
            }
            
            return ['status' => false, 'message' => $userMessage . ' (Detalle: ' . $e->getMessage() . ')'];
        }
    }
    
    private static function getTemplateRecuperacion($nombreUsuario, $resetUrl, $token) 
    {
        $nombre = !empty($nombreUsuario) ? $nombreUsuario : 'Usuario';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Recuperación de Contraseña</h1>
                    <p>Sistema de Gestión - Recuperadora</p>
                </div>
                
                <div class='content'>
                    <h2>Hola {$nombre},</h2>
                    
                    <p>Has solicitado restablecer tu contraseña para acceder al sistema.</p>
                    
                    <p>Para continuar con el proceso, haz clic en el siguiente botón:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Restablecer Contraseña</a>
                    </div>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;'>
                        {$resetUrl}
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <ul>
                            <li>Este enlace expira en <strong>1 hora</strong></li>
                            <li>Solo puede ser usado una vez</li>
                            <li>Si no solicitaste este cambio, puedes ignorar este mensaje</li>
                        </ul>
                    </div>
                    
                    <p>Si tienes algún problema, contacta al administrador del sistema.</p>
                    
                    <p>Saludos,<br><strong>Equipo de Recuperadora</strong></p>
                </div>
                
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                    <p>Token de seguridad: {$token}</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
