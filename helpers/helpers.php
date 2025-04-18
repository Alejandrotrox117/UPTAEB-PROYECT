<?php

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
//permite fragmentar los modales
function getModal(string $modal, $data)
{
    $view_modal = "app/views/forms/{$modal}.php";
    require_once($view_modal);
}





?>