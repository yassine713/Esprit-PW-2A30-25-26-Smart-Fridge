<?php
require_once __DIR__ . '/UserC.php';

class SignupController
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            $destination = ($_SESSION['user']['role'] ?? 'user') === 'admin' ? 'admin/index.php' : 'dashboard.php';
            header('Location: ' . $destination);
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $recoveryCode = trim($_POST['recovery_code'] ?? '');

            $userController = new UserC();
            if (!preg_match('/^\d{4}$/', $recoveryCode)) {
                $error = 'Recovery code must be exactly 4 numbers.';
            } elseif ($userController->getByEmail($email)) {
                $error = 'Email already exists.';
            } else {
                $userId = $userController->register($name, $email, $password, 'user', $recoveryCode);
                $_SESSION['user'] = $userController->getById($userId);
                header('Location: dashboard.php');
                exit;
            }
        }

        return ['error' => $error];
    }
}
?>
