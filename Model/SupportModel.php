<?php
require_once __DIR__ . '/../config.php';

class SupportModel
{
    private $supportRequestColumns = null;

    public function createRequest($userId, $first, $last, $email, $type, $title, $desc, $aiData = [])
    {
        $db = config::getConnexion();

        $columns = ['user_id', 'first_name', 'last_name', 'email', 'type', 'issue_title', 'description', 'status', 'created_at'];
        $values = [':uid', ':first', ':last', ':email', ':type', ':title', ':desc', ':status', 'NOW()'];
        $params = [
            'uid' => $userId,
            'first' => $first,
            'last' => $last,
            'email' => $email,
            'type' => $type,
            'title' => $title,
            'desc' => $desc,
            'status' => 'pending'
        ];

        $aiData = is_array($aiData) ? $aiData : [];
        $optionalColumns = [
            'ai_category' => 'ai_category',
            'ai_priority' => 'ai_priority',
            'ai_summary' => 'ai_summary',
            'ai_suggested_solution' => 'ai_suggested_solution',
            'ai_user_solved' => 'ai_user_solved'
        ];

        foreach ($optionalColumns as $column => $dataKey) {
            if (array_key_exists($dataKey, $aiData) && $this->supportRequestHasColumn($column)) {
                $columns[] = $column;
                $values[] = ':' . $column;
                $params[$column] = $aiData[$dataKey];
            }
        }

        if ($aiData && $this->supportRequestHasColumn('ai_created_at')) {
            $columns[] = 'ai_created_at';
            $values[] = 'NOW()';
        }

        $columnSql = implode(', ', array_map(fn($column) => '`' . $column . '`', $columns));
        $valueSql = implode(', ', $values);
        $stmt = $db->prepare("INSERT INTO support_request ($columnSql) VALUES ($valueSql)");
        $stmt->execute($params);
    }

    public function listByUser($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM support_request WHERE user_id = :uid ORDER BY created_at DESC, id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function listAll()
    {
        $db = config::getConnexion();
        $orderBy = 'created_at DESC, id DESC';
        if ($this->supportRequestHasColumn('ai_priority')) {
            $orderBy = "CASE ai_priority
                    WHEN 'Urgent' THEN 4
                    WHEN 'High' THEN 3
                    WHEN 'Medium' THEN 2
                    WHEN 'Low' THEN 1
                    ELSE 0
                END DESC,
                created_at DESC,
                id DESC";
        }

        return $db->query("SELECT * FROM support_request ORDER BY $orderBy")->fetchAll();
    }

    public function getRequestById($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM support_request WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getTypeStatsByUser($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare(
            'SELECT type, COUNT(*) AS request_count
             FROM support_request
             WHERE user_id = :uid
             GROUP BY type
             ORDER BY request_count DESC, type ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE support_request SET first_name=:first, last_name=:last, email=:email, type=:type, issue_title=:title, description=:desc WHERE id=:id AND user_id=:uid');
        $stmt->execute([
            'id' => $id,
            'uid' => $userId,
            'first' => $first,
            'last' => $last,
            'email' => $email,
            'type' => $type,
            'title' => $title,
            'desc' => $desc
        ]);
    }

    public function deleteRequest($id, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM support_request WHERE id=:id AND user_id=:uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function addResponse($requestId, $adminId, $message)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO support_response (request_id, admin_id, message, responded_at) VALUES (:rid, :aid, :msg, NOW())');
        $stmt->execute(['rid' => $requestId, 'aid' => $adminId, 'msg' => $message]);
        $this->syncRequestStatus($requestId);
    }

    public function updateResponse($responseId, $message)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE support_response SET message = :msg WHERE id = :id');
        $stmt->execute(['id' => $responseId, 'msg' => $message]);
    }

    public function deleteResponse($responseId)
    {
        $db = config::getConnexion();
        $requestStmt = $db->prepare('SELECT request_id FROM support_response WHERE id = :id LIMIT 1');
        $requestStmt->execute(['id' => $responseId]);
        $requestId = (int) ($requestStmt->fetchColumn() ?: 0);

        $stmt = $db->prepare('DELETE FROM support_response WHERE id = :id');
        $stmt->execute(['id' => $responseId]);

        if ($requestId > 0) {
            $this->syncRequestStatus($requestId);
        }
    }

    public function listResponses($requestId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare(
            'SELECT sr.*, u.name AS admin_name
             FROM support_response sr
             JOIN `user` u ON u.id = sr.admin_id
             WHERE sr.request_id = :rid
             ORDER BY sr.responded_at DESC, sr.id DESC'
        );
        $stmt->execute(['rid' => $requestId]);
        return $stmt->fetchAll();
    }

    public function listResponsesForRequestIds($requestIds)
    {
        $requestIds = array_values(array_filter(array_map('intval', $requestIds), fn($id) => $id > 0));
        if (!$requestIds) {
            return [];
        }

        $db = config::getConnexion();
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $stmt = $db->prepare(
            "SELECT sr.*, u.name AS admin_name
             FROM support_response sr
             JOIN `user` u ON u.id = sr.admin_id
             WHERE sr.request_id IN ($placeholders)
             ORDER BY sr.responded_at DESC, sr.id DESC"
        );
        $stmt->execute($requestIds);

        $responsesByRequest = [];
        foreach ($stmt->fetchAll() as $response) {
            $requestId = (int) $response['request_id'];
            if (!isset($responsesByRequest[$requestId])) {
                $responsesByRequest[$requestId] = [];
            }
            $responsesByRequest[$requestId][] = $response;
        }

        return $responsesByRequest;
    }

    private function syncRequestStatus($requestId)
    {
        $db = config::getConnexion();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM support_response WHERE request_id = :rid');
        $countStmt->execute(['rid' => $requestId]);
        $hasResponses = (int) $countStmt->fetchColumn() > 0;

        $statusStmt = $db->prepare('UPDATE support_request SET status = :status WHERE id = :id');
        $statusStmt->execute([
            'status' => $hasResponses ? 'resolved' : 'pending',
            'id' => $requestId
        ]);
    }

    private function supportRequestHasColumn($column)
    {
        $allowedColumns = [
            'ai_category' => true,
            'ai_priority' => true,
            'ai_summary' => true,
            'ai_suggested_solution' => true,
            'ai_user_solved' => true,
            'ai_created_at' => true
        ];

        if (!isset($allowedColumns[$column])) {
            return false;
        }

        if ($this->supportRequestColumns === null) {
            try {
                $db = config::getConnexion();
                $stmt = $db->query('SHOW COLUMNS FROM support_request');
                $this->supportRequestColumns = [];
                foreach ($stmt->fetchAll() as $row) {
                    $this->supportRequestColumns[$row['Field']] = true;
                }
            } catch (Exception $e) {
                $this->supportRequestColumns = [];
            }
        }

        return isset($this->supportRequestColumns[$column]);
    }
}
?>
