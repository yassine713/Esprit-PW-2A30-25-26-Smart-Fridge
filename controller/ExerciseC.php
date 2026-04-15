<?php
require_once __DIR__ . '/../Model/ExerciseModel.php';

class ExerciseC
{
    private $model;

    public function __construct()
    {
        $this->model = new ExerciseModel();
    }

    public function listExercises()
    {
        return $this->model->listExercises();
    }

    public function addExercise($name)
    {
        $this->model->addExercise($name);
    }

    public function updateExercise($id, $name)
    {
        $this->model->updateExercise($id, $name);
    }

    public function deleteExercise($id)
    {
        $this->model->deleteExercise($id);
    }

    public function listLogsByUser($userId)
    {
        return $this->model->listLogsByUser($userId);
    }

    public function addLog($userId, $exerciseId, $durationMin, $dateDone)
    {
        $this->model->addLog($userId, $exerciseId, $durationMin, $dateDone);
    }

    public function updateLog($logId, $userId, $exerciseId, $durationMin, $dateDone)
    {
        $this->model->updateLog($logId, $userId, $exerciseId, $durationMin, $dateDone);
    }

    public function deleteLog($logId, $userId)
    {
        $this->model->deleteLog($logId, $userId);
    }
}
?>
