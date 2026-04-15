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
            header('Location: dashboard.php');
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $userController = new UserC();
            if ($userController->getByEmail($email)) {
                $error = 'Email already exists.';
            } else {
                $userId = $userController->register($name, $email, $password, 'user');
                $_SESSION['user'] = $userController->getById($userId);
                header('Location: dashboard.php');
                exit;
            }
        }

        return ['error' => $error];
    }
}
?>
