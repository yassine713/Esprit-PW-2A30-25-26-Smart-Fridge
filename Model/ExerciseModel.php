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

    public function listObjectivesByUser($userId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT o.id, o.user_id, o.exercise_id, o.title, o.target_duration_min,
                       o.start_date, o.end_date, o.status, e.name AS exercise_name,
                       COALESCE(SUM(ue.duration_min), 0) AS progress_min
                FROM objective o
                JOIN exercise e ON e.id = o.exercise_id
                LEFT JOIN user_exercise ue
                    ON ue.user_id = o.user_id
                    AND ue.exercise_id = o.exercise_id
                    AND ue.date_done BETWEEN o.start_date AND o.end_date
                WHERE o.user_id = :uid
                GROUP BY o.id, o.user_id, o.exercise_id, o.title, o.target_duration_min,
                         o.start_date, o.end_date, o.status, e.name
                ORDER BY o.end_date ASC, o.id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function addObjective($userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare(
            'INSERT INTO objective (user_id, exercise_id, title, target_duration_min, start_date, end_date, status)
             VALUES (:uid, :eid, :title, :target, :start_date, :end_date, :status)'
        );
        $stmt->execute([
            'uid' => $userId,
            'eid' => $exerciseId,
            'title' => $title,
            'target' => $targetDurationMin,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ]);
    }

    public function updateObjective($objectiveId, $userId, $exerciseId, $title, $targetDurationMin, $startDate, $endDate, $status)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare(
            'UPDATE objective
             SET exercise_id=:eid, title=:title, target_duration_min=:target,
                 start_date=:start_date, end_date=:end_date, status=:status
             WHERE id=:id AND user_id=:uid'
        );
        $stmt->execute([
            'id' => $objectiveId,
            'uid' => $userId,
            'eid' => $exerciseId,
            'title' => $title,
            'target' => $targetDurationMin,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ]);
    }

    public function deleteObjective($objectiveId, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM objective WHERE id=:id AND user_id=:uid');
        $stmt->execute(['id' => $objectiveId, 'uid' => $userId]);
    }
}
?>
