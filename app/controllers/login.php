<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Login extends Controllers
{
    public function set_model($model)
    {
        $this->model = $model;
    }

    public function get_model()
    {
        return $this->model;
    }

    public function __construct()
    {
        parent::__construct();
        session_start();
        if (isset($_SESSION['login'])) {
            header('Location: ' . base_url() . '/dashboard');
            die();
        }
    }

    public function index()
    {
        $data['page_id'] = 5;
        $data["page_title"] = "Inicio de sesión";
        $data["page_tag"] = "Inicio";
        $data["page_name"] = "login";
        $data["page_functions_js"] = "functions_login.js";
        $data["recaptcha_site_key"] = getRecaptchaSiteKey(); // Agregar clave del sitio para reCAPTCHA
        $this->views->getView($this, "login", $data);
    }

    /**
     * Verificar reCAPTCHA
     */
    private function verifyRecaptcha($recaptcha_response)
    {
        if (empty($recaptcha_response)) {
            return false;
        }

        $data = array(
            'secret' => getRecaptchaSecretKey(),
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $resultJson = json_decode($result);

        return $resultJson->success;
    }

    public function loginUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Verificar reCAPTCHA
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            
            if (!$this->verifyRecaptcha($recaptcha_response)) {
                $arrResponse = array('status' => false, 'msg' => 'Por favor, verifica que no eres un robot');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $email = strtolower(($_POST['txtEmail']));
            $password = hash("SHA256", strClean($_POST['txtPass']));

            $user = $this->model->login($email, $password);

            if (empty($user)) {
                $arrResponse = array('status' => false, 'msg' => 'Usuario o contraseña incorrectos');
            } else if ($user['estatus'] != "activo") {
                $arrResponse = array('status' => false, 'msg' => 'Usuario inactivo');
            } else {
                // Guardar datos de sesión con nombres consistentes
                $_SESSION['idUser'] = $user['idusuario'];           // Mantener compatibilidad
                $_SESSION['usuario_id'] = $user['idusuario'];       // Para sistema de permisos
                $_SESSION['usuario_email'] = $user['correo'];
                $_SESSION['login'] = true;
                
                // Obtener información completa del usuario
                $userData = $this->model->sessionLogin($user['idusuario']);
                
                if (!empty($userData)) {
                    $_SESSION['userData'] = $userData;
                    $_SESSION['usuario_nombre'] = $userData['usuario'];
                    $_SESSION['usuario_rol'] = $userData['idrol'];
                    // $_SESSION['rol_nombre'] = $userData['nombre'];
                }
                
                $arrResponse = array('status' => true, 'msg' => 'ok');
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function resetPass()
    {
        if (!empty($_POST)) {
            
            // Verificar reCAPTCHA para reseteo de contraseña
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            
            if (!$this->verifyRecaptcha($recaptcha_response)) {
                $arrResponse = array('status' => false, 'msg' => 'Por favor, verifica que no eres un robot');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                exit();
            }

            if (empty($_POST['txtEmailReset'])) {
                $arrResponse = array("status" => false, "msg" => "El correo es obligatorio");
            } else {
                $strEmail = strtolower(strClean($_POST['txtEmailReset']));
                $arrData = $this->model->getUsuarioEmail($strEmail);

                if (empty($arrData)) {
                    $arrResponse = array("status" => false, "msg" => "El usuario no existe");
                } else {
                    $idUsuario = $arrData['idusuario'];
                    $nombreUsuario = $arrData['usuario'];
                    $token = bin2hex(random_bytes(20));

                    $url_recovery = base_url() . '/login/confirmUser/' . $strEmail . ',' . $token;
                    $requestUpdate = $this->model->setTokenUser($idUsuario, $token);

                    $dataUsuario = array(
                        'usuario' => $nombreUsuario,
                        'correo' => $strEmail,
                        'asunto' => 'Recuperar cuenta - Recuperadora',
                        'url_recovery' => $url_recovery
                    );

                    if ($requestUpdate) {
                        $sendEmail = sendEmailLocal($dataUsuario, 'email_cambioPassword');
                        if ($sendEmail === "Mensaje Enviado") {
                            $arrResponse = array(
                                'status' => true,
                                'msg' => 'Se ha enviado un email a tu cuenta de correo para cambiar tu contraseña.'
                            );
                        } else {
                            $arrResponse = array(
                                'status' => false,
                                'msg' => 'No es posible realizar el proceso, intenta más tarde.'
                            );
                        }
                    } else {
                        $arrResponse = array(
                            'status' => false,
                            'msg' => 'No es posible realizar el proceso, intenta más tarde'
                        );
                    }
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    public function confirmUser(string $params)
    {
        if (empty($params)) {
            header("Location:" . base_url());
        } else {
            $arrParams = explode(",", $params);
            $strEmail = ($arrParams[0]);
            $strToken = ($arrParams[1]);

            $arrResponse = $this->model->getTokenUser($strEmail, $strToken);
            if (empty($arrResponse)) {
                header("Location:" . base_url());
            } else {
                $data['page_title'] = "cambiar contraseña";
                $data['page_tag'] = "Cambiar contraseña";
                $data['page_name'] = "cambiar_contrasenia";
                $data['idusuario'] = $arrResponse['idusuario'];
                $data["page_functions_js"] = "functions_login.js";
                $data['correo'] = $strEmail;
                $data['token'] = $strToken;
                $data["recaptcha_site_key"] = getRecaptchaSiteKey(); // Agregar clave para el formulario de cambio de contraseña

                $this->views->getView($this, "Password", $data);
            }
        }
        die();
    }

    public function setPassword()
    {
        // Verificar reCAPTCHA para cambio de contraseña
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        if (!$this->verifyRecaptcha($recaptcha_response)) {
            $arrResponse = array('status' => false, 'msg' => 'Por favor, verifica que no eres un robot');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            exit();
        }

        if (empty($_POST['txtPassWord']) || empty($_POST['txtConfirmPassWord'])) {
            $arrResponse = array("status" => false, "msg" => "Error en Datos");
        } else {
            $idUsuario = intval($_POST['idusuario']);
            $password = $_POST['txtPassWord'];
            $confirmPassword = $_POST['txtConfirmPassWord'];
            $strEmail = $_POST['txtCorreo'];
            $strToken = $_POST['txtToken'];

            if ($password != $confirmPassword) {
                $arrResponse = array("status" => false, "msg" => "Las contraseñas no coinciden");
            } else {
                $arrResponse = $this->model->getTokenUser($strEmail, $strToken);

                if (empty($arrResponse)) {
                    $arrResponse = array("status" => false, "msg" => "No se pudo actualizar la contraseña");
                } else {
                    $password = hash("SHA256", $password);
                    $requestUpdate = $this->model->insertPassword($idUsuario, $password);

                    if ($requestUpdate) {
                        $arrResponse = array("status" => true, "msg" => "Contraseña actualizada");
                    } else {
                        $arrResponse = array("status" => false, "msg" => "No se pudo actualizar");
                    }
                }
            }
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Método para cerrar sesión
    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: ' . base_url() . 'login');
        exit;
    }
}
?>