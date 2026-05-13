<?php
require_once __DIR__ . '/../config.php';

class ExerciseModel
{
    public function listExercises()
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);
        return $db->query('SELECT * FROM exercise ORDER BY name')->fetchAll();
    }

    public function addExercise($name, $youtubeUrl = '')
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);
        $stmt = $db->prepare('INSERT INTO exercise (name, qr_token, youtube_url) VALUES (:name, :qr_token, :youtube_url)');
        $stmt->execute([
            'name' => $name,
            'qr_token' => $this->generateQrToken(),
            'youtube_url' => $this->normalizeYoutubeUrl($youtubeUrl)
        ]);
    }

    public function updateExercise($id, $name, $youtubeUrl = '')
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);
        $stmt = $db->prepare('UPDATE exercise SET name=:name, youtube_url=:youtube_url WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'youtube_url' => $this->normalizeYoutubeUrl($youtubeUrl)
        ]);
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
        $this->ensureExerciseColumns($db);
        $sql = 'SELECT ue.id, ue.exercise_id, ue.duration_min, ue.date_done, e.name, e.qr_token, e.youtube_url
                FROM user_exercise ue
                JOIN exercise e ON e.id = ue.exercise_id
                WHERE ue.user_id = :uid
                ORDER BY ue.date_done DESC, ue.id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getExerciseStatsByUser($userId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT e.name,
                       COUNT(ue.id) AS log_count,
                       COALESCE(SUM(ue.duration_min), 0) AS total_duration
                FROM user_exercise ue
                JOIN exercise e ON e.id = ue.exercise_id
                WHERE ue.user_id = :uid
                GROUP BY e.id, e.name
                ORDER BY log_count DESC, total_duration DESC, e.name ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $distribution = $stmt->fetchAll();
        $favorite = $distribution[0] ?? null;

        $summary = $db->prepare(
            'SELECT COUNT(id) AS total_logs,
                    COALESCE(SUM(duration_min), 0) AS total_duration
             FROM user_exercise
             WHERE user_id = :uid'
        );
        $summary->execute(['uid' => $userId]);
        $totals = $summary->fetch();

        return [
            'favorite' => $favorite ?: null,
            'distribution' => $distribution,
            'total_logs' => (int) ($totals['total_logs'] ?? 0),
            'total_duration' => (int) ($totals['total_duration'] ?? 0)
        ];
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

    public function verifyQrToken($exerciseId, $token)
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);

        $stmt = $db->prepare('SELECT id, name, qr_token FROM exercise WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $exerciseId]);
        $exercise = $stmt->fetch();

        if (!$exercise || $token === '' || empty($exercise['qr_token'])) {
            return false;
        }

        return hash_equals($exercise['qr_token'], $token) ? $exercise : false;
    }

    public function saveYoutubeUrl($exerciseId, $youtubeUrl)
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);

        $stmt = $db->prepare('UPDATE exercise SET youtube_url = :youtube_url WHERE id = :id');
        $stmt->execute([
            'id' => $exerciseId,
            'youtube_url' => $this->normalizeYoutubeUrl($youtubeUrl)
        ]);
    }

    public function getYoutubeUrl($exerciseId)
    {
        $db = config::getConnexion();
        $this->ensureExerciseColumns($db);

        $stmt = $db->prepare('SELECT youtube_url FROM exercise WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $exerciseId]);
        $exercise = $stmt->fetch();

        return trim((string) ($exercise['youtube_url'] ?? ''));
    }

    public function hasTutorial($exerciseId)
    {
        return $this->getYoutubeUrl($exerciseId) !== '';
    }

    public function logFromQrScan($exerciseId, $userId)
    {
        $db = config::getConnexion();

        $durationStmt = $db->prepare(
            'SELECT duration_min
             FROM user_exercise
             WHERE user_id = :uid AND exercise_id = :eid
             ORDER BY date_done DESC, id DESC
             LIMIT 1'
        );
        $durationStmt->execute([
            'uid' => $userId,
            'eid' => $exerciseId
        ]);
        $lastLog = $durationStmt->fetch();
        $durationMin = max(1, (int) ($lastLog['duration_min'] ?? 15));

        $stmt = $db->prepare(
            'INSERT INTO user_exercise (user_id, exercise_id, duration_min, date_done)
             VALUES (:uid, :eid, :dur, CURDATE())'
        );
        $stmt->execute([
            'uid' => $userId,
            'eid' => $exerciseId,
            'dur' => $durationMin
        ]);

        return $durationMin;
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

    private function ensureExerciseColumns($db)
    {
        if (!$this->exerciseColumnExists($db, 'qr_token')) {
            $db->exec('ALTER TABLE exercise ADD qr_token VARCHAR(64) DEFAULT NULL UNIQUE');
        }

        if (!$this->exerciseColumnExists($db, 'youtube_url')) {
            $db->exec('ALTER TABLE exercise ADD youtube_url VARCHAR(500) NULL');
        }

        $stmt = $db->query("SELECT id FROM exercise WHERE qr_token IS NULL OR qr_token = ''");
        $missingTokenExercises = $stmt->fetchAll();

        foreach ($missingTokenExercises as $exercise) {
            $update = $db->prepare('UPDATE exercise SET qr_token = :qr_token WHERE id = :id');
            $update->execute([
                'id' => $exercise['id'],
                'qr_token' => $this->generateQrToken()
            ]);
        }
    }

    private function exerciseColumnExists($db, $columnName)
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS column_exists
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => 'exercise',
            'column_name' => $columnName
        ]);
        $columnInfo = $stmt->fetch();

        return (int) ($columnInfo['column_exists'] ?? 0) > 0;
    }

    private function generateQrToken()
    {
        return bin2hex(random_bytes(16));
    }

    private function normalizeYoutubeUrl($youtubeUrl)
    {
        $youtubeUrl = trim((string) $youtubeUrl);

        if ($youtubeUrl === '' || strlen($youtubeUrl) > 500 || !filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $youtubeUrl;
    }
}
?>
