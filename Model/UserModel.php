<?php
require_once __DIR__ . '/../config.php';

class UserModel
{
    public function register($name, $email, $password, $role = 'user')
    {
        $db = config::getConnexion();
        $sql = 'INSERT INTO user (name, email, password, role) VALUES (:name, :email, :password, :role)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role
        ]);
        return $db->lastInsertId();
    }

    public function login($email, $password)
    {
        $db = config::getConnexion();
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
}
?>
