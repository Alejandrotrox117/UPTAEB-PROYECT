<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'helpers\phpmailer\Exception.php';
require 'helpers\phpmailer\PHPMailer.php';
require 'helpers\phpmailer\SMTP.php';
date_default_timezone_set('America/Caracas');
const BASE_URL = "http://localhost/project";
function base_url()
{
    return BASE_URL;
}

const RECAPTCHA_SITE_KEY = "6LdZw1srAAAAAKMqgrnpTZzD52Mb1piDmpwMR-VX";
function getRecaptchaSiteKey()
{
    return RECAPTCHA_SITE_KEY;
}
const RECAPTCHA_SECRET_KEY = "6LdZw1srAAAAALfwJOzFS-1PER0cHv-elWV-5-xZ";
function getRecaptchaSecretKey()
{
    return RECAPTCHA_SECRET_KEY;
}

//permite fragmentar el header del html principal
function headerAdmin($data = "")
{
    $view_header = "public/header.php";
    require_once($view_header);
}

//Permite fragmentar el footer del html principal
function footerAdmin($data = "")
{
    $view_footer = "public/footer.php";
    require_once($view_footer);
}

function sessionUser(int $usuarioId)
{
    require_once("models/loginModel.php");
    $objLogin = new LoginModel();
    $request = $objLogin->sessionLogin($usuarioId);
    return $request;
}

function sessionPersona($usuarioId)
{
    require_once("models/loginModel.php");
    $objLogin = new LoginModel();
    $request = $objLogin->getInfoPerson($usuarioId);
    return $request;
}
function sendEmail($data, $template)
{
   $asunto = "reinicio de contraseña";
    $emailDestino = $data['correo'];
    $empresa = "La pradera de pavia";
    $remitente = "  ";
    $emailCopia=!empty($data['copia']) ? $data['copia'] : $remitente;
    //ENVIO DE CORREO
    $de = "MIME-Version: 1.0\r\n";
    $de .= "Content-type: text/html; charset=UTF-8\r\n";
    $de .= "From: {$empresa} <{$remitente}>\r\n";
    $de .= "Bcc: {$remitente}\r\n";
    ob_start();
    require_once("views/templates/email/" . $template . ".php");
    $mensaje = ob_get_clean();
    $send = mail($emailDestino,$asunto, $mensaje, $de);
    return $send;
}
function sendEmailLocal($data, $template)
{
    $mail = new PHPMailer(true);
    ob_start();
    require_once("views/templates/email/" . $template . ".php");
    $mensaje = ob_get_clean();

    try {
        //Server settings
        
        $mail->SMTPDebug = 0; // Set to 0 for no debug output, 2 for detailed
        $mail->isSMTP();
        $mail->Host       = 'smtp-mail.outlook.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'celtechstore2000@outlook.com';
        $mail->Password   = 'lgtjztgijxhwipvn';
        $mail->SMTPSecure = 'tls'; // Use 'tls' instead of PHPMailer::ENCRYPTION_STARTTLS
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('celtechstore2000@outlook.com', 'Celtech Store');
        $mail->addAddress($data['correo']);

        //Attachments (optional)
        //$mail->addAttachment('/var/tmp/file.tar.gz');
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

        //Content
        $mail->isHTML(true);
        $mail->Subject = "Recuperar acceso a la cuenta";
        $mail->Body    = $mensaje;
      
        $mail->send();
        return 'Mensaje Enviado';
    } catch (Exception $e) {
        echo "El mensaje no pudo ser enviado. Error: {$mail->ErrorInfo}";
    }
}
function strClean($str)
{
    $string = preg_replace('/[^A-Za-z0-9]/', ' ', $str);
    $string = trim($string); //Elimina espacios en blanco al inicio y al final
    $string = stripslashes($string);
    $string = str_ireplace("<script>", "", $string);
    $string = str_ireplace("</script>", "", $string);
    $string = str_ireplace("<script src", "", $string);
    $string = str_ireplace("<script type=", "", $string);
    $string = str_ireplace("SELECT * FROM", "", $string);
    $string = str_ireplace("DELETE FROM", "", $string);
    $string = str_ireplace("INSERT INTO", "", $string);
    $string = str_ireplace("DROP TABLE", "", $string);
    $string = str_ireplace("OR '1'='1", "", $string);
    $string = str_ireplace('OR "1"="1"', "", $string);
    $string = str_ireplace('OR  ́1 ́= ́1', "", $string);
    $string = str_ireplace("is NULL; --", "", $string);
    $string = str_ireplace("is NULL; --", "", $string);
    $string = str_ireplace("LIKE '", "", $string);
    $string = str_ireplace('LIKE "', "", $string);
    $string = str_ireplace("LIKE  ́", "", $string);
    $string = str_ireplace("OR 'a'='a", "", $string);
    $string = str_ireplace('OR "a"="a', "", $string);
    $string = str_ireplace("OR  ́a ́= ́a", "", $string);
    $string = str_ireplace("OR  ́a ́= ́a", "", $string);
    $string = str_ireplace("--", "", $string);
    $string = str_ireplace("^", "", $string);
    $string = str_ireplace("[", "", $string);
    $string = str_ireplace("]", "", $string);
    $string = str_ireplace("==", "", $string);
    return $string;
}

?>