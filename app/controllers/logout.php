<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class logout extends Controllers
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
    }

    public function index()
{
    $this->cerrarSesion();
}



     private function cerrarSesion()
    {
        session_start();
        session_unset();
        session_destroy();

        // Redirigir al index o login principal
        header("Location: ./");
        exit();
    }



}

?>