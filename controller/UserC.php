<?php
require_once __DIR__ . '/../Model/UserModel.php';

class UserC
{
    private $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function register($name, $email, $password, $role = 'user')
    {
        return $this->model->register($name, $email, $password, $role);
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
