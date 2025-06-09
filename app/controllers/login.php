<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";
class Login extends Controllers
{
    
    private $bitacoraModel;
    private $BitacoraHelper;

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
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();
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
        $this->views->getView($this, "login", $data);
    }

    public function loginUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $email = strtolower($_POST['txtEmail']);
                $password = hash("SHA256", $_POST['txtPass']);

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
                        $_SESSION['rol_nombre'] = $userData['rol_nombre'] ?? '';
                    }
                    
                    $arrResponse = array('status' => true, 'msg' => 'Login exitoso');
                }
            } catch (Exception $e) {
                error_log("Login::loginUser - Error: " . $e->getMessage());
                $arrResponse = array('status' => false, 'msg' => 'Error interno del servidor');
            }
            
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function resetPass()
    {
        if (!empty($_POST)) {
            try {
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
                            'asunto' => 'Recuperar cuenta - Sistema',
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
                                    'msg' => 'No es posible enviar el email, intenta más tarde.'
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
            } catch (Exception $e) {
                error_log("Login::resetPass - Error: " . $e->getMessage());
                $arrResponse = array('status' => false, 'msg' => 'Error interno del servidor');
            }
            
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    public function confirmUser(string $params)
    {
        try {
            if (empty($params)) {
                header("Location:" . base_url());
                die();
            }

            $arrParams = explode(",", $params);
            if (count($arrParams) < 2) {
                header("Location:" . base_url());
                die();
            }

            $strEmail = strClean($arrParams[0]);
            $strToken = strClean($arrParams[1]);

            $arrResponse = $this->model->getTokenUser($strEmail, $strToken);
            
            if (empty($arrResponse)) {
                header("Location:" . base_url());
                die();
            }

            $data['page_title'] = "Cambiar contraseña";
            $data['page_tag'] = "Cambiar contraseña";
            $data['page_name'] = "cambiar_contrasenia";
            $data['idusuario'] = $arrResponse['idusuario'];
            $data["page_functions_js"] = "functions_login.js";
            $data['correo'] = $strEmail;
            $data['token'] = $strToken;

            $this->views->getView($this, "Password", $data);
            
        } catch (Exception $e) {
            error_log("Login::confirmUser - Error: " . $e->getMessage());
            header("Location:" . base_url());
        }
        die();
    }

    public function setPassword()
    {
        try {
            if (empty($_POST['txtPassWord']) || empty($_POST['txtConfirmPassWord'])) {
                $arrResponse = array("status" => false, "msg" => "Todos los campos son obligatorios");
            } else {
                $idUsuario = intval($_POST['idusuario']);
                $password = strClean($_POST['txtPassWord']);
                $confirmPassword = strClean($_POST['txtConfirmPassWord']);
                $strEmail = strClean($_POST['txtCorreo']);
                $strToken = strClean($_POST['txtToken']);

                if ($password != $confirmPassword) {
                    $arrResponse = array("status" => false, "msg" => "Las contraseñas no coinciden");
                } else if (strlen($password) < 6) {
                    $arrResponse = array("status" => false, "msg" => "La contraseña debe tener al menos 6 caracteres");
                } else {
                    // Verificar que el token sigue siendo válido
                    $tokenValidation = $this->model->getTokenUser($strEmail, $strToken);

                    if (empty($tokenValidation)) {
                        $arrResponse = array("status" => false, "msg" => "Token inválido o expirado");
                    } else {
                        $hashedPassword = hash("SHA256", $password);
                        $requestUpdate = $this->model->insertPassword($idUsuario, $hashedPassword);

                        if ($requestUpdate) {
                            $arrResponse = array("status" => true, "msg" => "Contraseña actualizada correctamente");
                        } else {
                            $arrResponse = array("status" => false, "msg" => "No se pudo actualizar la contraseña");
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Login::setPassword - Error: " . $e->getMessage());
            $arrResponse = array("status" => false, "msg" => "Error interno del servidor");
        }
        
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function logout()
    {
        try {
            session_start();
            
            // Limpiar todas las variables de sesión
            $_SESSION = array();
            
            // Destruir la cookie de sesión si existe
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destruir la sesión
            session_destroy();
            
        } catch (Exception $e) {
            error_log("Login::logout - Error: " . $e->getMessage());
        }
        
        header('Location: ' . base_url() . '/login');
        exit;
    }
}
?>