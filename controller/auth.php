<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin()
{
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}
?>
