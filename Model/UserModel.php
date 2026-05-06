<?php
require_once __DIR__ . '/../config.php';

class UserModel
{
    public function register($name, $email, $password, $role = 'user', $recoveryCode = '')
    {
        $db = config::getConnexion();
        $this->ensureRecoveryCodeColumn($db);
        $sql = 'INSERT INTO user (name, email, password, role, recovery_code) VALUES (:name, :email, :password, :role, :recovery_code)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'recovery_code' => password_hash($recoveryCode, PASSWORD_BCRYPT)
        ]);
        return $db->lastInsertId();
    }

    public function login($email, $password)
    {
        $db = config::getConnexion();
        $this->ensureLoginSecurityColumns($db);
        $stmt = $db->prepare('SELECT * FROM user WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function getByEmail($email)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM user WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function verifyRecoveryCode($email, $recoveryCode)
    {
        $db = config::getConnexion();
        $this->ensureRecoveryCodeColumn($db);
        $this->ensureLoginSecurityColumns($db);
        $user = $this->getByEmail($email);
        if (!$user || empty($user['recovery_code'])) {
            return null;
        }

        return password_verify($recoveryCode, $user['recovery_code']) ? $user : null;
    }

    public function updatePassword($email, $password)
    {
        $db = config::getConnexion();
        $this->ensureLoginSecurityColumns($db);
        $stmt = $db->prepare('UPDATE user SET password = :password WHERE email = :email');
        $stmt->execute([
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'email' => $email
        ]);
        $this->resetLoginSecurity($email);
    }

    public function getLoginSecurity($email)
    {
        $db = config::getConnexion();
        $this->ensureLoginSecurityColumns($db);
        $stmt = $db->prepare('SELECT failed_login_attempts, locked_until FROM user WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function recordFailedLogin($email)
    {
        $db = config::getConnexion();
        $this->ensureLoginSecurityColumns($db);

        $security = $this->getLoginSecurity($email);
        if (!$security) {
            return null;
        }

        $attempts = ((int) ($security['failed_login_attempts'] ?? 0)) + 1;
        $lockedUntil = null;

        if ($attempts >= 3) {
            $lockedUntil = time() + 30;
            $attempts = 0;
        }

        $stmt = $db->prepare('UPDATE user SET failed_login_attempts = :attempts, locked_until = :locked_until WHERE email = :email');
        $stmt->execute([
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
            'email' => $email
        ]);

        return [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil
        ];
    }

    public function resetLoginSecurity($email)
    {
        $db = config::getConnexion();
        $this->ensureLoginSecurityColumns($db);
        $stmt = $db->prepare('UPDATE user SET failed_login_attempts = 0, locked_until = NULL WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    public function getById($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM user WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function listUsers()
    {
        $db = config::getConnexion();
        return $db->query('SELECT id, name, email, role FROM user ORDER BY id DESC')->fetchAll();
    }

    public function setRole($id, $role)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE user SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);
    }

    public function deleteUser($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM user WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function ensureRecoveryCodeColumn($db)
    {
        $stmt = $db->query("SHOW COLUMNS FROM user LIKE 'recovery_code'");
        if (!$stmt->fetch()) {
            $db->exec('ALTER TABLE user ADD recovery_code VARCHAR(255) NULL');
        }
    }

    private function ensureLoginSecurityColumns($db)
    {
        $stmt = $db->query("SHOW COLUMNS FROM user LIKE 'failed_login_attempts'");
        if (!$stmt->fetch()) {
            $db->exec('ALTER TABLE user ADD failed_login_attempts INT NOT NULL DEFAULT 0');
        }

        $stmt = $db->query("SHOW COLUMNS FROM user LIKE 'locked_until'");
        if (!$stmt->fetch()) {
            $db->exec('ALTER TABLE user ADD locked_until INT NULL');
        }
    }
}
?>
