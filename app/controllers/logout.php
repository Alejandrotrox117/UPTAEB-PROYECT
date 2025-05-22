<?php
class Logout{

    public function __construct()
    {
        session_start();
        session_destroy();
        header("location: ".base_url()."/login");
    }





}

?>