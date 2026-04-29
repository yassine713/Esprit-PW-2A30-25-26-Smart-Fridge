<?php
require_once __DIR__ . '/UserC.php';

class LoginController
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            header('Location: ' . $this->redirectFor($_SESSION['user']));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $userController = new UserC();
            $user = $userController->login($email, $password);

            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: ' . $this->redirectFor($user));
                exit;
            }

            $error = 'Invalid email or password.';
        }

        return ['error' => $error];
    }

    private function redirectFor($user)
    {
        return ($user['role'] ?? 'user') === 'admin' ? 'admin/index.php' : 'dashboard.php';
    }
}
?>
