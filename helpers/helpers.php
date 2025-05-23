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