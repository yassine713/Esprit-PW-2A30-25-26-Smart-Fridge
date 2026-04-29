<?php
require_once __DIR__ . '/../config.php';

class SupportModel
{
    public function createRequest($userId, $first, $last, $email, $type, $title, $desc)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO support_request (user_id, first_name, last_name, email, type, issue_title, description, status, created_at) VALUES (:uid, :first, :last, :email, :type, :title, :desc, :status, NOW())');
        $stmt->execute([
            'uid' => $userId,
            'first' => $first,
            'last' => $last,
            'email' => $email,
            'type' => $type,
            'title' => $title,
            'desc' => $desc,
            'status' => 'pending'
        ]);
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
        return $db->query('SELECT * FROM support_request ORDER BY created_at DESC, id DESC')->fetchAll();
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
}
?>
