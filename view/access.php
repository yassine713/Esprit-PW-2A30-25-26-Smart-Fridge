<?php
require_once __DIR__ . '/../controller/auth.php';
require_login();

$user = refresh_current_user();
$target = $_GET['target'] ?? '';

if ($target === 'admin') {
    if ($user['role'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    }

    header('Location: dashboard.php?access_message=user_cannot_admin');
    exit;
}

if ($target === 'user') {
    if ($user['role'] === 'user') {
        header('Location: dashboard.php');
        exit;
    }

    header('Location: admin/index.php?access_message=admin_cannot_user');
    exit;
}

header('Location: index.php');
exit;
?>
