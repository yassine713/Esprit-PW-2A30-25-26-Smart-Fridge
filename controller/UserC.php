<?php
require_once __DIR__ . '/../Model/UserModel.php';

class UserC
{
    private $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function register($name, $email, $password, $role = 'user', $recoveryCode = '')
    {
        return $this->model->register($name, $email, $password, $role, $recoveryCode);
    }

    public function login($email, $password)
    {
        return $this->model->login($email, $password);
    }

    public function getByEmail($email)
    {
        return $this->model->getByEmail($email);
    }

    public function getById($id)
    {
        return $this->model->getById($id);
    }

    public function verifyRecoveryCode($email, $recoveryCode)
    {
        return $this->model->verifyRecoveryCode($email, $recoveryCode);
    }

    public function updatePassword($email, $password)
    {
        $this->model->updatePassword($email, $password);
    }

    public function getLoginSecurity($email)
    {
        return $this->model->getLoginSecurity($email);
    }

    public function recordFailedLogin($email)
    {
        return $this->model->recordFailedLogin($email);
    }

    public function resetLoginSecurity($email)
    {
        $this->model->resetLoginSecurity($email);
    }

    public function listUsers()
    {
        return $this->model->listUsers();
    }

    public function setRole($id, $role)
    {
        $this->model->setRole($id, $role);
    }

    public function deleteUser($id)
    {
        $this->model->deleteUser($id);
    }
}
?>
