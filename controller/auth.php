<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/UserC.php';

function refresh_current_user($loginPath = 'login.php', $maxAgeSeconds = 20)
{
    if (!isset($_SESSION['user']['id'])) {
        header('Location: ' . $loginPath);
        exit;
    }

    $now = time();
    $lastRefresh = (int) ($_SESSION['user_refreshed_at'] ?? 0);
    if ($maxAgeSeconds > 0 && $lastRefresh > 0 && ($now - $lastRefresh) < $maxAgeSeconds) {
        return $_SESSION['user'];
    }

    $userController = new UserC();
    $freshUser = $userController->getById((int) $_SESSION['user']['id']);

    if (!$freshUser) {
        unset($_SESSION['user']);
        header('Location: ' . $loginPath);
        exit;
    }

    $_SESSION['user'] = $freshUser;
    $_SESSION['user_refreshed_at'] = $now;
    return $freshUser;
}

function require_login($loginPath = 'login.php')
{
    if (!isset($_SESSION['user'])) {
        header('Location: ' . $loginPath);
        exit;
    }

    refresh_current_user($loginPath);
}

function require_admin($loginPath = 'login.php', $fallbackPath = 'dashboard.php')
{
    $user = refresh_current_user($loginPath, 0);
    if ($user['role'] !== 'admin') {
        header('Location: ' . $fallbackPath);
        exit;
    }
}

function access_notice()
{
    $message = $_GET['access_message'] ?? '';
    if ($message === 'user_cannot_admin') {
        return 'You are registered as a user. You cannot access the admin page.';
    }
    if ($message === 'admin_cannot_user') {
        return 'You are registered as an admin. You cannot access the user site.';
    }
    return '';
}
?>
