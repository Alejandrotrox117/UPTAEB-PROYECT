<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class Login extends Controllers
{
    // Método getter para obtener el valor de $model
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
        $this->views->getView($this, "login", $data);
    }


 
public function loginUser()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(($_POST['txtEmail']));
        $password = hash("SHA256", strClean($_POST['txtPass'])); // Encripta aquí

        $user = $this->model->login($email, $password);

        if (empty($user)) {
            $arrResponse = array('status' => false, 'msg' => 'Usuario o contraseña incorrectos');
        } else if ($user['estatus'] != "activo") { // Usa 1 si es numérico
            $arrResponse = array('status' => false, 'msg' => 'Usuario inactivo');
        } else {
            $_SESSION['idUser'] = $user['idusuario'];
            $_SESSION['login'] = true;
            $this->model->sessionLogin($user['idusuario']);
            $arrResponse = array('status' => true, 'msg' => 'ok');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}

public function resetPass()
{
    if (!empty($_POST)) {
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
                $token = bin2hex(random_bytes(20)); // Genera un token seguro

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
    //Funcion para confirmar el usuario
    public function confirmUser(string $params)
    {
        //verificar los parametros
        if (empty($params)) {
            header("Location:" . base_url());
        } else {

            $arrParams = explode(",", $params);
            $strEmail = ($arrParams[0]);
            $strToken = ($arrParams[1]);

            $arrResponse = $this->model->getTokenUser($strEmail, $strToken);
            if (empty($arrResponse)) {
                //var_dump($arrResponse);


                header("Location:" . base_url());
            } else {

                $data['page_title'] = "cambiar contraseña";
                $data['page_tag'] = "Cambiar contraseña";
                $data['page_name'] = "cambiar_contrasenia";
                $data['idusuario'] = $arrResponse['idusuario'];
                $data["page_functions_js"] = "functions_login.js";
                $data['correo'] = $strEmail;
                $data['token'] = $strToken;

                $this->views->getView($this, "Password", $data);
            }
        }


        die();
    }

    public function setPassword()
    {
        if (empty($_POST['txtPassWord']) || empty($_POST['txtConfirmPassWord'])) {
            $arrResponse = array("status" => false, "msg" => "Error en Datos");
        } else {
            $idUsuario = intval($_POST['idusuario']);
            $password = $_POST['txtPassWord'];
            $confirmPassword = $_POST['txtConfirmPassWord'];
            $strEmail = $_POST['txtCorreo'];
            $strToken = $_POST['txtToken'];

            if ($password != $confirmPassword) {
                $arrResponse = array("status" => false, "msg" => "Las contraseñas no coinciden");
            } else {
                $arrResponse = $this->model->getTokenUser($strEmail, $strToken);

                if (empty($arrResponse)) {
                    $arrResponse = array("status" => false, "msg" => "No se pudo actualizar la contraseña");
                } else {
                    $password = hash("SHA256", $password);

                    $requestUpdate = $this->model->insertPassword($idUsuario, $password);

                    if ($requestUpdate) {
                        $arrResponse = array("status" => true, "msg" => "Contraseña actualizada");
                    } else {
                        $arrResponse = array("status" => false, "msg" => "No se pudo actualizar");
                    }
                }
            }
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

        exit();
    }
}
