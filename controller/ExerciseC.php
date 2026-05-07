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

    public function addExercise($name, $youtubeUrl = '')
    {
        $this->model->addExercise($name, $youtubeUrl);
    }

    public function updateExercise($id, $name, $youtubeUrl = '')
    {
        $this->model->updateExercise($id, $name, $youtubeUrl);
    }

    public function saveYoutubeUrl($exerciseId, $youtubeUrl)
    {
        $this->model->saveYoutubeUrl($exerciseId, $youtubeUrl);
    }

    public function getYoutubeUrl($exerciseId)
    {
        return $this->model->getYoutubeUrl($exerciseId);
    }

    public function hasTutorial($exerciseId)
    {
        return $this->model->hasTutorial($exerciseId);
    }

    public function deleteExercise($id)
    {
        $this->model->deleteExercise($id);
    }

    public function listLogsByUser($userId)
    {
        return $this->model->listLogsByUser($userId);
    }

    public function getExerciseStatsByUser($userId)
    {
        return $this->model->getExerciseStatsByUser($userId);
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

    public function listObjectivesByUser($userId)
    {
        return $this->model->listObjectivesByUser($userId);
    }

    public function addObjective($userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status)
    {
        if (!$this->isObjectiveTitleValid($title)) {
            return;
        }

        $this->model->addObjective($userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status);
    }

    public function updateObjective($objectiveId, $userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status)
    {
        if (!$this->isObjectiveTitleValid($title)) {
            return;
        }

        $this->model->updateObjective($objectiveId, $userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status);
    }

    public function deleteObjective($objectiveId, $userId)
    {
        $this->model->deleteObjective($objectiveId, $userId);
    }

    private function isObjectiveTitleValid($title)
    {
        return preg_match('/^[\p{L} ]{3,}$/u', trim($title)) === 1;
    }
}
?>
