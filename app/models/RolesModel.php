<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class RolesModel  extends Mysql
{
    private $roles;
    public function __construct()
    {
        $this->usuario = new Conexion();
    }

    


}
?>