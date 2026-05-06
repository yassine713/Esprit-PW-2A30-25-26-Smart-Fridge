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

        if (empty($_SESSION['login_captcha_token'])) {
            $_SESSION['login_captcha_token'] = bin2hex(random_bytes(16));
        }

        $error = '';
        $showForgotPassword = false;
        $resetModalOpen = false;
        $resetStep = 'code';
        $resetError = '';
        $resetMessage = '';
        $submittedEmail = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController = new UserC();
            $resetAction = $_POST['reset_action'] ?? '';

            if ($resetAction === 'verify_recovery') {
                $resetModalOpen = true;
                $email = trim($_POST['reset_email'] ?? '');
                $submittedEmail = $email;
                $recoveryCode = trim($_POST['recovery_code'] ?? '');

                if (!preg_match('/^\d{4}$/', $recoveryCode)) {
                    $resetError = 'Recovery code must be exactly 4 numbers.';
                } elseif ($userController->verifyRecoveryCode($email, $recoveryCode)) {
                    $_SESSION['password_reset_email'] = $email;
                    $resetStep = 'password';
                    $resetMessage = 'Recovery code confirmed. Create a new password.';
                } else {
                    $resetError = 'Invalid recovery code.';
                }

                return $this->response($error, $_SESSION['login_captcha_token'], $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail);
            }

            if ($resetAction === 'update_password') {
                $resetModalOpen = true;
                $resetStep = 'password';
                $email = $_SESSION['password_reset_email'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';

                if (!$email) {
                    $resetStep = 'code';
                    $resetError = 'Confirm your recovery code first.';
                } elseif (strlen(trim($newPassword)) < 4) {
                    $resetError = 'Password must be at least 4 characters.';
                } else {
                    $userController->updatePassword($email, $newPassword);
                    unset($_SESSION['password_reset_email']);
                    $resetModalOpen = false;
                    $resetStep = 'code';
                    $resetMessage = 'Password updated. You can sign in now.';
                }

                return $this->response($error, $_SESSION['login_captcha_token'], $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail);
            }

            $email = trim($_POST['email'] ?? '');
            $submittedEmail = $email;
            $password = $_POST['password'] ?? '';
            $captchaToken = $_POST['captcha_token'] ?? '';

            if (!hash_equals($_SESSION['login_captcha_token'], $captchaToken)) {
                return $this->response('Please complete the robot verification.', $_SESSION['login_captcha_token'], $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail);
            }

            $existingUser = $userController->getByEmail($email);
            if ($existingUser) {
                $security = $userController->getLoginSecurity($email);
                $lockedUntil = (int) ($security['locked_until'] ?? 0);

                if ($lockedUntil > time()) {
                    $secondsLeft = $lockedUntil - time();
                    $showForgotPassword = true;
                    return $this->response('Too many failed attempts. Try again after ' . $secondsLeft . ' seconds.', $_SESSION['login_captcha_token'], $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail);
                }
            }

            $user = $userController->login($email, $password);

            if ($user) {
                $userController->resetLoginSecurity($email);
                $_SESSION['user'] = $user;
                unset($_SESSION['login_captcha_token']);
                header('Location: ' . $this->redirectFor($user));
                exit;
            }

            $error = 'Invalid email or password.';
            $showForgotPassword = (bool) $existingUser;

            if ($existingUser) {
                $failedLogin = $userController->recordFailedLogin($email);
                if (!empty($failedLogin['locked_until']) && $failedLogin['locked_until'] > time()) {
                    $error = 'Too many failed attempts. Try again after 30 seconds.';
                }
            }
        }

        return $this->response($error, $_SESSION['login_captcha_token'], $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail);
    }

    private function redirectFor($user)
    {
        return ($user['role'] ?? 'user') === 'admin' ? 'admin/index.php' : 'dashboard.php';
    }

    private function response($error, $captchaToken, $showForgotPassword, $resetModalOpen, $resetStep, $resetError, $resetMessage, $submittedEmail)
    {
        return [
            'error' => $error,
            'captchaToken' => $captchaToken,
            'showForgotPassword' => $showForgotPassword,
            'resetModalOpen' => $resetModalOpen,
            'resetStep' => $resetStep,
            'resetError' => $resetError,
            'resetMessage' => $resetMessage,
            'submittedEmail' => $submittedEmail
        ];
    }
}
?>
