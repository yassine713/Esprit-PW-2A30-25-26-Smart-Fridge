<?php
require_once __DIR__ . '/../Model/SupportModel.php';

class SupportC
{
    private $model;

    public function __construct()
    {
        $this->model = new SupportModel();
    }

    public function createRequest($userId, $first, $last, $email, $type, $title, $desc)
    {
        $this->model->createRequest($userId, $first, $last, $email, $type, $title, $desc);
    }

    public function listByUser($userId)
    {
        return $this->model->listByUser($userId);
    }

    public function listAll()
    {
        return $this->model->listAll();
    }

    public function updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc)
    {
        $this->model->updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc);
    }

    public function deleteRequest($id, $userId)
    {
        $this->model->deleteRequest($id, $userId);
    }

    public function addResponse($requestId, $adminId, $message)
    {
        $this->model->addResponse($requestId, $adminId, $message);
    }

    public function listResponses($requestId)
    {
        return $this->model->listResponses($requestId);
    }
}
?>
