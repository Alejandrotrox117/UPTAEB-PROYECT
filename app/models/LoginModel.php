<?php
// filepath: c:\xampp\htdocs\project\app\models\LoginModel.php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class LoginModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }

    public function getUserByUsername($usuario) {
        $sql = "SELECT * FROM usuarios WHERE usuario = ? AND estado = 1";
        $result = $this->searchAll($sql, [$usuario]);

        if (!empty($result)) {
            return $result[0];
        }

        return [];
    }
}