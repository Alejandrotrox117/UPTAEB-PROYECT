<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class Permisos extends Controllers
{
    protected $model;

    public function __construct()
    {
        parent::__construct(); // aquí también se carga PermisosModel
        $this->model = new PermisosModel();
    }
    public function index()
    {
        $this->views->getView($this, "permisos");
    }
}