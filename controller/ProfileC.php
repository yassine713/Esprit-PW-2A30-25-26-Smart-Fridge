<?php
require_once __DIR__ . '/../Model/ProfileModel.php';

class ProfileC
{
    private $model;

    public function __construct()
    {
        $this->model = new ProfileModel();
    }

    public function getByUserId($userId)
    {
        return $this->model->getByUserId($userId);
    }

    public function upsert($userId, $weight, $height, $goal, $disease, $allergy, $budget)
    {
        $this->model->upsert($userId, $weight, $height, $goal, $disease, $allergy, $budget);
    }
}
?>
