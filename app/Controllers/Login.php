<?php

use App\Models\LoginModel;

//obtener el modelo de login
function getLoginModel() {
    return new LoginModel();
}

function login_index() {
    if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
        header('Location: ' . base_url('dashboard'));
        exit();
    }
    
    $data['page_id'] = 5;
    $data["page_title"] = "Inicio de sesión";
    $data["page_tag"] = "Inicio";
    $data["page_name"] = "login";
    $data["page_functions_js"] = "functions_login.js";
    $data["recaptcha_site_key"] = getRecaptchaSiteKey();
    $data["csrf_token"] = generateCSRFToken();
    renderView('login', 'login', $data);
}

function login_loginUser() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
        exit();
    }

    try {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($csrf_token)) {
            echo json_encode(['status' => false, 'msg' => 'Token de seguridad inválido. Por favor, recargue la página e intente nuevamente.']);
            exit();
        }
        
        if (defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY)) {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!login_verifyRecaptcha($recaptcha_response)) {
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
        
        $model = getLoginModel();
        $emailExists = $model->getUsuarioEmail($strUsuario);
        
        if (!$emailExists) {
            echo json_encode(['status' => false, 'msg' => 'El correo electrónico ingresado no existe en el sistema']);
            exit();
        }

        if ($emailExists['estatus'] != 'activo') {
            echo json_encode(['status' => false, 'msg' => 'Su cuenta se encuentra inactiva. Contacte al administrador del sistema']);
            exit();
        }
        
        $strPassword = hash("SHA256", $strPassword);
        $requestUser = $model->login($strUsuario, $strPassword);

        if (!$requestUser) {
            echo json_encode(['status' => false, 'msg' => 'La contraseña ingresada es incorrecta. Por favor, verifique e intente nuevamente']);
            exit();
        }

        $userData = $model->sessionLogin($requestUser['idusuario']);

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

function login_logout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    header('Location: ' . base_url('login'));
    exit();
}

function login_resetPassword() {
    $data['page_title'] = "Recuperar Contraseña";
    $data['recaptcha_site_key'] = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';
    $data["csrf_token"] = generateCSRFToken();
    renderView('login', 'resetPassword', $data);
}

function login_enviarResetPassword() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf_token)) {
                echo json_encode(['status' => false, 'msg' => 'Token de seguridad inválido. Por favor, recargue la página e intente nuevamente.']);
                exit();
            }
            
            $email = strtolower(trim($_POST['txtEmailReset'] ?? ''));
            
            if (empty($email)) {
                echo json_encode(['status' => false, 'msg' => 'El correo electrónico es obligatorio para poder recuperar tu contraseña.']);
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => false, 'msg' => 'El formato del correo electrónico no es válido. Por favor, verifica e intenta nuevamente.']);
                exit();
            }

            $model = getLoginModel();
            $usuario = $model->getUsuarioEmail($email);
            
            if (!$usuario) {
                echo json_encode(['status' => false, 'msg' => 'No encontramos una cuenta asociada a este correo electrónico. Verifica que sea el correo correcto.']);
                exit();
            }

            if ($usuario['estatus'] != 'activo') {
                echo json_encode(['status' => false, 'msg' => 'Tu cuenta se encuentra inactiva. Por favor, contacta al administrador del sistema.']);
                exit();
            }

            $token = bin2hex(random_bytes(32));
            $tokenSaved = $model->setTokenUser($usuario['idusuario'], $token);
            
            if ($tokenSaved) {
                $nombreCompleto = $usuario['usuario'] ?? 'Usuario';
                
                $emailResult = \EmailHelper::enviarEmailRecuperacion(
                    $email, 
                    $token, 
                    $nombreCompleto
                );
                
                if ($emailResult['status']) {
                    echo json_encode([
                        'status' => true, 
                        'msg' => 'Se ha enviado un enlace de recuperación a tu correo electrónico. El enlace expira en 1 hora, así que úsalo pronto. Revisa tu bandeja de entrada y la carpeta de spam.'
                    ]);
                } else {
                    echo json_encode([
                        'status' => false, 
                        'msg' => 'No pudimos enviar el correo de recuperación. ' . $emailResult['message'] . ' Por favor, intenta nuevamente en unos minutos.'
                    ]);
                }
            } else {
                echo json_encode(['status' => false, 'msg' => 'Ocurrió un problema al generar el enlace de recuperación. Por favor, intenta nuevamente.']);
            }

        } catch (Exception $e) {
            error_log("Error en enviarResetPassword: " . $e->getMessage());
            echo json_encode(['status' => false, 'msg' => 'Error interno del servidor']);
        }
    }
    exit();
}

function login_confirmarReset($token = null) {
    if (empty($token)) {
        header("Location: " . base_url('login?error=token_invalido'));
        exit();
    }

    $model = getLoginModel();
    $tokenData = $model->getTokenUserByToken($token);
    
    if (!$tokenData) {
        $data['page_title'] = "Token Inválido";
        $data['error'] = "El enlace de recuperación es inválido o ha expirado.";
        renderView('login', 'tokenError', $data);
        return;
    }

    $data['page_title'] = "Nueva Contraseña";
    $data['token'] = $token;
    $data['usuario'] = $tokenData;
    $data['page_functions_js'] = "functions_resetpass.js";
    $data["csrf_token"] = generateCSRFToken();
    renderView('login', 'nuevaPassword', $data);
}

function login_actualizarPassword() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf_token)) {
                echo json_encode(['status' => false, 'msg' => 'Token de seguridad inválido. Por favor, recargue la página e intente nuevamente.']);
                exit();
            }
            
            $token = trim($_POST['token'] ?? '');
            $password = trim($_POST['txtPassword'] ?? '');
            $confirmPassword = trim($_POST['txtConfirmPassword'] ?? '');

            if (empty($token) || empty($password) || empty($confirmPassword)) {
                echo json_encode(['status' => false, 'msg' => 'Todos los campos son obligatorios. Por favor, completa la información requerida.']);
                exit();
            }

            if ($password !== $confirmPassword) {
                echo json_encode(['status' => false, 'msg' => 'Las contraseñas no coinciden. Por favor, verifica que hayas escrito la misma contraseña en ambos campos.']);
                exit();
            }

            if (strlen($password) < 6) {
                echo json_encode(['status' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres. Te recomendamos usar una contraseña más segura.']);
                exit();
            }

            $model = getLoginModel();
            $tokenData = $model->getTokenUserByToken($token);
            if (!$tokenData) {
                echo json_encode(['status' => false, 'msg' => 'El enlace de recuperación ha expirado o no es válido. Por favor, solicita un nuevo enlace de recuperación.']);
                exit();
            }

            $passwordHash = hash("SHA256", $password);
            $updated = $model->updatePassword($tokenData['idusuario'], $passwordHash);

            if ($updated) {
                $model->deleteToken($token);
                
                echo json_encode([
                    'status' => true, 
                    'msg' => '¡Perfecto! Tu contraseña ha sido actualizada exitosamente. Ahora puedes iniciar sesión con tu nueva contraseña.'
                ]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'No pudimos actualizar tu contraseña. Por favor, intenta nuevamente o solicita un nuevo enlace de recuperación.']);
            }

        } catch (Exception $e) {
            error_log("Error en actualizarPassword: " . $e->getMessage());
            echo json_encode(['status' => false, 'msg' => 'Error interno del servidor']);
        }
    }
    exit();
}

function login_verifyRecaptcha($recaptcha_response) {
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