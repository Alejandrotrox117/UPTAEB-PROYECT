<?php
date_default_timezone_set('America/Caracas');
const BASE_URL = "http://localhost/project";
function base_url()
{
    return BASE_URL;
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

?>