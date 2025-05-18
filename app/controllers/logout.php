<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/bitacoraModel.php"; 
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
        $bitacora = new BitacoraModel();

        $idusuario = $_SESSION['user']['idusuario'] ?? null;

        if ($idusuario) {
            $bitacora->setTabla("usuario");
            $bitacora->setAccion("Salida del sistema");
            $bitacora->setIdUsuario($idusuario);
            // Si quieres puedes también setear la fecha manualmente, pero no es necesario
            $bitacora->setFecha(date("Y-m-d H:i:s"));
            $bitacora->insertar2();
        }

        session_unset();
        session_destroy();

        // Redirigir al index o login principal
        header("Location: ./");
        exit();
    }



}

?>