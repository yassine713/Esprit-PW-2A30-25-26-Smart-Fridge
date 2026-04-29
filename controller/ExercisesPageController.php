<?php
require_once __DIR__ . '/ExerciseC.php';

class ExercisesPageController
{
    public function handle($user)
    {
        $exerciseController = new ExerciseC();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add_log') {
                $exerciseController->addLog(
                    $user['id'],
                    (int) ($_POST['exercise_id'] ?? 0),
                    (int) ($_POST['duration_min'] ?? 0),
                    $_POST['date_done'] ?? ''
                );
                header('Location: exercises.php');
                exit;
            }

            if ($action === 'update_log') {
                $exerciseController->updateLog(
                    (int) ($_POST['log_id'] ?? 0),
                    $user['id'],
                    (int) ($_POST['exercise_id'] ?? 0),
                    (int) ($_POST['duration_min'] ?? 0),
                    $_POST['date_done'] ?? ''
                );
                header('Location: exercises.php');
                exit;
            }

            if ($action === 'delete_log') {
                $exerciseController->deleteLog((int) ($_POST['log_id'] ?? 0), $user['id']);
                header('Location: exercises.php');
                exit;
            }

            if ($action === 'add_objective') {
                $exerciseController->addObjective(
                    $user['id'],
                    (int) ($_POST['exercise_id'] ?? 0),
                    trim($_POST['title'] ?? ''),
                    (int) ($_POST['target_duration_min'] ?? 0),
                    $_POST['start_date'] ?? '',
                    $_POST['end_date'] ?? '',
                    $_POST['status'] ?? 'active'
                );
                header('Location: exercises.php');
                exit;
            }

            if ($action === 'update_objective') {
                $exerciseController->updateObjective(
                    (int) ($_POST['objective_id'] ?? 0),
                    $user['id'],
                    (int) ($_POST['exercise_id'] ?? 0),
                    trim($_POST['title'] ?? ''),
                    (int) ($_POST['target_duration_min'] ?? 0),
                    $_POST['start_date'] ?? '',
                    $_POST['end_date'] ?? '',
                    $_POST['status'] ?? 'active'
                );
                header('Location: exercises.php');
                exit;
            }

            if ($action === 'delete_objective') {
                $exerciseController->deleteObjective((int) ($_POST['objective_id'] ?? 0), $user['id']);
                header('Location: exercises.php');
                exit;
            }
        }

        return [
            'exerciseList' => $exerciseController->listExercises(),
            'logs' => $exerciseController->listLogsByUser($user['id']),
            'objectives' => $exerciseController->listObjectivesByUser($user['id'])
        ];
    }
}
?>
