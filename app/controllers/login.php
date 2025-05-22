<?php

class Login extends Controllers
{
    // Método getter para obtener el valor de $model
    public function getModel()
    {
        return $this->model;
    }

    // Método setter para establecer el valor de $model
    public function setModel(loginModel $model)
    {
        $this->model = $model;
    }

    public function __construct()
    {
        parent::__construct();
        session_start();
        if (isset($_SESSION['login'])) {
            header('Location: ' . base_url() . '/dashboard');
            die();
        }
        $this->model = new loginModel();
    }

    public function login($params)
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
        $arrResponse = array();

        if (empty($_POST['txtEmail']) || empty($_POST['txtPass'])) {
            $arrResponse = array("status" => false, "msg" => "Datos vacíos");
        } else {
            $strEmail = strtolower($_POST['txtEmail']);
            $strPass = hash("SHA256", $_POST['txtPass']);
            //$strPass = $_POST['txtPass'];
            $requestUser = $this->model->login($strEmail, $strPass);

            if (empty($requestUser)) {
                $arrResponse = array("status" => false, "msg" => "El usuario o contraseña son incorrectos");
            } else {
                $arrData = $requestUser;

                if ($arrData['estado'] == 1) {
                    $_SESSION['idusuario'] = $arrData['idusuario']; // Asigna el valor a $_SESSION['idusuario']
                    // $_SESSION['personId'] = $arrData['personaId'];
                    $_SESSION['login'] = true;
                    $arrData = $this->model->sessionLogin($_SESSION['idusuario']);
                    sessionUser($_SESSION['idusuario']);
                    $arrResponse = array("status" => true, "msg" => "OK");
                } else {
                    $arrResponse = array("status" => false, "msg" => "Usuario inactivo");
                }
            }
            if (isset($_SESSION['userData'])) {
                $idusuario = $_SESSION['userData']['personaId'];
                if ($idusuario > 0) {
                    $arrData = $this->model->getInfoPerson($idusuario);
                    if (empty($arrData)) {
                        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
                    } else {
                        $arrResponse = array('status' => true, 'data' => $arrData);
                        sessionPersona($_SESSION['userData']['personaId']);
                    }
                }
            }
        }
        // Agrega "echo" antes de "json_encode" para enviar la respuesta
        //dep($_POST);
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        //var_dump($_POST);
        exit();
    }

    public function resetPass()
    {
        if (!empty($_POST)) {
            if (empty($_POST['txtEmailReset'])) {
                $arrResponse = array("status" => false, "msg" => "Datos vacíos");
            } else {
                $token = token();
                $strEmail = strtolower($_POST['txtEmailReset']);
                $arrData = $this->model->getUsuarioEmail($strEmail);

                if (empty($arrData)) {
                    $arrResponse = array("status" => false, "msg" => "El usuario no existe");
                } else {
                    $idUsuario = $arrData['idusuario'];
                    $nombreUsuario = $arrData['usuario'];

                    $url_recovery = base_url() . '/login/confirmUser/' . $strEmail . '/' . $token;
                    $requestUpdate = $this->model->setTokenUser($idUsuario, $token);

                    $dataUsuario = array(
                        'usuario' => $nombreUsuario,
                        'correo' => $strEmail,
                        'asunto' => 'Recuperar cuenta - ' . NOMBRE_REMITENTE,
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
                                'msg' => 'No es posible realizar el proceso, intenta más tarde.......'
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

            // Agrega "echo" antes de "json_encode" para enviar la respuesta
            // dep($_POST);
            // dep($arrResponse);
            // var_dump($sendEmail);
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
