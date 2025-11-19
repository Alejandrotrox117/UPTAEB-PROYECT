<?php
namespace App\Controllers;

use App\Core\Controllers;

class Logout extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("location: ".base_url()."/login");
        exit;
    }
}
?>