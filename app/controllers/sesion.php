<?php
session_start();

require_once "../../helpers/helpers.php";

if (!isset($_SESSION['user'])) {
    header("Location: " . base_url()); 
    exit();
}
?>
