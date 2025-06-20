<?php
require_once "app/models/loginModel.php";
require_once "helpers/helpers.php";

class Login extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new LoginModel();
    }

    public function index()
    {
        $data['page_id'] = 5;
        $data["page_title"] = "Inicio de sesión";
        $data["page_tag"] = "Inicio";
        $data["page_name"] = "login";
        $data["page_functions_js"] = "functions_login.js";
        $data["recaptcha_site_key"] = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';
        $this->views->getView($this, "login", $data);
    }

    public function loginUser()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
            exit();
        }

        try {
            
            if (defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY)) {
                $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
                if (!$this->verifyRecaptcha($recaptcha_response)) {
                    echo json_encode(['status' => false, 'msg' => 'Por favor, verifica que no eres un robot']);
                    exit();
                }
            }

            $strUsuario = strtolower(($_POST['txtEmail'] ?? ''));
            $strPassword = ($_POST['txtPass'] ?? '');

            if (empty($strUsuario) || empty($strPassword)) {
                echo json_encode(['status' => false, 'msg' => 'Usuario y contraseña son obligatorios']);
                exit();
            }

            
            $strPassword = hash("SHA256", $strPassword);

            
            $requestUser = $this->model->login($strUsuario, $strPassword);

            if (!$requestUser) {
                echo json_encode(['status' => false, 'msg' => 'Usuario o contraseña incorrectos']);
                exit();
            }

            
            if ($requestUser['estatus'] != 'activo') {
                echo json_encode(['status' => false, 'msg' => 'Usuario inactivo']);
                exit();
            }

            
            $userData = $this->model->sessionLogin($requestUser['idusuario']);

            if (!$userData) {
                echo json_encode(['status' => false, 'msg' => 'Error al cargar datos del usuario']);
                exit();
            }

            
            $_SESSION = array(); 
            
            
            $_SESSION['user'] = [
                'idusuario' => intval($userData['idusuario']),
                'usuario' => $userData['usuario'],
                'correo' => $userData['correo'],
                'estatus' => $userData['estatus'],
                'idrol' => intval($userData['idrol']),
                'rol_nombre' => $userData['rol_nombre'] ?? 'Usuario',
                'nombre' => $userData['usuario'],
                'logueado' => true,
                'tiempo_login' => date('Y-m-d H:i:s')
            ];

            
            $_SESSION['sessionUser'] = $_SESSION['user']['idusuario'];
            $_SESSION['login'] = true;

            
            $_SESSION['usuario_id'] = $userData['idusuario'];
            $_SESSION['rol_id'] = $userData['idrol'];
            $_SESSION['usuario_nombre'] = $userData['usuario'];
            $_SESSION['usuario_correo'] = $userData['correo'];

            
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            
            echo json_encode([
                'status' => true, 
                'msg' => 'Login exitoso',
                'redirect' => base_url() . 'dashboard'
            ]);

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            echo json_encode(['status' => false, 'msg' => 'Error interno del servidor']);
        }
        exit();
    }

    public function logout()
    {
        
        $_SESSION = [];
        
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        
        session_destroy();
        
        header('Location: ' . base_url() . 'login');
        exit();
    }

    /**
     * Verifica reCAPTCHA si está configurado
     */
    private function verifyRecaptcha($recaptcha_response)
    {
        if (empty($recaptcha_response)) {
            return false;
        }

        $secret_key = RECAPTCHA_SECRET_KEY;
        $verify_url = "https://www.google.com/recaptcha/api/siteverify";
        
        $data = array(
            'secret' => $secret_key,
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
        $result = file_get_contents($verify_url, false, $context);
        $resultJson = json_decode($result);

        return isset($resultJson->success) && $resultJson->success === true;
    }
}
?>