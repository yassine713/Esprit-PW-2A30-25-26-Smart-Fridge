<?php
require_once __DIR__ . '/../config.php';

class ExerciseModel
{
    public function listExercises()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM exercise ORDER BY name')->fetchAll();
    }

    public function addExercise($name)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO exercise (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }

    public function updateExercise($id, $name)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE exercise SET name=:name WHERE id=:id');
        $stmt->execute(['id' => $id, 'name' => $name]);
    }

    public function deleteExercise($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM exercise WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function listLogsByUser($userId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT ue.id, ue.duration_min, ue.date_done, e.name
                FROM user_exercise ue
                JOIN exercise e ON e.id = ue.exercise_id
                WHERE ue.user_id = :uid
                ORDER BY ue.date_done DESC, ue.id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function addLog($userId, $exerciseId, $durationMin, $dateDone)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO user_exercise (user_id, exercise_id, duration_min, date_done) VALUES (:uid, :eid, :dur, :date)');
        $stmt->execute([
            'uid' => $userId,
            'eid' => $exerciseId,
            'dur' => $durationMin,
            'date' => $dateDone
        ]);
    }

    public function updateLog($logId, $userId, $exerciseId, $durationMin, $dateDone)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE user_exercise SET exercise_id=:eid, duration_min=:dur, date_done=:date WHERE id=:id AND user_id=:uid');
        $stmt->execute([
            'id' => $logId,
            'uid' => $userId,
            'eid' => $exerciseId,
            'dur' => $durationMin,
            'date' => $dateDone
        ]);
    }

    public function deleteLog($logId, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM user_exercise WHERE id=:id AND user_id=:uid');
        $stmt->execute(['id' => $logId, 'uid' => $userId]);
    }
}
?>
