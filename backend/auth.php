<?php
session_start();

function require_admin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: /UNISTAY_UIUX/frontend/public/login.php"); 
        exit();
    }
}
?>